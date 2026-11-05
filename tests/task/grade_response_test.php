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

/**
 * Tests for the async grade_response adhoc task.
 *
 * @package    qtype_aitext
 * @copyright  2026 Fabian Barbuia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \qtype_aitext\task\grade_response
 */
final class grade_response_test extends \advanced_testcase {
    /**
     * Test that an adhoc task can be queued.
     */
    public function test_adhoc_task_is_queued(): void {
        $this->resetAfterTest();

        $tasksbefore = \core\task\manager::get_adhoc_tasks(grade_response::class);
        $this->assertEmpty($tasksbefore);

        $task = new grade_response();
        $task->set_custom_data([
            'attemptstepid' => 1,
            'response' => 'Test student response',
            'questionid' => 1,
            'defaultmark' => 10.0,
            'aiprompt' => 'Grade this response',
            'markscheme' => 'Full marks for any answer',
            'spellcheck' => false,
            'contextid' => \context_system::instance()->id,
        ]);

        \core\task\manager::queue_adhoc_task($task);

        $tasksafter = \core\task\manager::get_adhoc_tasks(grade_response::class);
        $this->assertCount(1, $tasksafter);
    }

    /**
     * Test that the task does not retry on failure.
     */
    public function test_retry_until_success_returns_false(): void {
        $task = new grade_response();
        $this->assertFalse($task->retry_until_success());
    }
}
