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
     * @return array
     */
    private function job_data(int $attemptid, string $response = 'A response'): array {
        return [
            'questionattemptid' => $attemptid,
            'usageid' => 100 + $attemptid,
            'slot' => 1,
            'questionid' => 200 + $attemptid,
            'sourceattemptstepid' => 300 + $attemptid,
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
