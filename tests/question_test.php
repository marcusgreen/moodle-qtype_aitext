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
use PHPUnit\Framework\ExpectationFailedException;
use question_attempt_step;
use question_display_options;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/aitext/tests/helper.php');
use qtype_aitext_test_helper;

/**
 * Unit tests for the matching question definition class.
 *
 * @package qtype_aitext
 * @author  Marcus Green 2024
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_test extends \advanced_testcase {


    /**
     * @covers ::get_question_summary()
     * @return void
     * @throws coding_exception
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function test_get_question_summary() {
            $aitext = qtype_aitext_test_helper::make_aitext_question([]);
            $aitext->questiontext = 'Hello <img src="http://example.com/globe.png" alt="world" />';
            $this->assertEquals('Hello [world]', $aitext->get_question_summary());
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
     *
     * @covers ::is_same_response()
     *
     * @return void
     */
    public function test_is_same_response() {
        $aitext = \test_question_maker::make_an_essay_question();

        $aitext->responsetemplate = '';

        $aitext->start_attempt(new question_attempt_step(), 1);

        $this->assertTrue($aitext->is_same_response(
                array(),
                array('answer' => '')));

        $this->assertTrue($aitext->is_same_response(
                array('answer' => ''),
                array('answer' => '')));

        $this->assertTrue($aitext->is_same_response(
                array('answer' => ''),
                array()));

        $this->assertFalse($aitext->is_same_response(
                array('answer' => 'Hello'),
                array()));

        $this->assertFalse($aitext->is_same_response(
                array('answer' => 'Hello'),
                array('answer' => '')));

        $this->assertFalse($aitext->is_same_response(
                array('answer' => 0),
                array('answer' => '')));

        $this->assertFalse($aitext->is_same_response(
                array('answer' => ''),
                array('answer' => 0)));

        $this->assertFalse($aitext->is_same_response(
                array('answer' => '0'),
                array('answer' => '')));

        $this->assertFalse($aitext->is_same_response(
                array('answer' => ''),
                array('answer' => '0')));
    }


    /**
     * @covers ::is_same_response_with_template()
     */
    public function test_is_same_response_with_template() {
        $aitext = \test_question_maker::make_an_essay_question();

        $aitext->responsetemplate = 'Once upon a time';

        $aitext->start_attempt(new question_attempt_step(), 1);

        $this->assertTrue($aitext->is_same_response(
                array(),
                array('answer' => 'Once upon a time')));

        $this->assertTrue($aitext->is_same_response(
                array('answer' => ''),
                array('answer' => 'Once upon a time')));

        $this->assertTrue($aitext->is_same_response(
                array('answer' => 'Once upon a time'),
                array('answer' => '')));

        $this->assertTrue($aitext->is_same_response(
                array('answer' => ''),
                array()));

        $this->assertTrue($aitext->is_same_response(
                array('answer' => 'Once upon a time'),
                array()));

        $this->assertFalse($aitext->is_same_response(
                array('answer' => 0),
                array('answer' => '')));

        $this->assertFalse($aitext->is_same_response(
                array('answer' => ''),
                array('answer' => 0)));

        $this->assertFalse($aitext->is_same_response(
                array('answer' => '0'),
                array('answer' => '')));

        $this->assertFalse($aitext->is_same_response(
                array('answer' => ''),
                array('answer' => '0')));
    }

    /**
     * @covers ::is_complete_response()
     *
     * @return void
     */
    public function test_is_complete_response() {
        $this->resetAfterTest(true);

        // Create sample attachments.
        $attachments = $this->create_user_and_sample_attachments();

        // Create the essay question under test.
        $aitext = \test_question_maker::make_an_essay_question();
        $aitext->start_attempt(new question_attempt_step(), 1);

        // Test the "traditional" case, where we must receive a response from the user.
        $aitext->responserequired = 1;
        $aitext->attachmentsrequired = 0;
        $aitext->responseformat = 'editor';

        // The empty string should be considered an incomplete response, as should a lack of a response.
        $this->assertFalse($aitext->is_complete_response(array('answer' => '')));
        $this->assertFalse($aitext->is_complete_response(array()));

        // Any nonempty string should be considered a complete response.
        $this->assertTrue($aitext->is_complete_response(array('answer' => 'A student response.')));
        $this->assertTrue($aitext->is_complete_response(array('answer' => '0 times.')));
        $this->assertTrue($aitext->is_complete_response(array('answer' => '0')));

        // Test case for minimum and/or maximum word limit.
        $response = [];
        $response['answer'] = 'In this essay, I will be testing a function called check_input_word_count().';

        $aitext->minwordlimit = 50; // The answer is shorter than the required minimum word limit.
        $this->assertFalse($aitext->is_complete_response($response));

        $aitext->minwordlimit = 10; // The  word count  meets the required minimum word limit.
        $this->assertTrue($aitext->is_complete_response($response));

        // The word count meets the required minimum  and maximum word limit.
        $aitext->minwordlimit = 10;
        $aitext->maxwordlimit = 15;
        $this->assertTrue($aitext->is_complete_response($response));

        // Unset the minwordlimit/maxwordlimit variables to avoid the extra check in is_complete_response() for further tests.
        $aitext->minwordlimit = null;
        $aitext->maxwordlimit = null;

        // Test the case where two files are required.
        $aitext->attachmentsrequired = 2;

        // Attaching less than two files should result in an incomplete response.
        $this->assertFalse($aitext->is_complete_response(array('answer' => 'A')));
        $this->assertFalse($aitext->is_complete_response(
                array('answer' => 'A', 'attachments' => $attachments[0])));
        $this->assertFalse($aitext->is_complete_response(
                array('answer' => 'A', 'attachments' => $attachments[1])));

        // Anything without response text should result in an incomplete response.
        $this->assertFalse($aitext->is_complete_response(
                array('answer' => '', 'attachments' => $attachments[2])));

        // Attaching two or more files should result in a complete response.
        $this->assertTrue($aitext->is_complete_response(
                array('answer' => 'A', 'attachments' => $attachments[2])));
        $this->assertTrue($aitext->is_complete_response(
                array('answer' => 'A', 'attachments' => $attachments[3])));

        // Test the case in which two files are required, but the inline
        // response is optional.
        $aitext->responserequired = 0;

        $this->assertFalse($aitext->is_complete_response(
                array('answer' => '', 'attachments' => $attachments[1])));

        $this->assertTrue($aitext->is_complete_response(
                array('answer' => '', 'attachments' => $attachments[2])));

        // Test the case in which both the response and online text are optional.
        $aitext->attachmentsrequired = 0;

        // Providing no answer and no attachment should result in an incomplete
        // response.
        $this->assertFalse($aitext->is_complete_response(
                array('answer' => '')));
        $this->assertFalse($aitext->is_complete_response(
                array('answer' => '', 'attachments' => $attachments[0])));

        // Providing an answer _or_ an attachment should result in a complete
        // response.
        $this->assertTrue($aitext->is_complete_response(
                array('answer' => '', 'attachments' => $attachments[1])));
        $this->assertTrue($aitext->is_complete_response(
                array('answer' => 'Answer text.', 'attachments' => $attachments[0])));

        // Test the case in which we're in "no inline response" mode,
        // in which the response is not required (as it's not provided).
        $aitext->responserequired = 0;
        $aitext->responseformat = 'noinline';
        $aitext->attachmentsrequired = 1;

        $this->assertFalse($aitext->is_complete_response(
                array()));
        $this->assertFalse($aitext->is_complete_response(
                array('attachments' => $attachments[0])));

        // Providing an attachment should result in a complete response.
        $this->assertTrue($aitext->is_complete_response(
                array('attachments' => $attachments[1])));

        // Ensure that responserequired is ignored when we're in inline response mode.
        $aitext->responserequired = 1;
        $this->assertTrue($aitext->is_complete_response(
                array('attachments' => $attachments[1])));
    }

    /**
     * @covers ::get_question_definition_for_external_rendering()
     */
    public function test_get_question_definition_for_external_rendering() {
        $this->resetAfterTest();

        $aitext = qtype_aitext_test_helper::make_aitext_question([]);
        $aitext->minwordlimit = 15;
        $aitext->start_attempt(new question_attempt_step(), 1);
        $qa = \test_question_maker::get_a_qa($aitext);
        $displayoptions = new question_display_options();

        $options = $aitext->get_question_definition_for_external_rendering($qa, $displayoptions);
        $this->assertNotEmpty($options);
        $this->assertEquals('editor', $options['responseformat']);
        $this->assertEquals(1, $options['responserequired']);
        $this->assertEquals(15, $options['responsefieldlines']);
        $this->assertEquals('', $options['responsetemplate']);
        $this->assertEquals(FORMAT_MOODLE, $options['responsetemplateformat']);
        $this->assertEquals($aitext->minwordlimit, $options['minwordlimit']);
        $this->assertNull($options['maxwordlimit']);
    }

    /**
     * Test get_validation_error when users submit their input text.
     *
     * (The tests are done with a fixed 14-word response.)
     *
     * @covers ::get_validation_error()
     *
     * @dataProvider get_min_max_wordlimit_test_cases()
     * @param  int $responserequired whether response required (yes = 1, no = 0)
     * @param  int $minwordlimit minimum word limit
     * @param  int $maxwordlimit maximum word limit
     * @param  string $expected error message | null
     */
    public function test_get_validation_error(int $responserequired,
                                              int $minwordlimit, int $maxwordlimit, string $expected): void {
        $question = \test_question_maker::make_an_essay_question();
        $response = ['answer' => 'One two three four five six seven eight nine ten eleven twelve thirteen fourteen.'];
        $question->responserequired = $responserequired;
        $question->minwordlimit = $minwordlimit;
        $question->maxwordlimit = $maxwordlimit;
        $actual = $question->get_validation_error($response);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Data provider for get_validation_error test.
     *
     * @return array the test cases.
     */
    public function get_min_max_wordlimit_test_cases(): array {
        return [
            'text input required, min/max word limit not set'  => [1, 0, 0, ''],
            'text input required, min/max word limit valid (within the boundaries)'  => [1, 10, 25, ''],
            'text input required, min word limit not reached'  => [1, 15, 25,
                get_string('minwordlimitboundary', 'qtype_aitext', ['count' => 14, 'limit' => 15])],
            'text input required, max word limit is exceeded'  => [1, 5, 12,
                get_string('maxwordlimitboundary', 'qtype_aitext', ['count' => 14, 'limit' => 12])],
            'text input not required, min/max word limit not set'  => [0, 5, 12, ''],
        ];
    }

    /**
     * Test get_word_count_message_for_review when users submit their input text.
     *
     * (The tests are done with a fixed 14-word response.)
     *
     * @covers ::get_word_count()
     *
     * @dataProvider get_word_count_message_for_review_test_cases()
     * @param int|null $minwordlimit minimum word limit
     * @param int|null $maxwordlimit maximum word limit
     * @param string $expected error message | null
     */
    public function test_get_word_count_message_for_review(?int $minwordlimit, ?int $maxwordlimit, string $expected): void {
        $question = \test_question_maker::make_an_essay_question();
        $question->minwordlimit = $minwordlimit;
        $question->maxwordlimit = $maxwordlimit;

        $response = ['answer' => 'One two three four five six seven eight nine ten eleven twelve thirteen fourteen.'];
        $this->assertEquals($expected, $question->get_word_count_message_for_review($response));
    }

    /**
     * Data provider for test_get_word_count_message_for_review.
     *
     * @return array the test cases.
     */
    public function get_word_count_message_for_review_test_cases() {
        return [
            'No limit' =>
                    [null, null, ''],
            'min and max, answer within range' =>
                    [10, 25, get_string('wordcount', 'qtype_aitext', 14)],
            'min and max, answer too short' =>
                    [15, 25, get_string('wordcounttoofew', 'qtype_aitext', ['count' => 14, 'limit' => 15])],
            'min and max, answer too long' =>
                    [5, 12, get_string('wordcounttoomuch', 'qtype_aitext', ['count' => 14, 'limit' => 12])],
            'min only, answer within range' =>
                    [14, null, get_string('wordcount', 'qtype_aitext', 14)],
            'min only, answer too short' =>
                    [15, null, get_string('wordcounttoofew', 'qtype_aitext', ['count' => 14, 'limit' => 15])],
            'max only, answer within range' =>
                    [null, 14, get_string('wordcount', 'qtype_aitext', 14)],
            'max only, answer too short' =>
                    [null, 13, get_string('wordcounttoomuch', 'qtype_aitext', ['count' => 14, 'limit' => 13])],
        ];
    }

    /**
     * Create sample attachemnts and retun generated attachments.
     * @param int $numberofattachments
     * @return array
     */
    private function create_user_and_sample_attachments($numberofattachments = 4) {
        // Create a new logged-in user, so we can test responses with attachments.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Create sample attachments to use in testing.
        $helper = qtype_aitext_test_helper::make_aitext_question([]);
        $attachments = [];
        for ($i = 0; $i < ($numberofattachments + 1); ++$i) {
            $attachments[$i] = $helper->make_attachments_saver($i);
        }
        return $attachments;
    }
}
