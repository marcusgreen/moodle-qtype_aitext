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
            $response = $customdata->response;
            $questionid = $customdata->questionid;
            $questionattemptid = (int)$customdata->questionattemptid;
            $defaultmark = (float) $customdata->defaultmark;
            $aiprompt = $customdata->aiprompt;
            $markscheme = $customdata->markscheme;
            $spellcheck = !empty($customdata->spellcheck);
            $contextid = (int) $customdata->contextid;

            $this->start_stored_progress();
            $this->progress->update(0, 100, get_string('async_grading_started', 'qtype_aitext'));

            // Load the question so we can call its methods.
            $questiondata = \question_bank::load_question_data($questionid);
            $question = \question_bank::make_question($questiondata);
            $question->contextid = $contextid;

            // Create a fake step so the question can write step data.
            $fakestep = new \question_attempt_step();
            $reflectionclass = new \ReflectionClass($fakestep);
            $idproperty = $reflectionclass->getProperty('id');
            $idproperty->setAccessible(true);
            $idproperty->setValue($fakestep, $attemptstepid);
            $question->apply_attempt_state($fakestep);

            // Perform spellchecking if enabled.
            if ($spellcheck) {
                $this->progress->update(10, 100, get_string('async_spellcheck_running', 'qtype_aitext'));
                $fullaispellcheckprompt = $question->build_full_ai_spellchecking_prompt($response);
                $spellcheckresponse = $question->perform_request($fullaispellcheckprompt, 'feedback');
                $this->insert_attempt_step_data($attemptstepid, '-spellcheckresponse', $spellcheckresponse);
            }

            // Build the full AI prompt and make the request.
            $this->progress->update(30, 100, get_string('async_grading_requesting', 'qtype_aitext'));
            $fullaiprompt = $question->build_full_ai_prompt($response, $aiprompt, $defaultmark, $markscheme);
            $feedback = $question->perform_request($fullaiprompt, 'feedback');

            $this->progress->update(70, 100, get_string('async_grading_processing', 'qtype_aitext'));
            $contentobject = $question->process_feedback($feedback);

            // Calculate the grade fraction.
            $fraction = 0.0;
            if (is_null($contentobject->marks)) {
                $state = \question_state::$needsgrading;
            } else {
                if (is_numeric($contentobject->marks) && $defaultmark > 0) {
                    $fraction = (float) $contentobject->marks / $defaultmark;
                }
                $state = \question_state::graded_state_for_fraction($fraction);
            }

            // Write the grading data to the attempt step.
            $this->insert_attempt_step_data($attemptstepid, '-aiprompt', $fullaiprompt);
            $this->insert_attempt_step_data($attemptstepid, '-aicontent', $contentobject->feedback);
            $this->insert_attempt_step_data($attemptstepid, '-comment', $contentobject->feedback);
            $this->insert_attempt_step_data($attemptstepid, '-commentformat', FORMAT_HTML);
            $this->insert_attempt_step_data($attemptstepid, '-aifraction', (string) $fraction);
            $this->insert_attempt_step_data($attemptstepid, '-aistate', $state);
            $this->insert_attempt_step_data($attemptstepid, '-aigraded', '1');

            $this->progress->update_full(
                100,
                get_string('async_grading_complete', 'qtype_aitext')
            );

            // Update fraction and state on the latest step (the one the engine reads for scoring).
            $lateststep = $DB->get_record_sql(
                'SELECT id FROM {question_attempt_steps} WHERE questionattemptid = ? ORDER BY sequencenumber DESC',
                [$questionattemptid],
                IGNORE_MULTIPLE
            );

            if ($lateststep) {
                $DB->update_record('question_attempt_steps', (object)[
                    'id' => $lateststep->id,
                    'fraction' => $fraction,
                    'state' => $state->__toString(),
                ]);
                mtrace("Updated step {$lateststep->id} with fraction {$fraction}, state {$state}.");
            } else {
                mtrace("Warning — no step found for question attempt with ID {$questionattemptid}!");
            }

            $this->recalculate_quiz_grades($questionattemptid);

            mtrace("[qtype_aitext] Async grading complete for step {$attemptstepid}");
        } catch (\Exception $exception) {
            mtrace('[qtype_aitext] Async grading failed: ' . $exception->getMessage());
            mtrace($exception->getTraceAsString());

            // Mark as failed so the placeholder can show an error.
            if (isset($attemptstepid)) {
                $this->insert_attempt_step_data($attemptstepid, '-aigraded', 'error');
                $this->insert_attempt_step_data(
                    $attemptstepid,
                    '-comment',
                    get_string('async_grading_failed', 'qtype_aitext')
                );
                $this->insert_attempt_step_data($attemptstepid, '-commentformat', FORMAT_HTML);
            }

            if ($this->progress->get_percent() === 0.0) {
                $this->progress->update_full(100, '');
            }
            $this->progress->error(get_string('async_grading_failed', 'qtype_aitext'));
        }
    }

    /**
     * Insert attempt step data directly into the database.
     *
     * @param int $attemptstepid The attempt step ID.
     * @param string $name The data name.
     * @param string $value The data value.
     */
    private function insert_attempt_step_data(int $attemptstepid, string $name, string $value): void {
        global $DB;

        // Update if exists, otherwise insert.
        $existing = $DB->get_record('question_attempt_step_data', [
            'attemptstepid' => $attemptstepid,
            'name' => $name,
        ]);

        if ($existing) {
            $existing->value = $value;
            $DB->update_record('question_attempt_step_data', $existing);
        } else {
            $DB->insert_record('question_attempt_step_data', [
                'attemptstepid' => $attemptstepid,
                'name' => $name,
                'value' => $value,
            ]);
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
