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

namespace qtype_aitext;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/aitext/tests/helper.php');
require_once($CFG->dirroot . '/question/type/aitext/questiontype.php');

use core_ai\aiactions\generate_text;

use qtype_aitext_test_helper;
use qtype_aitext;

/**
 * Tests for AI Text
 *
 * @package    qtype_aitext
 * @category   test
 * @copyright  2025 2024 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

final class ai_tool_test extends \advanced_testcase {

    /**
     * Instance of the question type class
     * @var question
     */
    public $question;


    /** @var \core_ai\manager AI Manager. */
    private $manager;

    /**
     * There is a live connection to the External AI system
     * When run locally it will make a connection. Otherwise the
     * tests will be skipped
     * @var bool
     */
    protected bool $islive = false;

    /**
     * Config.php should include the apikey and orgid in the form
     * define("TEST_LLM_APIKEY", "XXXXXXXXXXXX");
     * define("TEST_LLM_ORGID", "XXXXXXXXXXXX");
     * Summary of setUp
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->question = new \qtype_aitext();
        if (defined('TEST_LLM_APIKEY') && defined('TEST_LLM_ORGID')) {
            set_config('apikey', TEST_LLM_APIKEY, 'tool_aiconnect');
            set_config('backend','tool_aimanager', 'qtype_aitext');

            $this->islive = true;
        }

    }

    public function test_perform_request(): void {
        $this->resetAfterTest(true);
        if (!$this->islive) {
                $this->markTestSkipped('No live connection to the AI system');
        }

        $aitext = qtype_aitext_test_helper::make_aitext_question([]);
        $aitext->questiontext = 'What is 2 * 4?';
        $response = $aitext->perform_request('What is 2 * 4 only return a single number');
        xdebug_break();
        $this->assertEquals('8', $response);
    }

}

