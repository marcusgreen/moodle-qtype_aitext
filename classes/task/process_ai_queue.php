<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace qtype_aitext\task;

defined('MOODLE_INTERNAL') || die();

use qtype_aitext\local\queue;

/**
 * Process delayed AI grading jobs from the Moodle cron worker.
 *
 * @package    qtype_aitext
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process_ai_queue extends \core\task\scheduled_task {
    /**
     * Return the human-readable task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_process_ai_queue', 'qtype_aitext');
    }

    /**
     * Process a bounded batch of jobs. The cron lock prevents concurrent
     * workers from claiming the same job on multi-node installations.
     *
     * @return void
     */
    public function execute(): void {
        if (!get_config('qtype_aitext', 'cron_enabled')) {
            return;
        }

        $lockfactory = \core\lock\lock_config::get_lock_factory('cron');
        $lock = $lockfactory->get_lock('qtype_aitext_ai_queue', 0);
        if (!$lock) {
            mtrace('qtype_aitext: another queue worker is already running.');
            return;
        }

        try {
            queue::recover_stale();
            $batchsize = queue::get_batch_size();
            for ($index = 0; $index < $batchsize; $index++) {
                $job = queue::claim_next();
                if (!$job) {
                    break;
                }

                try {
                    $this->process_job($job);
                    mtrace('qtype_aitext: completed AI grading job ' . $job->id . '.');
                } catch (\Throwable $exception) {
                    queue::fail($job, $exception);
                    mtrace('qtype_aitext: AI grading job ' . $job->id . ' failed: ' . $exception->getMessage());
                }
            }
        } finally {
            $lock->release();
        }
    }

    /**
     * Process one claimed job.
     *
     * @param \stdClass $job Queue record.
     * @return void
     * @throws \moodle_exception If the question attempt no longer exists.
     */
    protected function process_job(\stdClass $job): void {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/question/engine/lib.php');
        require_once($CFG->dirroot . '/mod/quiz/lib.php');

        $quba = \question_engine::load_questions_usage_by_activity((int) $job->usageid);
        $qa = $quba->get_question_attempt((int) $job->slot);
        if ((int) $qa->get_database_id() !== (int) $job->questionattemptid) {
            throw new \moodle_exception('err_queueattemptmismatch', 'qtype_aitext');
        }

        $question = $qa->get_question();
        if (!($question instanceof \qtype_aitext_question)) {
            throw new \moodle_exception('err_queuewrongtype', 'qtype_aitext');
        }

        // Do not apply an old result after the student has submitted a newer
        // response. The response hash is generated before the job is queued.
        $lateststep = $qa->get_last_step_with_qt_var('answer');
        if (!$lateststep) {
            queue::cancel((int) $job->id, 'The source response step no longer exists.');
            return;
        }
        $latestresponse = $lateststep->get_qt_var('answer');
        if (hash('sha256', (string) $latestresponse) !== $job->responsehash) {
            queue::cancel((int) $job->id, 'A newer response was submitted before this job ran.');
            return;
        }

        $spellcheckresponse = null;
        if ($question->spellcheck) {
            $spellcheckprompt = $question->build_full_ai_spellchecking_prompt((string) $job->response);
            $spellcheckresponse = $question->perform_request($spellcheckprompt, 'feedback', (int) $job->userid);
        }

        $rawfeedback = $question->perform_request($job->prompt, 'feedback', (int) $job->userid);
        $content = $question->process_feedback($rawfeedback, (int) $job->userid);
        $marks = null;
        if (is_numeric($content->marks) && is_finite((float) $content->marks)) {
            $marks = max(0.0, min((float) $job->defaultmark, (float) $content->marks));
        }

        $transaction = $DB->start_delegated_transaction();
        try {
            // Use the support user for the audit trail: the AI worker, not the
            // student, is responsible for this grading action.
            $supportuser = \core_user::get_support_user();
            $qa->manual_grade($content->feedback, $marks, FORMAT_HTML, time(), $supportuser->id);
            \question_engine::save_questions_usage_by_activity($quba);

            $newstep = $qa->get_last_step();
            if ($newstep->get_id()) {
                $stepdata = [
                    '-aiprompt' => $job->prompt,
                    '-aicontent' => $content->feedback,
                    '-aipending' => '0',
                ];
                if ($spellcheckresponse !== null) {
                    $stepdata['-spellcheckresponse'] = $spellcheckresponse;
                }
                $this->insert_step_data($newstep->get_id(), $stepdata);
            }

            queue::complete((int) $job->id, (string) $content->feedback, $marks);

            // When the quiz attempt has already been submitted, question engine
            // data is updated first and then the quiz attempt/gradebook is
            // refreshed to include the delayed mark.
            $quizattempt = $DB->get_record('quiz_attempts', ['uniqueid' => $job->usageid]);
            if ($quizattempt && $quizattempt->state === 'finished') {
                $quizattempt->sumgrades = $quba->get_total_mark();
                $quizattempt->timemodified = time();
                $DB->update_record('quiz_attempts', $quizattempt);
                $quiz = $DB->get_record('quiz', ['id' => $quizattempt->quiz]);
                if ($quiz) {
                    quiz_update_grades($quiz, $quizattempt->userid);
                }
            }

            $transaction->allow_commit();
        } catch (\Throwable $exception) {
            // Moodle's rollback API rethrows the original exception. The outer
            // task loop catches it and moves the queue job into retry/failed.
            $transaction->rollback($exception);
        }
    }

    /**
     * Store AI metadata on the newly-created manual grade step.
     *
     * @param int $stepid Step id.
     * @param array $data Step data.
     * @return void
     */
    private function insert_step_data(int $stepid, array $data): void {
        global $DB;
        foreach ($data as $name => $value) {
            $conditions = ['attemptstepid' => $stepid, 'name' => $name];
            $existing = $DB->get_record('question_attempt_step_data', $conditions);
            if ($existing) {
                $existing->value = $value;
                $DB->update_record('question_attempt_step_data', $existing);
                continue;
            }
            $DB->insert_record('question_attempt_step_data', (object) ($conditions + ['value' => $value]));
        }
    }
}
