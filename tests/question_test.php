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
use dml_exception;
use moodle_exception;
use PHPUnit\Framework\ExpectationFailedException;
use question_attempt_step;
use question_display_options;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/aitext/tests/helper.php');
use qtype_aitext_test_helper;
use Random\RandomException;

/**
 * Unit tests for the matching question definition class.
 *
 * @package qtype_aitext
 * @copyright 2024 Marcus Green
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class question_test extends \advanced_testcase {


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
