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


/**
 * Tests for AI Text
 *
 * @package    qtype_aitext
 * @category   test
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_aitext;
use qtype_aitext;
use qtype_aitext_test_helper;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/aitext/tests/helper.php');
require_once($CFG->dirroot . '/question/type/aitext/questiontype.php');

class log_test extends \advanced_testcase {
    /**
     * Always aitext
     *
     * @var mixed
     */
    protected $question;

    protected function setUp(): void {
        parent::setUp();
        // $this->qtype->id = rand(100, 1000);
        $this->question = qtype_aitext_test_helper::make_aitext_question([]);
        $this->setAdminUser();
    }

    public function test_insert_all_prompts() {
        global $DB;
        $this->resetAfterTest(true);

        // Set config for logging all prompts
        set_config('logallprompts', '1', 'qtype_aitext');

        $log = new log();
        $prompt = "Test prompt text";

        // Test the insert
        $result = $log->insert($this->question->id,$prompt);

        // // Assert the result is true
        $this->assertTrue($result);

        // // Verify the database record
        $records = $DB->get_records('qtype_aitext_log');
        $this->assertCount(1, $records);
    }

    public function test_insert_injection_detection() {
        global $DB;
        $this->resetAfterTest(true);


        // Set config for regular expressions
        set_config('regularexpressions', '1', 'qtype_aitext');
        set_config('injectionprompts', '/malicious|hack/', 'qtype_aitext');

        $log = new log();
        $prompt = "This is a malicious prompt";

        // Test the insert
        $result = $log->insert($this->question->id,$prompt);

        // Assert the result is true
        $this->assertTrue($result);

        // Verify the database record
        $records = $DB->get_records('qtype_aitext_log');
        $this->assertCount(1, $records);

        // $record = reset($records);
        // $this->assertEquals($prompt, $record->prompt);
        // $this->assertEquals('/malicious|hack/', $record->pattern);
    }

    public function test_insert_no_logging() {
        global $DB;
        $this->resetAfterTest(true);

        // Ensure both logging options are disabled
        set_config('logallprompts', '0', 'qtype_aitext');
        set_config('regularexpressions', '0', 'qtype_aitext');

        $log = new log();
        $prompt = "Normal prompt text";

        // Test the insert
        $result = $log->insert($this->question->id,$prompt);

        // Assert the result is false
        $this->assertFalse($result);

        // Verify no records were created
        $records = $DB->get_records('qtype_aitext_log');
        $this->assertCount(0, $records);
    }
}
