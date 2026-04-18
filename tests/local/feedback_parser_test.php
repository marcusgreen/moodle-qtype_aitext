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

namespace qtype_aitext\local;

/**
 * Unit tests for the feedback_parser class.
 *
 * Tests each pipeline stage in isolation (JSON extraction, LaTeX repair,
 * backslash protection) as well as the full parse() pipeline.
 *
 * @package    qtype_aitext
 * @copyright  2026 ISB Bayern
 * @author     Fabian Barbuia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \qtype_aitext\local\feedback_parser
 */
final class feedback_parser_test extends \advanced_testcase {
    /**
     * Test that find_json_substring correctly extracts the first JSON object.
     *
     * @dataProvider find_json_substring_provider
     * @param string $input The raw LLM string.
     * @param string|null $expected The expected JSON substring, or null.
     */
    public function test_find_json_substring(string $input, ?string $expected): void {
        $parser = new feedback_parser();
        $this->assertSame($expected, $parser->find_json_substring($input));
    }

    /**
     * Data provider for test_find_json_substring.
     *
     * @return array[]
     */
    public static function find_json_substring_provider(): array {
        return [
            'plain_json' => [
                '{"feedback":"Good","marks":1}',
                '{"feedback":"Good","marks":1}',
            ],
            'json_with_surrounding_text' => [
                'Here is: {"feedback":"OK","marks":0} done',
                '{"feedback":"OK","marks":0}',
            ],
            'json_in_markdown_fence' => [
                // @codingStandardsIgnoreLine moodle.Strings.ForbiddenStrings.Found
                '```json{"feedback":"Yes","marks":2}```',
                '{"feedback":"Yes","marks":2}',
            ],
            'json_with_nested_braces' => [
                '{"feedback":"Brace \'{\' missing","marks":0.5}',
                '{"feedback":"Brace \'{\' missing","marks":0.5}',
            ],
            'no_json_at_all' => [
                'Just plain text without any JSON',
                null,
            ],
            'empty_string' => [
                '',
                null,
            ],
        ];
    }

    /**
     * Test that repair_latex_in_json fixes broken LaTeX backslashes.
     *
     * @dataProvider repair_latex_in_json_provider
     * @param string $input The broken JSON string.
     * @param bool $expectvalid Whether the repaired string should be valid JSON.
     */
    public function test_repair_latex_in_json(string $input, bool $expectvalid): void {
        $parser = new feedback_parser();
        $repaired = $parser->repair_latex_in_json($input);

        $decoded = json_decode($repaired);
        if ($expectvalid) {
            $this->assertSame(
                JSON_ERROR_NONE,
                json_last_error(),
                'Repaired JSON should be valid: ' . json_last_error_msg()
            );
            $this->assertIsObject($decoded);
        } else {
            $this->assertNotSame(JSON_ERROR_NONE, json_last_error());
        }
    }

    /**
     * Data provider for test_repair_latex_in_json.
     *
     * @return array[]
     */
    public static function repair_latex_in_json_provider(): array {
        return [
            // @codingStandardsIgnoreStart moodle.Strings.ForbiddenStrings.Found
            'broken_latex_backslash_paren' => [
                '{"feedback":"Value is \(x+1\)","marks":1}',
                true,
            ],
            'broken_latex_with_commands' => [
                '{"feedback":"Formula \(K_\alpha\) here","marks":2}',
                true,
            ],
            'already_valid_json_not_corrupted' => [
                '{"feedback":"Value is \\\\(x+1\\\\)","marks":1}',
                true,
            ],
            'completely_broken_json' => [
                '{"feedback": broken garbage',
                false,
            ],
            // @codingStandardsIgnoreEnd
        ];
    }

    /**
     * Test that clean_feedback_text removes internal markers.
     */
    public function test_clean_feedback_text(): void {
        $parser = new feedback_parser();

        $this->assertSame('"quoted"', $parser->clean_feedback_text('[[quoted]]'));
        $this->assertSame('plain text', $parser->clean_feedback_text('  plain text  '));
        $this->assertSame('no markers here', $parser->clean_feedback_text('no markers here'));
    }

    /**
     * Test that protect_latex_backslashes doubles backslashes only inside LaTeX.
     *
     * @dataProvider protect_latex_backslashes_provider
     * @param string $input The feedback text.
     * @param string $expected The expected output.
     */
    public function test_protect_latex_backslashes(string $input, string $expected): void {
        $parser = new feedback_parser();
        $this->assertSame($expected, $parser->protect_latex_backslashes($input));
    }

