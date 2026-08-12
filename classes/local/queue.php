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

    /** @var int Default number of attempts before a job is permanently failed. */
    public const DEFAULT_MAX_ATTEMPTS = 5;

    /** @var int Upper bound for administrator-configured retry attempts. */
    public const MAX_ATTEMPTS = 20;

    /** @var int Default number of jobs processed in one scheduled-task run. */
    public const DEFAULT_BATCH_SIZE = 5;

    /** @var int Upper bound for administrator-configured batch size. */
    public const MAX_BATCH_SIZE = 100;

    /** @var int Default retry delay in seconds. */
    public const DEFAULT_RETRY_DELAY = 300;

    /** @var int Upper bound for a retry delay in seconds. */
    public const MAX_RETRY_DELAY = 3600;

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

        // A question attempt step can only be graded once. A short distributed
        // lock keeps the lookup and insert atomic across web workers.
        $lockfactory = \core\lock\lock_config::get_lock_factory('cron');
        $lock = $lockfactory->get_lock('qtype_aitext_enqueue_' . $record->sourceattemptstepid, 10);
        if (!$lock) {
            return 0;
        }

        try {
            $existing = $DB->get_record('qtype_aitext_queue',
                ['sourceattemptstepid' => $record->sourceattemptstepid],
                'id,status'
            );
            if ($existing) {
                return (int) $existing->id;
            }
            return (int) $DB->insert_record('qtype_aitext_queue', $record);
        } finally {
            $lock->release();
        }
    }

    /**
     * Read an integer setting while preserving explicit zero and negative values
     * so public accessors can clamp them predictably.
     *
     * @param string $name Setting name without the component prefix.
     * @param int $default Default when no setting has been saved.
     * @return int
     */
    private static function get_configured_int(string $name, int $default): int {
        $value = get_config('qtype_aitext', $name);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }
        return (int) $value;
    }

    /**
     * Get a safe batch-size value from plugin configuration.
     *
     * @return int
     */
    public static function get_batch_size(): int {
        $configured = self::get_configured_int('cron_batch_size', self::DEFAULT_BATCH_SIZE);
        return min(self::MAX_BATCH_SIZE, max(1, $configured));
    }

    /**
     * Get a safe maximum-attempts value from plugin configuration.
     *
     * @return int
     */
    public static function get_max_attempts(): int {
        $configured = self::get_configured_int('cron_max_attempts', self::DEFAULT_MAX_ATTEMPTS);
        return min(self::MAX_ATTEMPTS, max(1, $configured));
    }

    /**
     * Get a safe retry-delay value from plugin configuration.
     *
     * @return int
     */
    public static function get_retry_delay(): int {
        $configured = self::get_configured_int('cron_retry_delay', self::DEFAULT_RETRY_DELAY);
        return min(self::MAX_RETRY_DELAY, max(60, $configured));
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
     * @param float|null $marks Awarded marks, or null when manual grading is still required.
     * @return void
     */
    public static function complete(int $id, string $feedback, ?float $marks): void {
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
        $maxattempts = self::get_max_attempts();
        $permanent = $job->attempts >= $maxattempts;
        $delay = min(self::MAX_RETRY_DELAY, self::get_retry_delay() * $job->attempts);

        $DB->update_record('qtype_aitext_queue', (object) [
            'id' => $job->id,
            'status' => $permanent ? self::STATUS_FAILED : self::STATUS_PENDING,
            'nextrun' => $permanent ? $now : $now + $delay,
            'timemodified' => $now,
            'lasterror' => shorten_text($exception->getMessage(), 2000),
        ]);
    }
}
