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
use Exception;
use PHPUnit\Framework\ExpectationFailedException;
use question_attempt_step;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/aitext/tests/helper.php');
require_once($CFG->dirroot . '/question/type/aitext/questiontype.php');

use qtype_aitext_test_helper;

/**
 * Unit tests for the aitext question definition class.
 *
 * @package qtype_aitext
 * @copyright 2025 Marcus Green
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \qtype_aitext
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
     *
     * @covers \qtype_aitext\db\upgrade
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
     *
     * @covers ::save_question_options
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
            $this->resetAfterTest(true);
            $aitext = qtype_aitext_test_helper::make_aitext_question([]);
            $aitext->questiontext = 'Hello <img src="http://example.com/globe.png" alt="world" />';
            $this->assertEquals('Hello [world]', $aitext->get_question_summary());
    }

    /**
     * Test the structured prompt template system with various scenarios.
     *
     * This test covers three distinct scenarios:
     * 1. Standard mode - Uses admin template with {{placeholders}}.
     * 2. Expert mode - When aiprompt contains {{response}}, it becomes the template.
     * 3. Language handling - Tests [[language=XX]], [[language=""]], and translatepostfix.
     *
     * @covers \qtype_aitext_question::build_full_ai_prompt
     */
    public function test_build_full_ai_prompt(): void {
        $this->resetAfterTest(true);

        // Setup common test data.
        $question = qtype_aitext_test_helper::make_aitext_question([]);
        $question->questiontext = 'Write a poem about nature';
        $studentresponse = 'The rain in Spain falls mainly on the plain';
        $markscheme = 'One mark for correct grammar';
        $defaultmark = 5;

        // Configure the template system. Note: {{jsonprompt}} and {{defaultmark}} are no longer in the template,
        // they are always appended automatically at the end of the prompt.
        $template = "=== ROLE ===\n{{role}}\n\n=== QUESTION ===\n{{questiontext}}\n\n" .
            "=== GRADING ===\n{{aiprompt}}\n\n=== SCORE ===\n{{markscheme}}\n\n" .
            "=== RESPONSE ===\n{{response}}\n\n=== LANGUAGE ===\n{{language}}";
        set_config('prompttemplate', $template, 'qtype_aitext');
        set_config('roleprompt', 'You are a helpful teacher.', 'qtype_aitext');
        set_config('jsonprompt', 'Return JSON: {"feedback":"...","marks":N}', 'qtype_aitext');

        // Scenario 1: Standard mode with admin template.
        set_config('translatepostfix', true, 'qtype_aitext');
        $aiprompt = 'Check if the poem has good imagery.';
        $result = $question->build_full_ai_prompt($studentresponse, $aiprompt, $defaultmark, $markscheme);

        $this->assertStringContainsString('You are a helpful teacher.', $result);
        $this->assertStringContainsString('Write a poem about nature', $result);
        $this->assertStringContainsString('Check if the poem has good imagery.', $result);
        $this->assertStringContainsString('One mark for correct grammar', $result);
        $this->assertStringContainsString('The rain in Spain', $result);
        $this->assertStringContainsString('Return JSON', $result);
        $this->assertStringContainsString('Maximum score: 5', $result);
        $this->assertStringContainsString('en', $result);

        // Scenario 2: Expert mode via {{response}} placeholder.
        // Note: {{jsonprompt}} and {{defaultmark}} are NOT in the expert prompt, but should still be appended.
        $expertaiprompt = "Du bist ein Deutschlehrer.\n\nFrage: {{questiontext}}\n\n" .
            "Antwort: {{response}}";
        $result = $question->build_full_ai_prompt($studentresponse, $expertaiprompt, $defaultmark, $markscheme);

        $this->assertStringContainsString('Du bist ein Deutschlehrer.', $result);
        $this->assertStringContainsString('Write a poem about nature', $result);
        $this->assertStringContainsString('The rain in Spain', $result);
        $this->assertStringContainsString('Maximum score: 5', $result);
        $this->assertStringContainsString('Return JSON', $result);
        $this->assertStringContainsString('=== OUTPUT FORMAT ===', $result);
        $this->assertStringNotContainsString('You are a helpful teacher.', $result);

        // Scenario 3a: Specific language via [[language=fr]].
        $aiprompt = 'Rate the answer [[language=fr]]';
        $result = $question->build_full_ai_prompt($studentresponse, $aiprompt, $defaultmark, $markscheme);
        $this->assertStringContainsString('fr', $result);
        $this->assertStringNotContainsString('[[language=fr]]', $result);

        // Scenario 3b: Disabled translation via [[language=""]].
        $aiprompt = 'Rate the answer [[language=""]]';
        set_config('translatepostfix', true, 'qtype_aitext');
        $result = $question->build_full_ai_prompt($studentresponse, $aiprompt, $defaultmark, $markscheme);
        $this->assertStringContainsString('the same language as the question', $result);
        $this->assertStringNotContainsString('[[language=""]]', $result);

        // Scenario 3c: translatepostfix disabled.
        set_config('translatepostfix', false, 'qtype_aitext');
        $aiprompt = 'Simple grading instruction';
        $result = $question->build_full_ai_prompt($studentresponse, $aiprompt, $defaultmark, $markscheme);
        $this->assertStringContainsString('the same language as the question', $result);
    }

    /**
     * Test that an empty markscheme is handled correctly with fallback text.
     *
     * @covers \qtype_aitext_question::build_full_ai_prompt
     */
    public function test_build_full_ai_prompt_empty_markscheme(): void {
        $this->resetAfterTest(true);

        $question = qtype_aitext_test_helper::make_aitext_question([]);
        $question->questiontext = 'Test question';

        set_config('prompttemplate', '{{markscheme}}', 'qtype_aitext');
        set_config('translatepostfix', false, 'qtype_aitext');

        $result = $question->build_full_ai_prompt('Student answer', 'Grade this', 10, '');

        // Should contain the fallback text for empty markscheme.
        $this->assertStringContainsString('null', $result);
    }

    /**
     * Verify that all kinds of feedback JSONs are parsed properly.
     *
     * @covers ::process_feedback()
     * @dataProvider process_feedback_provider
     * @param string $json The JSON string generated by the LLM.
     * @param bool $exceptionexpected If there is an exception expected during parsing.
     * @param string $expectedfeedback The expected feedback extracted from the JSON.
     * @param bool $expectedmathjaxapplied if it is expected that mathjax filter is being applied
     * @param float|null $expectedmarks The expected marks extracted from the JSON.
     */
    public function test_process_feedback(
        string $json,
        bool $exceptionexpected,
        string $expectedfeedback,
        bool $expectedmathjaxapplied,
        ?float $expectedmarks
    ): void {
        $this->resetAfterTest();
        set_config('disclaimer', '(example disclaimer)', 'qtype_aitext');
        set_config('translatepostfix', false, 'qtype_aitext');

        $questiontext = 'AI question text';
        $aitext = qtype_aitext_test_helper::make_aitext_question(['questiontext' => $questiontext, 'model' => 'llama3']);

        try {
            $processedfeedback = $aitext->process_feedback($json);
        } catch (Exception $e) {
            if ($exceptionexpected) {
                $this->assertTrue(true);
                return;
            } else {
                $this->fail('Unexpected exception thrown: ' . $e->getMessage());
            }
        }
        $this->assertIsObject($processedfeedback);
        // Empty feedback returns the err_nofeedback system message without disclaimer.
        if (empty($json)) {
            $this->assertEquals(
                get_string('err_nofeedback', 'qtype_aitext'),
                $processedfeedback->feedback
            );
        } else {
            // Normal case: feedback is formatted and disclaimer is appended.
            $expectedfeedback = format_text($expectedfeedback, FORMAT_MARKDOWN);
            $this->assertEquals(
                $expectedfeedback . ' (example disclaimer)',
                $processedfeedback->feedback
            );
            if ($expectedmathjaxapplied) {
                $this->assertStringContainsString('<span class="filter_mathjaxloader_equation">', $processedfeedback->feedback);
            }
        }
        $this->assertEquals($expectedmarks, $processedfeedback->marks);
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
            // If parsing works or not can be seen if the 'expectedfeedback' value is equal to the 'json' value.
            // If that's the case the parsing failed. Otherwise, the feedback and marks could be extracted properly.
            'valid_json' => [
                'json' => '{"feedback": "Good job", "marks": 0}',
                'exceptionexpected' => false,
                'expectedfeedback' => 'Good job',
                'expectedmathjaxapplied' => false,
                'expectedmarks' => 0,
            ],
            'broken_json' => [
                'json' => '{"feedback": "Good job", "marks": 0',
                'exceptionexpected' => false,
                'expectedfeedback' => '{"feedback": "Good job", "marks": 0',
                'expectedmathjaxapplied' => false,
                'expectedmarks' => 0,
            ],
            'valid_json_markdown_formatted' => [
                // @codingStandardsIgnoreLine moodle.Strings.ForbiddenStrings.Found
                'json' => '```json{"feedback": "Good job", "marks": 1}```',
                'exceptionexpected' => false,
                'expectedfeedback' => 'Good job',
                'expectedmathjaxapplied' => false,
                'expectedmarks' => 1,
            ],
            'valid_json_with_text_around' => [
                'json' => 'Here is the feedback: {"feedback": "Well done", "marks": 0.5} Thank you!',
                'exceptionexpected' => false,
                'expectedfeedback' => 'Well done',
                'expectedmathjaxapplied' => false,
                'expectedmarks' => 0.5,
            ],
            'valid_json_with_wrapped_html_tags' => [
                'json' => '<p>{"feedback": "Well done", "marks": 0.5} Thank you!</p>',
                'exceptionexpected' => false,
                'expectedfeedback' => 'Well done',
                'expectedmathjaxapplied' => false,
                'expectedmarks' => 0.5,
            ],
            'valid_json_with_code' => [
                'json' => '{"feedback": "The code has a syntax error: the opening brace '
                    . '\'{\' after the function signature is missing.", "marks": 0.5}',
                'exceptionexpected' => false,
                'expectedfeedback' => 'The code has a syntax error: the opening brace '
                    . '\'{\' after the function signature is missing.',
                'expectedmathjaxapplied' => false,
                'expectedmarks' => 0.5,
            ],
            'not_a_json_at_all' => [
                'json' => 'Not a json string',
                'exceptionexpected' => false,
                'expectedfeedback' => 'Not a json string',
                'expectedmathjaxapplied' => false,
                'expectedmarks' => 0,
            ],
            'empty_json' => [
                'json' => '',
                'exceptionexpected' => false,
                'expectedfeedback' => '',
                'expectedmathjaxapplied' => false,
                'expectedmarks' => null,
            ],
            // The following test cases includes crazy backslash scenarios. The process_feedback function will first "strip" one
            // backslash when parsing the JSON. Then it will substitute each backslash with a double backslash, before sending it
            // through format_text which again will strip one backslash.
            // Overall, the following test cases should represent properly what happens.
            // @codingStandardsIgnoreStart moodle.Strings.ForbiddenStrings.Found
            'json_with_backslashes' => [
                // This JSON is real answer from an LLM with Markdown code block delimiters.
                'json' => '```json { "feedback": "Die Antwort des Schülers zeigt ein grundlegendes Verständnis für das ' .
                    'Röntgenspektrum und die Entstehung des Bremsspektrums sowie des charakteristischen Spektrums. Es wird ' .
                    'korrekt beschrieben, dass das Bremsspektrum durch das Bremsen der Elektronen entsteht und von der ' .
                    'Beschleunigungsspannung abhängt. Auch die materialabhängige Entstehung des charakteristischen Spektrums ' .
                    'wird erwähnt und teilweise richtig interpretiert. Allerdings fehlen wesentliche physikalische Inhalte: 1. ' .
                    'Es wird nicht erklärt, dass die Bremsstrahlung ein kontinuierliches Spektrum darstellt. ' .
                    '2. Der Unterschied zwischen \(K_\alpha\)- und \(K_\beta\)-Linien des Röntgenspektrums wird nicht ' .
                    'angesprochen. 3. Der Zusammenhang zwischen der Kernladungszahl und den Frequenzen \(K_\alpha\)-Linien ' .
                    '(Moseleys Gesetz) ist ungenau erläutert und könnte präziser beschrieben werden. Insgesamt ist die ' .
                    'Antwort gut, aber es sind noch einige physikalische Details notwendig, um die volle Punktzahl zu ' .
                    'erreichen. Gegeben: 7/10 Punkte.", "marks": 7 } ```',
                'exceptionexpected' => false,
                'expectedfeedback' => 'Die Antwort des Schülers zeigt ein grundlegendes Verständnis für das ' .
                    'Röntgenspektrum und die Entstehung des Bremsspektrums sowie des charakteristischen Spektrums. Es wird ' .
                    'korrekt beschrieben, dass das Bremsspektrum durch das Bremsen der Elektronen entsteht und von der ' .
                    'Beschleunigungsspannung abhängt. Auch die materialabhängige Entstehung des charakteristischen Spektrums ' .
                    'wird erwähnt und teilweise richtig interpretiert. Allerdings fehlen wesentliche physikalische Inhalte: 1. ' .
                    'Es wird nicht erklärt, dass die Bremsstrahlung ein kontinuierliches Spektrum darstellt. ' .
                    '2. Der Unterschied zwischen \\\\(K_\\\\alpha\\\\)- und \\\\(K_\\\\beta\\\\)-Linien des Röntgenspektrums wird nicht ' .
                    'angesprochen. 3. Der Zusammenhang zwischen der Kernladungszahl und den Frequenzen \\\\(K_\\\\alpha\\\\)-Linien ' .
                    '(Moseleys Gesetz) ist ungenau erläutert und könnte präziser beschrieben werden. Insgesamt ist die ' .
                    'Antwort gut, aber es sind noch einige physikalische Details notwendig, um die volle Punktzahl zu ' .
                    'erreichen. Gegeben: 7/10 Punkte.',
                'expectedmathjaxapplied' => true,
                'expectedmarks' => 7,
            ],
            [
                'json' => '{"feedback":"Leider nicht richtig. Erwartet war eine Subtraktionsaufgabe der Form ___ - ___. ' .
                    'Deine Eingabe \\(3+2\\) ist eine Addition und stimmt nicht mit \\(5-3\\) oder \\(5-2\\) ' .
                    'überein. Vorschlag: Schreibe z. B. \\(5-3\\) oder \\(5-2\\). Punkte: 0/1.","marks":0}',
                'exceptionexpected' => false,
                'expectedfeedback' => 'Leider nicht richtig. Erwartet war eine Subtraktionsaufgabe der Form ___ - ___. ' .
                    'Deine Eingabe \\\\(3+2\\\\) ist eine Addition und stimmt nicht mit \\\\(5-3\\\\) oder \\\\(5-2\\\\) ' .
                    'überein. Vorschlag: Schreibe z. B. \\\\(5-3\\\\) oder \\\\(5-2\\\\). Punkte: 0/1.',
                'expectedmathjaxapplied' => true,
                'expectedmarks' => 0,
            ],
            [
                'json' => '{"feedback":"Richtig! Deine Eingabe \(3 \cdot 7\) ist gültig (auch \(7 \cdot 3\) ' .
                    'wäre korrekt). Punkte: 1/1.","marks":1}',
                'exceptionexpected' => false,
                'expectedfeedback' => 'Richtig! Deine Eingabe \\\\(3 \cdot 7\\\\) ist gültig (auch \\\\(7 \cdot 3\\\\) ' .
                    'wäre korrekt). Punkte: 1/1.',
                'expectedmathjaxapplied' => true,
                'expectedmarks' => 1,
            ]
            // @codingStandardsIgnoreEnd
        ];
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
            ['answer' => $questiontext, 'answerformat' => FORMAT_HTML]
        ));
    }


    /**
     * Test aitext is_same_response, used when scrolling beween questions
     *
     * @covers ::is_same_response()
     *
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
     *
     * @covers ::is_same_response_with_template()
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
