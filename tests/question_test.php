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

use coding_exception;
use core_reportbuilder\external\filters\set;
use PHPUnit\Framework\ExpectationFailedException;
use question_attempt_step;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/aitext/tests/helper.php');
require_once($CFG->dirroot . '/question/type/aitext/questiontype.php');

use qtype_aitext_test_helper;
use qtype_aitext;

/**
 * Unit tests for the matching question definition class.
 *
 * @package qtype_aitext
 * @copyright 2025 Marcus Green
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class question_test extends \advanced_testcase {

    /**
     * Instance of the question type class
     * @var question
     */
    public $question;

    /**
     * There is a live connection to the External AI system
     * When run locally it will make a connection. Otherwise the
     * tests will be skipped
     * @var bool
     */
    protected int $islive;

    /**
     * Config.php should include the apikey and orgid in the form
     * define("TEST_LLM_APIKEY", "XXXXXXXXXXXX");
     * define("TEST_LLM_ORGID", "XXXXXXXXXXXX");
     * Summary of setUp
     * @return void
     */
    protected function setUp(): void {
        $this->question = new \qtype_aitext();
        if (defined('TEST_LLM_APIKEY') && defined('TEST_LLM_ORGID')) {
            set_config('apikey', TEST_LLM_APIKEY, 'aiprovider_openai');
            set_config('orgid', TEST_LLM_ORGID, 'aiprovider_openai');
            set_config('enabled', true, 'aiprovider_openai');
            $this->islive = true;
        }
    }
    /**
     * Make a trivial request to the LLM to check the code works
     * Only designed to test the 4.5 subsystem when run locally
     * not when in GHA ci
     * @covers \qtype_aitext\question::perform_request
     * @return void
     */
    public function test_perform_request(): void {
        $this->resetAfterTest(true);
        if (!$this->islive) {
                $this->markTestSkipped('No live connection to the AI system');
        }
        $aitext = qtype_aitext_test_helper::make_aitext_question([]);
        $aitext->questiontext = 'What is 2 * 4?';
        $response = $aitext->perform_request('What is 2 * 4 only return a single number');
        $this->assertEquals('8', $response);
    }


    /**
     * Tests the call to the quesitonbase summary code
     *
     * @covers ::get_question_summary()
     * @return void
     * @throws coding_exception
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function test_get_question_summary(): void {
            $aitext = qtype_aitext_test_helper::make_aitext_question([]);
            $aitext->questiontext = 'Hello <img src="http://example.com/globe.png" alt="world" />';
            $this->assertEquals('Hello [world]', $aitext->get_question_summary());
    }

    /**
     * Check on some permutations of how the prompt that is sent to the
     * LLM is constructed
     * @covers ::build_full_ai_prompt
     */
    public function test_build_full_ai_prompt() :void {
        $this->resetAfterTest();

        $question = qtype_aitext_test_helper::make_aitext_question([]);
        set_config('prompt', 'in [responsetext] ','qtype_aitext');
        set_config('defaultprompt', 'check this','qtype_aitext');
        set_config('markscheme', 'one mark','qtype_aitext');
        set_config('jsonprompt', 'testprompt','qtype_aitext');

        $response = '<p> Thank you </p>';
        $result = $question->build_full_ai_prompt($response, $aiprompt, $defaultmark, $markscheme);

        $this->assertStringContainsString('[[ Thank you ]]', $result);
        // HTML tags should be stripped out.
        $this->assertStringNotContainsString('<p>', $result);

        $markscheme = "2 points";
        $result = $question->build_full_ai_prompt($response, $aiprompt, $defaultmark, $markscheme);
        $this->assertStringContainsString('2 points', $result);
}

    /**
     * Check that non valid json returned from the LLM is
     * dealt with gracefully
     * @covers ::process_feedback()
     *
     * @return void
     */
    public function test_get_feedback(): void {
        // Create the aitext question under test.
        $questiontext = 'AI question text';
        $aitext = qtype_aitext_test_helper::make_aitext_question(['questiontext' => $questiontext, 'model' => 'llama3']);
        $testdata = [
                "feedback" => "Feedback text",
                "marks" => 0,
                ];
        $goodjson = json_encode($testdata);

        $feedback = $aitext->process_feedback($goodjson);
        $this->assertIsObject($feedback);
        $badjson = 'Some random string'. $goodjson;
        $feedback = $aitext->process_feedback($badjson);
        $this->assertIsObject($feedback);
    }

    /**
     * Test summarise_response() when teachers view quiz attempts and then
     * review them to see what has been saved in the response history table.
     *
     * @covers ::summarise_response()
     */
    public function test_summarise_response(): void {
        $this->resetAfterTest();

        // Create the aitext question under test.
        $questiontext = 'AI question text';
        $aitext = qtype_aitext_test_helper::make_aitext_question(['questiontext' => $questiontext]);
        $aitext->start_attempt(new question_attempt_step(), 1);

        $aitext->responseformat = 'editor';

        $this->assertEquals($questiontext, $aitext->summarise_response(
            ['answer' => $questiontext, 'answerformat' => FORMAT_HTML]));
    }


    /**
     * Test aitext is_same_response, used when scrolling beween questions
     *
     * @covers ::is_same_response()
     *
     * @return void
     */
    public function test_is_same_response(): void {

        $aitext = qtype_aitext_test_helper::make_aitext_question([]);

        $aitext->responsetemplate = '';

        $aitext->start_attempt(new question_attempt_step(), 1);

        $this->assertTrue($aitext->is_same_response(
                [],
                ['answer' => '']));

        $this->assertTrue($aitext->is_same_response(
                ['answer' => ''],
                ['answer' => '']));

        $this->assertTrue($aitext->is_same_response(
                ['answer' => ''],
                []));

        $this->assertFalse($aitext->is_same_response(
                ['answer' => 'Hello'],
                []));

        $this->assertFalse($aitext->is_same_response(
                ['answer' => 'Hello'],
                ['answer' => '']));

        $this->assertFalse($aitext->is_same_response(
                ['answer' => 0],
                ['answer' => '']));

        $this->assertFalse($aitext->is_same_response(
                ['answer' => ''],
                ['answer' => 0]));

        $this->assertFalse($aitext->is_same_response(
                ['answer' => '0'],
                ['answer' => '']));

        $this->assertFalse($aitext->is_same_response(
                ['answer' => ''],
                ['answer' => '0']));
    }


    /**
     * Test aitext is_same_response, used when scrolling beween questions
     *
     * @covers ::is_same_response_with_template()
     */
    public function test_is_same_response_with_template(): void {
        $aitext = qtype_aitext_test_helper::make_aitext_question([]);

        $aitext->responsetemplate = 'Once upon a time';

        $aitext->start_attempt(new question_attempt_step(), 1);

        $this->assertTrue($aitext->is_same_response(
                [],
                ['answer' => 'Once upon a time']));

        $this->assertTrue($aitext->is_same_response(
                ['answer' => ''],
                ['answer' => 'Once upon a time']));

        $this->assertTrue($aitext->is_same_response(
                ['answer' => 'Once upon a time'],
                ['answer' => '']));

        $this->assertTrue($aitext->is_same_response(
                ['answer' => ''],
                []));

        $this->assertTrue($aitext->is_same_response(
                ['answer' => 'Once upon a time'],
                []));

        $this->assertFalse($aitext->is_same_response(
                ['answer' => 0],
                ['answer' => '']));

        $this->assertFalse($aitext->is_same_response(
                ['answer' => ''],
                ['answer' => 0]));

        $this->assertFalse($aitext->is_same_response(
                ['answer' => '0'],
                ['answer' => '']));

        $this->assertFalse($aitext->is_same_response(
                ['answer' => ''],
                ['answer' => '0']));
    }

}
