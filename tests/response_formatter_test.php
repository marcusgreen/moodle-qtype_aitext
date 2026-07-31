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

/**
 * Unit tests for the response_formatter helper.
 *
 * @package    qtype_aitext
 * @copyright  2026 ISB Bayern
 * @author     Paola Maneggia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \qtype_aitext\response_formatter
 */
final class response_formatter_test extends \advanced_testcase {
    /**
     * HTML entities are decoded for both consumers.
     */
    public function test_entities_are_decoded(): void {
        $answer = '<p>This is fine &amp; dandy</p>';
        $this->assertSame('This is fine & dandy', response_formatter::to_feedback_text($answer, FORMAT_HTML));
        $this->assertSame('This is fine & dandy', response_formatter::to_spellcheck_text($answer, FORMAT_HTML));
    }

    /**
     * Line breaks (<br> and <br />) are converted to newlines so words do not run together.
     */
    public function test_html_line_breaks_become_newlines(): void {
        $answer = 'line one<br>line two<br />line three';
        $this->assertSame("line one\nline two\nline three", response_formatter::to_feedback_text($answer, FORMAT_HTML));
        $this->assertSame("line one\nline two\nline three", response_formatter::to_spellcheck_text($answer, FORMAT_HTML));
    }

    /**
     * The textual content of <script> and <style> elements is removed, not just the tags.
     */
    public function test_script_and_style_content_is_removed(): void {
        $answer = '<p>Visible prose</p>'
            . '<style>.foo { color: red; }</style>'
            . '<script>alert("x");</script>';
        $this->assertStringNotContainsString('color: red', response_formatter::to_feedback_text($answer, FORMAT_HTML));
        $this->assertStringNotContainsString('alert', response_formatter::to_spellcheck_text($answer, FORMAT_HTML));
        $this->assertStringContainsString('Visible prose', response_formatter::to_feedback_text($answer, FORMAT_HTML));
    }

    /**
     * A numeric-string answerformat (as stored with the response) is handled.
     */
    public function test_numeric_string_format_is_accepted(): void {
        $answer = '<p>plain prose</p>';
        $this->assertSame('plain prose', response_formatter::to_feedback_text($answer, (string) FORMAT_HTML));
    }

    /**
     * For feedback, headings are rendered by html_to_text (upper-cased) as light structure.
     */
    public function test_feedback_renders_heading_structure(): void {
        $answer = '<h2>My Heading</h2><p>body text</p>';
        $result = response_formatter::to_feedback_text($answer, FORMAT_HTML);

        $this->assertStringContainsString('MY HEADING', $result);
        $this->assertStringContainsString('body text', $result);
    }

    /**
     * For feedback, bold and italic are rendered by html_to_text (upper-cased / underscored).
     */
    public function test_feedback_renders_emphasis(): void {
        $answer = '<p>Some <strong>bold</strong> and <em>italic</em> words</p>';
        $result = response_formatter::to_feedback_text($answer, FORMAT_HTML);

        $this->assertStringContainsString('BOLD', $result);
        $this->assertStringContainsString('_italic_', $result);
    }

    /**
     * For spellcheck, heading text keeps its original casing (not upper-cased).
     */
    public function test_spellcheck_preserves_heading_casing(): void {
        $answer = '<h2>My Heading</h2><p>body text</p>';
        $result = response_formatter::to_spellcheck_text($answer, FORMAT_HTML);

        $this->assertStringContainsString('My Heading', $result);
        $this->assertStringNotContainsString('MY HEADING', $result);
        $this->assertStringContainsString('body text', $result);
    }

    /**
     * For spellcheck, bold and italic keep their original casing and gain no underscores.
     */
    public function test_spellcheck_preserves_emphasis_casing(): void {
        $answer = '<p>Some <strong>Bold</strong> and <em>Italic</em> words</p>';
        $this->assertSame('Some Bold and Italic words', response_formatter::to_spellcheck_text($answer, FORMAT_HTML));
    }

    /**
     * HTML block code is preserved verbatim (indentation intact), fenced as a Markdown code block.
     */
    public function test_block_code_is_fenced(): void {
        $answer = '<p>See below:</p>'
            . "<pre class=\"language-python\"><code>def f():\n    return 1</code></pre>";

        foreach ([
            response_formatter::to_feedback_text($answer, FORMAT_HTML),
            response_formatter::to_spellcheck_text($answer, FORMAT_HTML),
        ] as $result) {
            $this->assertStringContainsString('See below:', $result);
            $this->assertStringContainsString("```\ndef f():\n    return 1\n```", $result);
        }
    }