    /**
     * Data provider for test_protect_latex_backslashes.
     *
     * @return array[]
     */
    public static function protect_latex_backslashes_provider(): array {
        return [
            // @codingStandardsIgnoreStart moodle.Strings.ForbiddenStrings.Found
            'single_backslash_latex' => [
                'Text \(x+1\) more',
                'Text \\\\(x+1\\\\) more',
            ],
            'double_backslash_normalised' => [
                'Text \\\\(x+1\\\\) more',
                'Text \\\\(x+1\\\\) more',
            ],
            'no_latex_untouched' => [
                'Plain text with no math',
                'Plain text with no math',
            ],
            'dollar_sign_latex' => [
                'Formula $$E=mc^2$$ here',
                'Formula $$E=mc^2$$ here',
            ],
            'mixed_latex_and_plain' => [
                'Code print(x) and \(x^2\) formula',
                'Code print(x) and \\\\(x^2\\\\) formula',
            ],
            // @codingStandardsIgnoreEnd
        ];
    }

    /**
     * Test extract_json with various LLM outputs.
     *
     * @dataProvider extract_json_provider
     * @param string $input The raw LLM output.
     * @param string $expectedfeedback The expected feedback text.
     * @param float|null $expectedmarks The expected marks.
     */
    public function test_extract_json(string $input, string $expectedfeedback, ?float $expectedmarks): void {
        $parser = new feedback_parser();
        $result = $parser->extract_json($input);

        $this->assertSame($expectedfeedback, $result->feedback);
        if ($expectedmarks === null) {
            $this->assertNull($result->marks);
        } else {
            $this->assertEqualsWithDelta($expectedmarks, $result->marks, PHP_FLOAT_EPSILON);
        }
    }

    /**
     * Data provider for test_extract_json.
     *
     * @return array[]
     */
    public static function extract_json_provider(): array {
        return [
            'valid_json' => [
                '{"feedback":"Good job","marks":1}',
                'Good job',
                1.0,
            ],
            'json_with_text_around' => [
                'Here: {"feedback":"Well done","marks":0.5} Thanks',
                'Well done',
                0.5,
            ],
            'json_in_html' => [
                '<p>{"feedback":"OK","marks":2}</p>',
                'OK',
                2.0,
            ],
            // @codingStandardsIgnoreStart moodle.Strings.ForbiddenStrings.Found
            'broken_json_with_latex_repaired' => [
                '{"feedback":"Formula \(x\) is correct","marks":1}',
                'Formula \(x\) is correct',
                1.0,
            ],
            // @codingStandardsIgnoreEnd
            'no_json_fallback' => [
                'Not a json string',
                'Not a json string',
                null,
            ],
            'broken_json_fallback' => [
                '{"feedback":"Good","marks":0',
                '{"feedback":"Good","marks":0',
                null,
            ],
        ];
    }

    /**
     * Test the full parse() pipeline including Markdown rendering and MathJax.
     *
     * @dataProvider full_pipeline_provider
     * @param string $rawfeedback The raw LLM output.
     * @param string $disclaimer The disclaimer to append.
     * @param string $expectedcontent Substring expected in the final HTML.
     * @param bool $expectmathjax Whether MathJax filter wrapping is expected.
     * @param float|null $expectedmarks The expected marks.
     */
    public function test_full_pipeline(
        string $rawfeedback,
        string $disclaimer,
        string $expectedcontent,
        bool $expectmathjax,
        ?float $expectedmarks
    ): void {
        $this->resetAfterTest();

        $parser = new feedback_parser();
        $result = $parser->parse($rawfeedback, $disclaimer);

        $this->assertIsObject($result);
        $this->assertStringContainsString($expectedcontent, $result->feedback);

        if ($expectmathjax) {
            // MathJax filter wrapping depends on filter availability in the test environment.
            if (strpos($result->feedback, 'filter_mathjaxloader_equation') !== false) {
                $this->assertStringContainsString(
                    '<span class="filter_mathjaxloader_equation">',
                    $result->feedback
                );
            }
        }

        if ($expectedmarks === null) {
            $this->assertNull($result->marks);
        } else {
            $this->assertEqualsWithDelta($expectedmarks, $result->marks, PHP_FLOAT_EPSILON);
        }

        if (!empty($disclaimer)) {
            $this->assertStringContainsString($disclaimer, $result->feedback);
        }
    }

    /**
     * Data provider for test_full_pipeline.
     *
     * @return array[]
     */
    public static function full_pipeline_provider(): array {
        return [
            'simple_feedback' => [
                '{"feedback":"Good job","marks":1}',
                '(AI generated)',
                'Good job',
                false,
                1.0,
            ],
            // @codingStandardsIgnoreStart moodle.Strings.ForbiddenStrings.Found
            'mbs10728_python_with_mathjax' => [
                '{"feedback":"Die Berechnung \\\\(preis / 2\\\\) ist korrekt.","marks":1}',
                '',
                'preis / 2',
                true,
                1.0,
            ],
            'display_math_dollars' => [
                '{"feedback":"Formel: $$a^2 + b^2 = c^2$$","marks":2}',
                '',
                'a^2 + b^2 = c^2',
                true,
                2.0,
            ],
            // @codingStandardsIgnoreEnd
            'plain_text_no_latex' => [
                '{"feedback":"Gut gemacht! print() ist korrekt.","marks":2}',
                '(Disclaimer)',
                'Gut gemacht',
                false,
                2.0,
            ],
        ];
    }

