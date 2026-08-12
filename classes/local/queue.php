<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace qtype_aitext\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Persistence and state transitions for asynchronous AI grading jobs.
 *
 * @package    qtype_aitext
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class queue {
    /** @var string Job is waiting for a worker. */
    public const STATUS_PENDING = 'pending';

    /** @var string Job is currently being processed by a worker. */
    public const STATUS_PROCESSING = 'processing';

    /** @var string Job was completed successfully. */
    public const STATUS_COMPLETE = 'complete';

    /** @var string Job failed permanently after retrying. */
    public const STATUS_FAILED = 'failed';

    /** @var int Number of attempts after which a job is permanently failed. */
    public const DEFAULT_MAX_ATTEMPTS = 5;

    /** @var int A processing job older than this is eligible for recovery. */
    public const STALE_AFTER = 1800;

    /**
     * Add a grading job. The source attempt step is the idempotency key.
     *
     * @param array $data Queue record fields.
     * @return int The inserted queue id.
     */
    public static function enqueue(array $data): int {
        global $DB;

        $now = time();
        $record = (object) [
            'questionattemptid' => (int) $data['questionattemptid'],
            'usageid' => (int) $data['usageid'],
            'slot' => (int) $data['slot'],
            'questionid' => (int) $data['questionid'],
            'sourceattemptstepid' => (int) $data['sourceattemptstepid'],
            'userid' => (int) $data['userid'],
            'prompt' => $data['prompt'],
            'response' => $data['response'],
            'responsehash' => $data['responsehash'],
            'defaultmark' => (float) $data['defaultmark'],
            'status' => self::STATUS_PENDING,
            'attempts' => 0,
            'nextrun' => $now,
            'timecreated' => $now,
            'timemodified' => $now,
            'lasterror' => null,
        ];

        // A question attempt step can only be graded once. This makes retries
        // safe and prevents duplicate jobs if a request is replayed.
        $existing = $DB->get_record('qtype_aitext_queue',
            [
                'questionattemptid' => $record->questionattemptid,
                'responsehash' => $record->responsehash,
            ],
            'id,status'
        );
        if ($existing) {
            return (int) $existing->id;
        }

        return (int) $DB->insert_record('qtype_aitext_queue', $record);
    }

    /**
     * Requeue jobs left in processing after a worker timeout.
     *
     * @param int|null $now Current Unix timestamp.
     * @return bool Whether the recovery update succeeded.
     */
    public static function recover_stale(?int $now = null): bool {
        global $DB;
        $now = $now ?? time();
        $cutoff = $now - self::STALE_AFTER;
        return $DB->execute(
            "UPDATE {qtype_aitext_queue}
                SET status = :pending, nextrun = :nextrun, timemodified = :modified,
                    lasterror = :error
              WHERE status = :processing AND timemodified < :cutoff",
            [
                'pending' => self::STATUS_PENDING,
                'nextrun' => $now,
                'modified' => $now,
                'error' => 'Recovered after the worker timed out.',
                'processing' => self::STATUS_PROCESSING,
                'cutoff' => $cutoff,
            ]
        );
    }

    /**
     * Claim the next due job. The scheduled task holds the class lock while
     * calling this method, so two cron runners cannot claim the same record.
     *
     * @param int|null $now Current Unix timestamp.
     * @return \stdClass|null The claimed record, or null when the queue is empty.
     */
    public static function claim_next(?int $now = null): ?\stdClass {
        global $DB;
        $now = $now ?? time();
        $job = $DB->get_record_select(
            'qtype_aitext_queue',
            'status = :status AND nextrun <= :now',
            ['status' => self::STATUS_PENDING, 'now' => $now],
            'nextrun ASC, id ASC',
            '*',
            0,
            1
        );
        if (!$job) {
            return null;
        }

        $job->status = self::STATUS_PROCESSING;
        $job->attempts++;
        $job->timemodified = $now;
        $DB->update_record('qtype_aitext_queue', $job);
        return $job;
    }

    /**
     * Cancel a job that must not be applied to the attempt.
     *
     * @param int $id Queue id.
     * @param string $reason Cancellation reason.
     * @return void
     */
    public static function cancel(int $id, string $reason): void {
        global $DB;
        $DB->update_record('qtype_aitext_queue', (object) [
            'id' => $id,
            'status' => self::STATUS_FAILED,
            'timemodified' => time(),
            'lasterror' => shorten_text($reason, 2000),
        ]);
    }

    /**
     * Mark a job complete and persist its result for auditability.
     *
     * @param int $id Queue id.
     * @param string $feedback Rendered feedback.
     * @param float $marks Awarded marks.
     * @return void
     */
    public static function complete(int $id, string $feedback, float $marks): void {
        global $DB;
        $DB->update_record('qtype_aitext_queue', (object) [
            'id' => $id,
            'status' => self::STATUS_COMPLETE,
            'feedback' => $feedback,
            'marks' => $marks,
            'timemodified' => time(),
            'lasterror' => null,
        ]);
    }

    /**
     * Put a job back in the queue or permanently fail it.
     *
     * @param \stdClass $job Claimed job.
     * @param \Throwable $exception Failure.
     * @return void
     */
    public static function fail(\stdClass $job, \Throwable $exception): void {
        global $DB;
        $now = time();
        $maxattempts = max(1, (int) (get_config('qtype_aitext', 'cron_max_attempts') ?: self::DEFAULT_MAX_ATTEMPTS));
        $permanent = $job->attempts >= $maxattempts;
        $delay = min(3600, max(60, (int) (get_config('qtype_aitext', 'cron_retry_delay') ?: 300) * $job->attempts));

        $DB->update_record('qtype_aitext_queue', (object) [
            'id' => $job->id,
            'status' => $permanent ? self::STATUS_FAILED : self::STATUS_PENDING,
            'nextrun' => $permanent ? $now : $now + $delay,
            'timemodified' => $now,
            'lasterror' => shorten_text($exception->getMessage(), 2000),
        ]);
    }
}
