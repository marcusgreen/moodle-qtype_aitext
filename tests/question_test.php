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
use Exception;
use PHPUnit\Framework\ExpectationFailedException;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use question_attempt_step;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/aitext/tests/helper.php');
require_once($CFG->dirroot . '/question/type/aitext/questiontype.php');

use qtype_aitext_test_helper;
use qtype_aitext;
use Random\RandomException;
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider};

/**
 * Unit tests for the aitext question definition class.
 *
 * @package qtype_aitext
 * @copyright 2025 Marcus Green
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(qtype_aitext::class)]
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
            set_config('apikey', TEST_LLM_APIKEY, 'aiprovider_openai');
            set_config('orgid', TEST_LLM_ORGID, 'aiprovider_openai');
            set_config('enabled', true, 'aiprovider_openai');
            $this->islive = true;
        }
    }

    /**
     * Test the upgrade process that migrates sample answers from the old table structure to the new one.
     * @return void
     */
    public function test_upgrade(): void {
        $this->resetAfterTest(true);
        global $DB;
        $aitext = ['questionid' => 1, 'sampleanswer' => 'sampleanswer'];
        $DB->insert_record('qtype_aitext', $aitext);

        $sampleanswers = $DB->get_records('qtype_aitext', null, '', 'id,sampleanswer');
        foreach ($sampleanswers as $sampleanswer) {
                $record = ['question' => $sampleanswer->id, 'response' => $sampleanswer->sampleanswer];
                $DB->insert_record('qtype_aitext_sampleresponses', $record);
        }
    }

    /**
     * Ensure grader info is persisted when saving question options.
     */
    public function test_graderinfo_is_saved(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $generator->create_question_category([]);
        $question = $generator->create_question('aitext', 'editor', ['category' => $cat->id]);

        $formdata = \test_question_maker::get_question_form_data('aitext', 'editor');
        $formdata->id = $question->id;
        $formdata->context = \context::instance_by_id($cat->contextid);
        $formdata->graderinfo = [
            'text' => 'Information for graders',
            'format' => FORMAT_HTML,
            'itemid' => 0,
        ];

        $qtype = \question_bank::get_qtype('aitext');
        $qtype->save_question_options($formdata);

        $options = $DB->get_record(
            'qtype_aitext',
            ['questionid' => $question->id],
            'graderinfo, graderinfoformat',
            MUST_EXIST
        );
        $this->assertEquals('Information for graders', $options->graderinfo);
        $this->assertEquals(FORMAT_HTML, $options->graderinfoformat);
    }
    /**
     * Make a trivial request to the LLM to check the code works
     * Only designed to test the 4.5 subsystem when run locally
     * not when in GHA ci
     *
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
     * @return void
     * @throws coding_exception
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function test_get_question_summary(): void {
            $this->resetAfterTest(true);
            $aitext = qtype_aitext_test_helper::make_aitext_question([]);
            $aitext->questiontext = 'Hello <img src="http://example.com/globe.png" alt="world" />';
            $this->assertEquals('Hello [world]', $aitext->get_question_summary());
    }

    /**
     * Check the student response gets interpolated into the prompt ready to send
     * off to the LLM
     */
    public function test_build_full_ai_prompt(): void {
        $this->resetAfterTest(true);

        $question = qtype_aitext_test_helper::make_aitext_question([]);
        $question->questiontext = 'Write a poem';
        $aiprompt = "Does this answer the request to '[[questiontext]]' ";
        $markscheme  = 'One mark if the response is gramatically correct';
        $studentresponse = 'The rain in Spain';
        $defaultmark = 1;

        // Default request to translate feedback to en.
        set_config('translatepostfix', true, 'qtype_aitext');
        $result = (string) $question->build_full_ai_prompt($studentresponse, $aiprompt, $defaultmark, $markscheme);
        $this->assertStringContainsString('translate the feedback to the language en', $result);

        // Set config to not auto translate feedback to en.
        set_config('translatepostfix', false, 'qtype_aitext');
        $result = (string) $question->build_full_ai_prompt($studentresponse, $aiprompt, $defaultmark, $markscheme);
        $this->assertStringNotContainsString('translate the feedback to the language en', $result);

        // The questiontext should have been interpolated into the prompt.
        $this->assertStringContainsString('Write a poem', $result);

        // Request feedback translation on a question by question basis.
        $aiprompt = "Is the text gramatically correct? [[language=jp]]";
        $result = (string) $question->build_full_ai_prompt($studentresponse, $aiprompt, $defaultmark, $markscheme);
        $this->assertStringContainsString('translate the feedback to the language jp', $result);

        // Disable insertion of the translation string.
        $aiprompt = 'Is the text gramatically correct? [[language=""]]';
        $result = (string) $question->build_full_ai_prompt($studentresponse, $aiprompt, $defaultmark, $markscheme);
        $this->assertStringNotContainsString('translate the feedback to the language', $result);

        // Student response is within [ ] delimters. Angle brackets might be better.
        $pattern = '/\[\[' . $studentresponse . '\]\]/';
        $this->assertEquals(1, preg_match($pattern, $result));

        // HTML tags should be stripped out, though that might change in the future.
        $this->assertStringNotContainsString('<p>', $result);

        // Marks scheme should be in result ready to send to LLm. Though it is optional.
        $this->assertStringContainsString($markscheme, $result);

        // Expert mode.
        set_config('expertmode', 1, 'qtype_aitext');
        $aiprompt = '[[expert]] Is the text gramatically correct?';
        $result = (string) $question->build_full_ai_prompt($studentresponse, $aiprompt, $defaultmark, $markscheme);

        // The string [[expert]] is only a flag so it should have been stripped out.
        $this->assertStringNotContainsString('[[expert]]', $result);
    }

    /**
     * Check when [[expert]] is included no "boilerplate" will be added
     * @return void
     */
    public function test_expert_mode(): void {
        global $DB;
        $this->resetAfterTest(true);
        $question = qtype_aitext_test_helper::make_aitext_question(['aiprompt' => '[[expert]] Write asentence in the past tense']);
        $response = ['answer' => 'Yesterday I went to the park', 'answerformat' => 2];
        $question->grade_response($response);
        $result = $DB->get_record('question_attempt_step_data', ['name' => '-aicontent']);
        $this->assertStringContainsString('If the prompt contains[[expert]] or [[response]] it must contain both', $result->value);
    }

    /**
     * Verify that all kinds of feedback JSONs are parsed properly.
     *
     * @param string $json The JSON string generated by the LLM.
     * @param bool $exceptionexpected Whether the called function is expected to throw an exception.
     * @param string $expectedfeedback The expected feedback extracted from the JSON.
     * @param float $expectedmarks The expected marks extracted from the JSON.
     */
    #[DataProvider('process_feedback_provider')]
    public function test_process_feedback(
        string $json,
        bool $exceptionexpected,
        string $expectedfeedback,
        float $expectedmarks
    ): void {
        $this->resetAfterTest();
        set_config('disclaimer', '(example disclaimer)', 'qtype_aitext');
        set_config('translatepostfix', false, 'qtype_aitext');

        $questiontext = 'AI question text';
        $aitext = qtype_aitext_test_helper::make_aitext_question(['questiontext' => $questiontext, 'model' => 'llama3']);

        try {
            $processedfeedback = $aitext->process_feedback($json);
            $this->assertTrue($exceptionexpected);
        } catch (Exception) {
            $this->assertFalse($exceptionexpected);
            return;
        }
        $this->assertIsObject($processedfeedback);
        $this->assertEquals(
            $processedfeedback->feedback,
            format_text($expectedfeedback, FORMAT_MARKDOWN) . ' (example disclaimer)'
        );
        $this->assertEquals($processedfeedback->marks, $expectedmarks);
    }

    /**
     * Data provider for test_process_feedback().
     *
     * Provides various generated JSON strings by an external LLM.
     *
     * @return array of test cases
     */
    public static function process_feedback_provider(): array {
        return [
            'valid_json' => [
                'json' => '{"feedback": "Good job", "marks": 0}',
                'exceptionexpected' => true,
                'expectedfeedback' => 'Good job',
                'expectedmarks' => 0,
            ],
            'broken_json' => [
                'json' => '{"feedback": "Good job", "marks": 0',
                'exceptionexpected' => false,
                'expectedfeedback' => 'Good job',
                'expectedmarks' => 0,
            ],
            'valid_json_markdown_formatted' => [
                // @codingStandardsIgnoreLine moodle.Strings.ForbiddenStrings.Found
                'json' => '```json{"feedback": "Good job", "marks": 1}```',
                'exceptionexpected' => true,
                'expectedfeedback' => 'Good job',
                'expectedmarks' => 1,
            ],
            'valid_json_with_text_around' => [
                'json' => 'Here is the feedback: {"feedback": "Well done", "marks": 0.5} Thank you!',
                'exceptionexpected' => true,
                'expectedfeedback' => 'Well done',
                'expectedmarks' => 0.5,
            ],
            'valid_json_with_wrapped_html_tags' => [
                'json' => '<p>{"feedback": "Well done", "marks": 0.5} Thank you!</p>',
                'exceptionexpected' => true,
                'expectedfeedback' => 'Well done',
                'expectedmarks' => 0.5,
            ],
            'valid_json_with_code' => [
                'json' => '{"feedback": "The code has a syntax error: the opening brace '
                    . '\'{\' after the function signature is missing.", "marks": 0.5}',
                'exceptionexpected' => true,
                'expectedfeedback' => 'The code has a syntax error: the opening brace '
                    . '\'{\' after the function signature is missing.',
                'expectedmarks' => 0.5,
            ],
            'not_a_json_at_all' => [
                'json' => 'Not a json string',
                'exceptionexpected' => true,
                'expectedfeedback' => 'Not a json string',
                'expectedmarks' => 0,
            ],
            'empty_json' => [
                'json' => '',
                'exceptionexpected' => false,
                'expectedfeedback' => '',
                'expectedmarks' => 0,
            ],
        ];
    }

    /**
     * Test summarise_response() when teachers view quiz attempts and then
     * review them to see what has been saved in the response history table.
     */
    public function test_summarise_response(): void {
        $this->resetAfterTest();

        // Create the aitext question under test.
        $questiontext = 'AI question text';
        $aitext = qtype_aitext_test_helper::make_aitext_question(['questiontext' => $questiontext]);
        $aitext->start_attempt(new question_attempt_step(), 1);

        $aitext->responseformat = 'editor';

        $this->assertEquals($questiontext, $aitext->summarise_response(
            ['answer' => $questiontext, 'answerformat' => FORMAT_HTML]
        ));
    }


    /**
     * Test aitext is_same_response, used when scrolling beween questions
     * @return void
     */
    public function test_is_same_response(): void {
        $this->resetAfterTest();

        $aitext = qtype_aitext_test_helper::make_aitext_question([]);

        $aitext->responsetemplate = '';

        $aitext->start_attempt(new question_attempt_step(), 1);

        $this->assertTrue($aitext->is_same_response(
            [],
            ['answer' => '']
        ));

        $this->assertTrue($aitext->is_same_response(
            ['answer' => ''],
            ['answer' => '']
        ));

        $this->assertTrue($aitext->is_same_response(
            ['answer' => ''],
            []
        ));

        $this->assertFalse($aitext->is_same_response(
            ['answer' => 'Hello'],
            []
        ));

        $this->assertFalse($aitext->is_same_response(
            ['answer' => 'Hello'],
            ['answer' => '']
        ));

        $this->assertFalse($aitext->is_same_response(
            ['answer' => 0],
            ['answer' => '']
        ));

        $this->assertFalse($aitext->is_same_response(
            ['answer' => ''],
            ['answer' => 0]
        ));

        $this->assertFalse($aitext->is_same_response(
            ['answer' => '0'],
            ['answer' => '']
        ));

        $this->assertFalse($aitext->is_same_response(
            ['answer' => ''],
            ['answer' => '0']
        ));
    }


    /**
     * Test aitext is_same_response, used when scrolling beween questions
     */
    public function test_is_same_response_with_template(): void {
        $this->resetAfterTest();
        $aitext = qtype_aitext_test_helper::make_aitext_question([]);

        $aitext->responsetemplate = 'Once upon a time';

        $aitext->start_attempt(new question_attempt_step(), 1);

        $this->assertTrue($aitext->is_same_response(
            [],
            ['answer' => 'Once upon a time']
        ));

        $this->assertTrue($aitext->is_same_response(
            ['answer' => ''],
            ['answer' => 'Once upon a time']
        ));

        $this->assertTrue($aitext->is_same_response(
            ['answer' => 'Once upon a time'],
            ['answer' => '']
        ));

        $this->assertTrue($aitext->is_same_response(
            ['answer' => ''],
            []
        ));

        $this->assertTrue($aitext->is_same_response(
            ['answer' => 'Once upon a time'],
            []
        ));

        $this->assertFalse($aitext->is_same_response(
            ['answer' => 0],
            ['answer' => '']
        ));

        $this->assertFalse($aitext->is_same_response(
            ['answer' => ''],
            ['answer' => 0]
        ));

        $this->assertFalse($aitext->is_same_response(
            ['answer' => '0'],
            ['answer' => '']
        ));

        $this->assertFalse($aitext->is_same_response(
            ['answer' => ''],
            ['answer' => '0']
        ));
    }
}
