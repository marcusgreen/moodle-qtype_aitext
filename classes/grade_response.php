<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace qtype_aitext\task;

use core\output\stored_progress_bar;
use core\task\adhoc_task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');

/**
 * Adhoc task for asynchronous AI grading of aitext question responses.
 *
 * @package    qtype_aitext
 * @copyright  2026 Fabian Barbuia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade_response extends adhoc_task {
    use \core\task\stored_progress_task_trait;

    #[\Override]
    public function execute(): void {
        global $DB;

        try {
            $customdata = $this->get_custom_data();
            $attemptstepid = $customdata->attemptstepid;
            $answer = $customdata->response;
            $questionid = $customdata->questionid;
            $questionattemptid = (int) $customdata->questionattemptid;
            $defaultmark = (float) $customdata->defaultmark;
            $aiprompt = $customdata->aiprompt;
            $markscheme = $customdata->markscheme;
            $spellcheck = !empty($customdata->spellcheck);
            $contextid = (int) $customdata->contextid;

            // Resolve the step the engine now actually scores, which may have moved on from
            // $attemptstepid since this task was queued (apply_attempt_state() is always handed
            // the attempt's first step, never the current one). Fall back to $attemptstepid only
            // if no step at all can be found for this question attempt (should not normally happen).
            $targetstepid = (int) $DB->get_field_sql(
                'SELECT id FROM {question_attempt_steps} WHERE questionattemptid = ? ORDER BY sequencenumber DESC',
                [$questionattemptid],
                IGNORE_MULTIPLE
            ) ?: $attemptstepid;

            $idnumber = stored_progress_bar::convert_to_idnumber(get_class($this) . '_' . $this->get_id());
            $existingbar = stored_progress_bar::get_by_idnumber($idnumber);

            if ($existingbar) {
                $this->progress = $existingbar;
            } else {
                $this->initialise_stored_progress();
            }
            $this->progress->update(0, 100, get_string('async_grading_started', 'qtype_aitext'));

            // Load the question so we can call its methods.
            $questiondata = \question_bank::load_question_data($questionid);
            $question = \question_bank::make_question($questiondata);
            $question->contextid = $contextid;

            // Give the question a step with the right id, so it can resolve the attempt context
            // etc. apply_attempt_state() only needs the step's id, not its data.
            $question->apply_attempt_state(new \question_attempt_step([], null, null, $targetstepid));

            // Perform spellchecking if enabled.
            $behaviourvars = [];
            if ($spellcheck) {
                $this->progress->update(10, 100, get_string('async_spellcheck_running', 'qtype_aitext'));
                $behaviourvars['_spellcheckresponse'] = $question->get_spellchecking($answer);
            }

            // Build the full AI prompt and make the request.
            $this->progress->update(30, 100, get_string('async_grading_requesting', 'qtype_aitext'));
            $fullaiprompt = $question->build_full_ai_prompt($answer, $aiprompt, $defaultmark, $markscheme);
            $feedback = $question->perform_request($fullaiprompt, 'feedback');

            $this->progress->update(70, 100, get_string('async_grading_processing', 'qtype_aitext'));
            $contentobject = $question->process_feedback($feedback);

            // Calculate the grade fraction.
            $fraction = 0.0;
            if (is_null($contentobject->marks)) {
                $state = \question_state::$needsgrading;
            } else {
                if (is_numeric($contentobject->marks) && $defaultmark > 0) {
                    $fraction = min(max(0, (float) $contentobject->marks / $defaultmark), 1.0);
                }
                $state = \question_state::graded_state_for_fraction($fraction);
            }

            $behaviourvars['_comment'] = $contentobject->feedback;
            $behaviourvars['_commentformat'] = FORMAT_HTML;
            $behaviourvars['_aiprompt'] = $fullaiprompt;

            \qtype_aitext_question::persist_step_vars(
                $targetstepid,
                $behaviourvars,
                ['_aigraded' => '1'],
                $contextid,
                $fraction,
                $state
            );

            // question_attempt::regrade() always rebuilds a regraded attempt from a verbatim copy
            // of the attempt's first step, so if $attemptstepid is a different, earlier step, mirror
            // _aigraded there too.
            if ($targetstepid !== $attemptstepid) {
                \qtype_aitext_question::persist_step_vars($attemptstepid, [], ['_aigraded' => '1'], $contextid);
            }

            $this->progress->update_full(
                100,
                get_string('async_grading_complete', 'qtype_aitext')
            );

            $this->recalculate_quiz_grades($questionattemptid);

            mtrace("[qtype_aitext] Async grading complete for step {$targetstepid}");
        } catch (\Exception $exception) {
            mtrace('[qtype_aitext] Async grading failed: ' . $exception->getMessage());
            mtrace($exception->getTraceAsString());

            // Mark as failed so the placeholder can show an error. $targetstepid may not have
            // resolved if the failure happened before it was computed, so fall back to
            // $attemptstepid, which is always available as soon as custom data is read.
            $errorstepid = $targetstepid ?? $attemptstepid ?? null;
            if (isset($errorstepid, $contextid)) {
                \qtype_aitext_question::persist_step_vars(
                    $errorstepid,
                    [
                        '_comment' => get_string('async_grading_failed', 'qtype_aitext'),
                        '_commentformat' => FORMAT_HTML,
                    ],
                    ['_aigraded' => 'error'],
                    $contextid
                );

                // See the matching comment in the try block above: mirror onto $attemptstepid too.
                if (isset($attemptstepid) && $errorstepid !== $attemptstepid) {
                    \qtype_aitext_question::persist_step_vars($attemptstepid, [], ['_aigraded' => 'error'], $contextid);
                }
            }

            if ($this->progress->get_percent() === 0.0) {
                $this->progress->update_full(100, '');
            }
            $this->progress->error(get_string('async_grading_failed', 'qtype_aitext'));
        }
    }

    /**
     * Recalculate quiz attempt sumgrades and the user's final quiz grade after
     * an asynchronous AI grading step completes.
     *
     * This is intentionally quiz-specific: other activity types that use the
     * question engine would need their own recalculation hook here.
     * @throws \dml_exception
     */
    private function recalculate_quiz_grades(int $questionattemptid): void {
        global $CFG, $DB;

        $questionusageid = $DB->get_field('question_attempts', 'questionusageid', ['id' => $questionattemptid]);
        if (!$questionusageid) {
            return;
        }

        $quizattempt = $DB->get_record('quiz_attempts', ['uniqueid' => $questionusageid]);
        if (!$quizattempt || $quizattempt->state !== \mod_quiz\quiz_attempt::FINISHED) {
            return;
        }

        // Recalculate sumgrades for this specific attempt using the same subquery
        // the quiz module uses, which excludes attempts still in the needsgrading state.
        $dm = new \question_engine_data_mapper();
        $DB->execute(
            "UPDATE {quiz_attempts}
                SET timemodified = :timenow,
                    sumgrades    = ({$dm->sum_usage_marks_subquery('uniqueid')})
              WHERE id = :id",
            ['timenow' => time(), 'id' => $quizattempt->id]
        );

        // Recompute the user's final grade and push it to the gradebook.
        require_once($CFG->dirroot . '/mod/quiz/lib.php');
        $quizobj = \mod_quiz\quiz_settings::create((int)$quizattempt->quiz);
        \mod_quiz\grade_calculator::create($quizobj)->recompute_final_grade((int)$quizattempt->userid);
    }

    /**
     * Sets the initial progress of the associated progress bar.
     */
    public function set_initial_progress(): void {
        $this->progress->update_full(0, get_string('async_grading_waiting', 'qtype_aitext'));
    }

    /**
     * Initializes and starts a stored progress bar for tracking progress.
     *
     * @return void
     */
    public function initialise_stored_progress(): void {
        // In Moodle 5.0+, adhoc_task provides this method natively; delegate to it.
        if (method_exists(adhoc_task::class, 'initialise_stored_progress')) {
            parent::initialise_stored_progress();
            return;
        }

        // Required for 4.5 compatibility.
        $this->progress = new stored_progress_bar(
            stored_progress_bar::convert_to_idnumber(get_class($this) . '_' . $this->get_id())
        );
        $this->progress->create();
        $this->progress->start();
    }

    #[\Override]
    public function retry_until_success(): bool {
        // We don't want to retry this task endlessly.
        return false;
    }
}