    /**
     * HTML inline code is preserved and wrapped in single backticks within the prose.
     */
    public function test_inline_code_is_fenced(): void {
        $answer = '<p>use the <code>&lt;br&gt;</code> tag here</p>';
        $this->assertSame('use the `<br>` tag here', response_formatter::to_feedback_text($answer, FORMAT_HTML));
        $this->assertSame('use the `<br>` tag here', response_formatter::to_spellcheck_text($answer, FORMAT_HTML));
    }

    /**
     * Markdown fenced and inline code are preserved (normalised to <pre><code>/<code>, re-fenced).
     */
    public function test_markdown_code_is_fenced(): void {
        $answer = "Some text\n\n```\ncode here\n```\n\nand `inline` too";

        foreach ([
            response_formatter::to_feedback_text($answer, FORMAT_MARKDOWN),
            response_formatter::to_spellcheck_text($answer, FORMAT_MARKDOWN),
        ] as $result) {
            $this->assertStringContainsString('Some text', $result);
            $this->assertStringContainsString('code here', $result);
            $this->assertStringContainsString('`inline`', $result);
            $this->assertStringContainsString('too', $result);
        }
    }

    /**
     * Block code containing a run of backticks is fenced with a longer fence so it cannot terminate
     * the block early (CommonMark rule), keeping the content intact.
     */
    public function test_uses_longer_fence_for_backticks_in_code(): void {
        $answer = "<pre><code>a ``` b</code></pre>";
        $result = response_formatter::to_feedback_text($answer, FORMAT_HTML);

        $this->assertStringContainsString('a ``` b', $result);
        $this->assertMatchesRegularExpression('/````+\na ``` b\n````+/', $result);
    }

    /**
     * An answer consisting solely of a code block is preserved (does not become empty), with the
     * escaped HTML decoded to literal text inside the fence.
     */
    public function test_code_only_answer_is_kept(): void {
        $answer = '<pre><code>&lt;h1&gt;only code&lt;/h1&gt;</code></pre>';
        $result = response_formatter::to_spellcheck_text($answer, FORMAT_HTML);
        $this->assertStringContainsString('<h1>only code</h1>', $result);
    }

    /**
     * Superscripts and subscripts are mapped to a caret/underscore convention for both consumers,
     * with multi-character scripts parenthesised so the grouping is unambiguous.
     */
    public function test_sub_and_superscripts_become_caret_underscore(): void {
        $answer = '<p>H<sub>2</sub>O and x<sup>2</sup>, plus x<sup>10</sup> and C<sub>n+1</sub></p>';
        $expected = 'H_2O and x^2, plus x^(10) and C_(n+1)';

        $this->assertSame($expected, response_formatter::to_feedback_text($answer, FORMAT_HTML));
        $this->assertSame($expected, response_formatter::to_spellcheck_text($answer, FORMAT_HTML));
    }

    /**
     * HTML source typed into a code block (e.g. the question asks the student to write the HTML for
     * "a to the power of 2") is kept verbatim inside the fence, never caret/underscore-transformed.
     * The editor stores such source escaped, so the code path reconstructs it literally.
     */
    public function test_html_source_in_code_block_is_kept_verbatim(): void {
        $answer = '<pre><code>a&lt;sup&gt;2&lt;/sup&gt;</code></pre>';
        $result = response_formatter::to_feedback_text($answer, FORMAT_HTML);

        $this->assertStringContainsString("```\na<sup>2</sup>\n```", $result);
        $this->assertStringNotContainsString('^', $result);
    }

    /**
     * Plain text is returned verbatim for feedback, including angle brackets and indentation.
     */
    public function test_feedback_plain_text_is_verbatim(): void {
        $answer = "if (a<b) {\n    return;\n}";
        $this->assertSame($answer, response_formatter::to_feedback_text($answer, FORMAT_PLAIN));
    }

    /**
     * Plain text is returned verbatim for spellchecking, including a literal angle bracket.
     */
    public function test_spellcheck_plain_text_is_verbatim(): void {
        $answer = 'just plain text with this<that';
        $this->assertSame('just plain text with this<that', response_formatter::to_spellcheck_text($answer, FORMAT_PLAIN));
    }
}