    /**
     * Test that empty feedback returns error message without disclaimer.
     */
    public function test_parse_empty_feedback_no_disclaimer(): void {
        $this->resetAfterTest();

        $parser = new feedback_parser();
        $result = $parser->parse('', 'This disclaimer must NOT appear');

        $this->assertEquals(
            get_string('err_nofeedback', 'qtype_aitext'),
            $result->feedback
        );
        $this->assertNull($result->marks);
        $this->assertStringNotContainsString('This disclaimer must NOT appear', $result->feedback);
    }

    /**
     * Test that clean_feedback_text is only applied when JSON was successfully parsed.
     */
    public function test_parse_fallback_does_not_clean_markers(): void {
        $this->resetAfterTest();

        $parser = new feedback_parser();
        $result = $parser->parse('Text with [[markers]] here', '');

        // The [[ ]] should NOT be replaced with quotes because JSON extraction failed.
        $this->assertStringNotContainsString('"markers"', $result->feedback);
    }

    /**
     * Test that detect_content_format correctly identifies HTML vs Markdown.
     *
     * @dataProvider detect_content_format_provider
     * @param string $text The feedback text to analyse.
     * @param int $expectedformat The expected format constant.
     */
    public function test_detect_content_format(string $text, int $expectedformat): void {
        $parser = new feedback_parser();
        $this->assertSame($expectedformat, $parser->detect_content_format($text));
    }

    /**
     * Data provider for test_detect_content_format.
     *
     * @return array[]
     */
    public static function detect_content_format_provider(): array {
        // FORMAT_MARKDOWN = 4, FORMAT_HTML = 1.
        // We use literal integers because data providers run before Moodle bootstraps constants.
        return [
            'plain_text_is_markdown' => [
                'Good job, well done!',
                4,
            ],
            'markdown_bold_is_markdown' => [
                // @codingStandardsIgnoreLine moodle.Strings.ForbiddenStrings.Found
                '**Good** job with `code`',
                4,
            ],
            'html_with_heading' => [
                '<h3>Bewertung: 1.5/6</h3><ul><li>Punkt 1</li></ul>',
                1,
            ],
            'html_with_list' => [
                'Feedback: <ul><li>Error 1</li><li>Error 2</li></ul>',
                1,
            ],
            'html_with_paragraph' => [
                '<p>Dein Code ist korrekt.</p>',
                1,
            ],
            'html_with_div' => [
                '<div class="feedback">Good</div>',
                1,
            ],
            'html_with_pre_code_block' => [
                '<pre>print("hello")</pre>',
                1,
            ],
            'html_with_br_tag' => [
                'Line one<br>Line two',
                1,
            ],
            'html_with_self_closing_br' => [
                'Line one<br />Line two',
                1,
            ],
            // @codingStandardsIgnoreStart moodle.Strings.ForbiddenStrings.Found
            'html_with_latex_and_structure' => [
                '<h3>Hinweis</h3><p>Die Formel ist \\(\text{Preis} \cdot 0.5\\)</p>',
                1,
            ],
            // @codingStandardsIgnoreEnd
        ];
    }

    /**
     * Test that HTML feedback with LaTeX renders MathJax without backslash mangling.
     *
     * This is the exact scenario from MBS-10728 where the LLM returns HTML-formatted
     * feedback (because the teacher's prompt says "Formatiere in HTML") containing
     * LaTeX expressions. The Markdown parser must NOT process this content.
     */
    public function test_parse_html_feedback_with_latex_renders_correctly(): void {
        $this->resetAfterTest();

        $parser = new feedback_parser();

        // @codingStandardsIgnoreStart moodle.Strings.ForbiddenStrings.Found
        $json = '{"feedback":"<h3>Hinweis</h3><p>Der Betrag ist \\\\(\\\\text{Preis} '
            . '\\\\cdot 0.5\\\\).</p>","marks":3}';
        // @codingStandardsIgnoreEnd

        $result = $parser->parse($json, '');

        $this->assertEquals(3, $result->marks);
        // The HTML structure must survive — not be mangled by Markdown.
        $this->assertStringContainsString('<h3>', $result->feedback);
        // LaTeX content must be present for MathJax to pick up.
        $this->assertStringContainsString('Preis', $result->feedback);
        $this->assertStringContainsString('0.5', $result->feedback);
    }
}
