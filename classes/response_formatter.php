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
 * Helpers to turn a stored student response into the text sent to the AI.
 *
 * Both consumers share one pipeline: the response is rendered to HTML, walked with a real DOM
 * parser to pull out code (kept verbatim and wrapped in Markdown fences), and the remaining prose
 * is reduced to plain text with core {@see html_to_text()} (no word wrap, no link lists). They
 * differ only in how prose formatting is treated:
 *  - {@see self::to_feedback_text()} leaves the prose formatting to html_to_text as-is. Its light
 *    structure (upper-cased headings/bold, emphasised text, bullet lists) is harmless — arguably
 *    helpful — context for the AI giving feedback.
 *  - {@see self::to_spellcheck_text()} first neutralises, on a whitelist, the tags whose html_to_text
 *    rendering would alter the student's actual characters ({@code <b>}/{@code <strong>} and
 *    {@code <th>} are upper-cased, {@code <i>}/{@code <em>} are wrapped in underscores), so the
 *    spellchecked text stays faithful to what the student wrote.
 *
 * Code is taken straight from the DOM (real spaces, entities decoded) and fenced before html_to_text
 * ever sees it, which also avoids the non-breaking spaces html_to_text injects into {@code <pre>}
 * indentation.
 *
 * @package    qtype_aitext
 * @copyright  2026 ISB Bayern
 * @author     Paola Maneggia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class response_formatter {
    /** @var string A distinctive, html_to_text-safe token used to mark where extracted code goes. */
    private const CODE_PLACEHOLDER = 'QTYPEAITEXTCODEPLACEHOLDER';

    /**
     * Convert a stored student response to the text sent to the AI for grading/feedback.
     *
     * Prose is reduced to plain text by core html_to_text() (keeping its light structural
     * formatting). Code ({@code <pre>}/{@code <code>}, or the {@code <pre><code>} that Markdown and
     * Moodle formats normalise to) is preserved verbatim, indentation intact, wrapped in Markdown
     * code fences so the AI receives it clearly delimited as code rather than instructions.
     *
     * @param string $answer The raw student response as stored in the database.
     * @param int|string $answerformat One of the FORMAT_* constants (as stored with the response).
     * @return string The text to embed in the feedback prompt.
     */
    public static function to_feedback_text(string $answer, $answerformat): string {
        return self::reduce($answer, (int) $answerformat, false);
    }

    /**
     * Convert a stored student response to the plain text used for spellchecking.
     *
     * Like the feedback text, but the tags whose html_to_text rendering would change the student's
     * characters (upper-casing {@code <b>}/{@code <strong>}/{@code <th>}, underscoring
     * {@code <i>}/{@code <em>}) are first neutralised so the spellchecked text stays faithful. Code
     * is still fenced, so both sides of the spellcheck diff treat it identically.
     *
     * @param string $answer The raw student response as stored in the database.
     * @param int|string $answerformat One of the FORMAT_* constants (as stored with the response).
     * @return string The plain text to spellcheck.
     */
    public static function to_spellcheck_text(string $answer, $answerformat): string {
        return self::reduce($answer, (int) $answerformat, true);
    }

    /**
     * Shared reduction for both consumers.
     *
     * @param string $answer The raw student response.
     * @param int $answerformat One of the FORMAT_* constants, already cast to int.
     * @param bool $neutraliseformatting Whether to neutralise character-changing inline tags (spellcheck).
     * @return string The reduced text.
     */
    private static function reduce(string $answer, int $answerformat, bool $neutraliseformatting): string {
        // Plain text has no markup to identify code and may itself be code, so it is returned verbatim.
        if ($answerformat === (int) FORMAT_PLAIN) {
            return trim($answer);
        }

        $dom = self::build_dom(self::to_html($answer, $answerformat));

        // Pull out code first so any (unlikely) formatting tags inside it are not neutralised.
        $fences = self::extract_code($dom);

        // Map sub/superscript markup to a caret/underscore convention so the distinction is not lost.
        self::transform_scripts($dom);

        if ($neutraliseformatting) {
            self::neutralise_inline_formatting($dom);
        }

        $text = html_to_text((string) $dom->saveHTML(), 0, false);

        // Put the verbatim, fenced code back where its placeholder is.
        foreach ($fences as $token => $fenced) {
            $text = str_replace($token, $fenced, $text);
        }

        // Collapse runs of blank lines (keeping paragraph separation) and trim the ends.
        return trim(preg_replace('/\n{3,}/', "\n\n", $text));
    }

    /**
     * Render the response to HTML honouring its format, so a single DOM walk can process it.
     *
     * Filtering is disabled (this text is only reduced for the AI: no multilang, glossary, MathJax,
     * etc. side effects, and no dependency on the ambient page context) and cleaning is left off so
     * nothing is silently dropped. Markdown and Moodle auto-format normalise every code form
     * (fenced, indented, tilde, inline) into {@code <pre><code>} / {@code <code>}, which is exactly
     * what {@see self::extract_code()} keys on.
     *
     * @param string $answer The raw student response.
     * @param int $answerformat One of the FORMAT_* constants, already cast to int.
     * @return string The response rendered as an HTML fragment.
     */
    private static function to_html(string $answer, int $answerformat): string {
        return format_text($answer, $answerformat, ['noclean' => true, 'para' => false, 'filter' => false]);
    }

    /**
     * Parse an HTML fragment into a DOMDocument without an implied {@code <html>}/{@code <body>}
     * wrapper or a DOCTYPE, and drop the XML declaration node so it does not leak into the output.
     *
     * @param string $html The HTML fragment.
     * @return \DOMDocument The parsed document.
     */
    private static function build_dom(string $html): \DOMDocument {
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        // The leading XML declaration forces UTF-8 interpretation of the fragment; the flags keep
        // libxml from injecting an implied <html>/<body> wrapper and a DOCTYPE.
        $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_use_internal_errors($previous);
        libxml_clear_errors();

        // Find and delete the XML_PI_NODE caused by the '<?xml encoding="UTF-8">' declaration.
        foreach (iterator_to_array($dom->childNodes) as $child) {
            if ($child->nodeType === XML_PI_NODE) {
                $dom->removeChild($child);
            }
        }

        return $dom;
    }

    /**
     * Replace every code element with a placeholder text node and return the fenced replacements.
     *
     * {@code <pre>} is treated as a block code fence and {@code <code>} (when not already inside a
     * {@code <pre>}) as an inline code span. Their textual content is taken verbatim from the DOM
     * (real spaces, entities decoded), so code indentation survives and no html_to_text non-breaking
     * spaces are introduced. Each element is swapped for a unique token so html_to_text processes
     * the surrounding prose only; the tokens are substituted back afterwards.
     *
     * @param \DOMDocument $dom The document to mutate.
     * @return array<string, string> Map of placeholder token to fenced code string.
     */
    private static function extract_code(\DOMDocument $dom): array {
        $xpath = new \DOMXPath($dom);
        $fences = [];
        $index = 0;

        // Order matters: process <pre> as blocks, and only those <code> spans not inside a <pre>.
        foreach ($xpath->query('//pre | //code[not(ancestor::pre)]') as $node) {
            $token = self::CODE_PLACEHOLDER . $index . 'END';
            $fences[$token] = strtolower($node->nodeName) === 'pre'
                ? self::fence_block($node->textContent)
                : self::fence_inline($node->textContent);
            $node->parentNode->replaceChild($dom->createTextNode($token), $node);
            $index++;
        }

        return $fences;
    }

    /**
     * Map superscript and subscript markup to a TeX-ish caret/underscore convention so the semantic
     * distinction survives the reduction to plain text (html_to_text would otherwise drop the tags,
     * turning {@code H<sub>2</sub>O} into an ambiguous "H2O").
     *
     * {@code <sup>} becomes {@code ^} and {@code <sub>} becomes {@code _}. A multi-character script is
     * parenthesised ({@code x<sup>10</sup>} => {@code x^(10)}) so the grouping is unambiguous, matching
     * the TeX convention that a bare caret/underscore binds only the next token. Empty scripts are
     * dropped, and only the outermost element is processed (its flattened text content is used).
     *
     * @param \DOMDocument $dom The document to mutate.
     * @return void
     */
    private static function transform_scripts(\DOMDocument $dom): void {
        $xpath = new \DOMXPath($dom);
        $query = '//sup[not(ancestor::sup or ancestor::sub)] | //sub[not(ancestor::sup or ancestor::sub)]';

        foreach (iterator_to_array($xpath->query($query)) as $node) {
            $content = trim($node->textContent);
            if ($content === '') {
                $node->parentNode->removeChild($node);
                continue;
            }
            $prefix = strtolower($node->nodeName) === 'sup' ? '^' : '_';
            $grouped = mb_strlen($content) === 1 ? $content : '(' . $content . ')';
            $node->parentNode->replaceChild($dom->createTextNode($prefix . $grouped), $node);
        }
    }

    /**
     * Neutralise, on a whitelist, the inline tags whose html_to_text rendering would change the
     * student's characters, so the spellchecked text stays faithful to what they wrote.
     *
     * {@code <b>}/{@code <strong>}/{@code <i>}/{@code <em>} are unwrapped (html_to_text would
     * upper-case or underscore their content); {@code <h1>}-{@code <h6>} are downgraded to
     * {@code <p>} (keeping a block break but dropping the upper-casing); {@code <th>} becomes
     * {@code <td>} (dropping the upper-casing while keeping the cell). Everything else is left for
     * html_to_text, since it only adds structure and does not alter the student's words.
     *
     * @param \DOMDocument $dom The document to mutate.
     * @return void
     */
    private static function neutralise_inline_formatting(\DOMDocument $dom): void {
        $xpath = new \DOMXPath($dom);

        foreach (iterator_to_array($xpath->query('//b | //strong | //i | //em')) as $node) {
            while ($node->firstChild) {
                $node->parentNode->insertBefore($node->firstChild, $node);
            }
            $node->parentNode->removeChild($node);
        }

        foreach (iterator_to_array($xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6 | //th')) as $node) {
            $newname = strtolower($node->nodeName) === 'th' ? 'td' : 'p';
            $replacement = $dom->createElement($newname);
            while ($node->firstChild) {
                $replacement->appendChild($node->firstChild);
            }
            $node->parentNode->replaceChild($replacement, $node);
        }
    }

    /**
     * Wrap block code in a Markdown fenced code block on its own lines.
     *
     * The fence length is chosen to be longer than the longest backtick run inside the code
     * (CommonMark rule), so code that itself contains backtick fences cannot terminate the block
     * early.
     *
     * @param string $code The verbatim code content.
     * @return string The fenced code block, surrounded by blank lines.
     */
    private static function fence_block(string $code): string {
        $code = trim($code, "\n");
        // @codingStandardsIgnoreLine moodle.Strings.ForbiddenStrings.Found
        $fence = str_repeat('`', self::fence_length($code, 3));
        return "\n" . $fence . "\n" . $code . "\n" . $fence . "\n";
    }

    /**
     * Wrap inline code in Markdown backticks.
     *
     * The number of backticks is chosen to exceed the longest backtick run inside the code; when
     * the code starts or ends with a backtick, a single space is added inside the delimiters
     * (CommonMark rule).
     *
     * @param string $code The verbatim inline code content.
     * @return string The backtick-delimited inline code.
     */
    private static function fence_inline(string $code): string {
        $code = trim($code, "\n");
        // @codingStandardsIgnoreStart moodle.Strings.ForbiddenStrings.Found
        $ticks = str_repeat('`', self::fence_length($code, 1));
        $pad = ($code === '' || str_starts_with($code, '`') || str_ends_with($code, '`')) ? ' ' : '';
        // @codingStandardsIgnoreEnd
        return $ticks . $pad . $code . $pad . $ticks;
    }

    /**
     * The number of backticks needed to fence the given code: longer than the longest backtick run
     * inside it, and at least $min.
     *
     * @param string $code The code content.
     * @param int $min The minimum fence length (3 for block fences, 1 for inline).
     * @return int The fence length.
     */
    private static function fence_length(string $code, int $min): int {
        $longest = 0;
        // @codingStandardsIgnoreLine moodle.Strings.ForbiddenStrings.Found
        if (preg_match_all('/`+/', $code, $matches)) {
            foreach ($matches[0] as $run) {
                $longest = max($longest, strlen($run));
            }
        }
        return max($min, $longest + 1);
    }
}
