<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

use qtype_aitext\local\queue;

/**
 * Tests for the asynchronous AI grading queue.
 *
 * @package    qtype_aitext
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class qtype_aitext_queue_test extends advanced_testcase {
    /**
     * Build a minimal valid job payload.
     *
     * @param int $attemptid Attempt id.
     * @param string $response Student response.
     * @param int|null $stepid Source attempt step id.
     * @return array
     */
    private function job_data(int $attemptid, string $response = 'A response', ?int $stepid = null): array {
        return [
            'questionattemptid' => $attemptid,
            'usageid' => 100 + $attemptid,
            'slot' => 1,
            'questionid' => 200 + $attemptid,
            'sourceattemptstepid' => $stepid ?? 300 + $attemptid,
            'userid' => 400 + $attemptid,
            'prompt' => 'Grade: ' . $response,
            'response' => $response,
            'responsehash' => hash('sha256', $response),
            'defaultmark' => 10,
        ];
    }

    /**
     * The same attempt and response cannot create duplicate queue work.
     *
     * @return void
     */
    public function test_enqueue_is_idempotent(): void {
        $this->resetAfterTest(true);
        $data = $this->job_data(1);

        $firstid = queue::enqueue($data);
        $secondid = queue::enqueue($data);

        global $DB;
        $this->assertSame($firstid, $secondid);
        $this->assertEquals(1, $DB->count_records('qtype_aitext_queue'));
    }

    /**
     * Different responses in the same attempt create distinct grading jobs.
     *
     * @return void
     */
    public function test_different_responses_create_distinct_jobs(): void {
        global $DB;
        $this->resetAfterTest(true);

        $firstid = queue::enqueue($this->job_data(4, 'First response', 304));
        $secondid = queue::enqueue($this->job_data(4, 'Second response', 305));

        $this->assertNotSame($firstid, $secondid);
        $this->assertEquals(2, $DB->count_records('qtype_aitext_queue'));
    }

    /**
     * Claiming and stale recovery return a job to the pending state.
     *
     * @return void
     */
    public function test_claim_and_recover_stale_job(): void {
        global $DB;
        $this->resetAfterTest(true);
        $id = queue::enqueue($this->job_data(2));

        $claimed = queue::claim_next(time());
        $this->assertEquals($id, $claimed->id);
        $this->assertEquals(queue::STATUS_PROCESSING, $claimed->status);

        $DB->set_field('qtype_aitext_queue', 'timemodified', time() - queue::STALE_AFTER - 1, ['id' => $id]);
        queue::recover_stale(time());

        $recovered = $DB->get_record('qtype_aitext_queue', ['id' => $id]);
        $this->assertEquals(queue::STATUS_PENDING, $recovered->status);
        $this->assertLessThanOrEqual(time(), $recovered->nextrun);
    }

    /**
     * Configured queue limits are normalized before they are used by cron.
     *
     * @return void
     */
    public function test_queue_configuration_limits(): void {
        $this->resetAfterTest(true);
        set_config('cron_batch_size', 0, 'qtype_aitext');
        set_config('cron_max_attempts', 999, 'qtype_aitext');
        set_config('cron_retry_delay', -1, 'qtype_aitext');

        $this->assertEquals(1, queue::get_batch_size());
        $this->assertEquals(queue::MAX_ATTEMPTS, queue::get_max_attempts());
        $this->assertEquals(60, queue::get_retry_delay());
    }

    /**
     * A completed job can retain a null mark when manual grading is required.
     *
     * @return void
     */
    public function test_complete_preserves_null_mark(): void {
        global $DB;
        $this->resetAfterTest(true);
        $id = queue::enqueue($this->job_data(5));

        queue::complete($id, 'Feedback requiring manual review', null);
        $record = $DB->get_record('qtype_aitext_queue', ['id' => $id]);

        $this->assertEquals(queue::STATUS_COMPLETE, $record->status);
        $this->assertNull($record->marks);
    }

    /**
     * Failed jobs are retried and eventually become permanently failed.
     *
     * @return void
     */
    public function test_failure_retry_limit(): void {
        global $DB;
        $this->resetAfterTest(true);
        set_config('cron_max_attempts', 2, 'qtype_aitext');
        $id = queue::enqueue($this->job_data(3));
        $job = queue::claim_next(time());

        queue::fail($job, new RuntimeException('Temporary backend failure'));
        $retry = $DB->get_record('qtype_aitext_queue', ['id' => $id]);
        $this->assertEquals(queue::STATUS_PENDING, $retry->status);

        $retry->attempts = 2;
        $DB->update_record('qtype_aitext_queue', $retry);
        queue::fail($retry, new RuntimeException('Permanent backend failure'));
        $failed = $DB->get_record('qtype_aitext_queue', ['id' => $id]);
        $this->assertEquals(queue::STATUS_FAILED, $failed->status);
    }
}
