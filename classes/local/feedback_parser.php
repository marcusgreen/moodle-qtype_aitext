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
 * Parses raw LLM feedback into structured output with properly rendered MathJax and Markdown.
 *
 * This class encapsulates the entire feedback processing pipeline:
 *
 * 1. **JSON extraction** — Finds the first valid JSON object in the raw LLM string,
 *    handling markdown code fences, surrounding text, and HTML wrappers.
 * 2. **JSON repair** — If the JSON is malformed due to unescaped LaTeX backslashes,
 *    selectively doubles backslashes inside LaTeX regions and retries parsing.
 * 3. **LaTeX protection** — Doubles backslashes inside LaTeX/MathJax delimiters so
 *    they survive the Markdown parser in format_text().
 * 4. **Markdown rendering** — Converts the feedback to HTML via format_text(FORMAT_MARKDOWN).
 * 5. **Disclaimer appending** — Appends the configured disclaimer text.
 *
 * @package    qtype_aitext
 * @copyright  2026 ISB Bayern
 * @author     Fabian Barbuia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class feedback_parser {
    /**
     * Regex pattern matching LaTeX/MathJax delimiters and their content.
     *
     * Matches: \(...\), \[...\], $$...$$, $...$
     * Handles 1-2 backslashes before parentheses/brackets to cover both properly
     * escaped and raw (broken) LLM output.
     */
    public const LATEX_DELIMITER_PATTERN =
        '/\\\\{1,2}\(.*?\\\\{1,2}\)|\\\\{1,2}\[.*?\\\\{1,2}\]|\$\$.*?\$\$|\$[^$]+\$/s';

    /**
     * Parse raw LLM feedback into a structured result object.
     *
     * This is the main entry point. It runs the full pipeline:
     * extract JSON -> clean feedback -> protect LaTeX -> render Markdown -> append disclaimer.
     *
     * @param string $rawfeedback The raw string returned by the LLM.
     * @param string $disclaimer The disclaimer text to append (may be empty).
     * @return \stdClass Object with 'feedback' (string, HTML) and 'marks' (float|null).
     */
    public function parse(string $rawfeedback, string $disclaimer = ''): \stdClass {
        if (empty($rawfeedback)) {
            $result = new \stdClass();
            $result->feedback = get_string('err_nofeedback', 'qtype_aitext');
            $result->marks = null;
            // Return immediately — no disclaimer, no Markdown, matching original behaviour.
            return $result;
        }

        $result = $this->extract_json($rawfeedback);

        // Only clean internal markers when JSON extraction succeeded (marks is not null).
        // Fallback raw text must not have [[ ]] replaced — it could be legitimate content.
        if ($result->marks !== null) {
            $result->feedback = $this->clean_feedback_text($result->feedback);
        }

        // Detect the content format. If the LLM returned HTML tags, treat it as HTML
        // so that format_text() does not run it through the Markdown parser (which
        // would mangle LaTeX backslashes and HTML structure). Otherwise use Markdown
        // which is the default LLM output format.
        $format = $this->detect_content_format($result->feedback);

        if ($format === FORMAT_MARKDOWN) {
            // Only protect LaTeX backslashes when using Markdown — the Markdown parser
            // consumes single backslashes as escape characters. When using FORMAT_HTML,
            // format_text() passes content through as-is and the MathJax filter handles
            // the LaTeX delimiters directly.
            $result->feedback = $this->protect_latex_backslashes($result->feedback);
        }

        $result->feedback = format_text($result->feedback, $format, ['para' => false]);
        $result->feedback .= ' ' . $disclaimer;

        return $result;
    }

    /**
     * Detect whether the feedback content is HTML or Markdown.
     *
     * If the feedback contains HTML block-level tags (headings, lists, paragraphs, divs,
     * tables, code blocks), it was likely generated as HTML by the LLM (often because the
     * teacher's prompt requested HTML formatting). In that case we use FORMAT_HTML to avoid
     * the Markdown parser mangling the structure and LaTeX backslashes.
     *
     * @param string $text The feedback text to analyse.
     * @return int FORMAT_HTML or FORMAT_MARKDOWN.
     */
    public function detect_content_format(string $text): int {
        // Look for common HTML block-level tags that indicate structured HTML output.
        // We check for tags that a Markdown-only response would never contain.
        if (preg_match('/<(h[1-6]|ul|ol|li|div|table|tr|td|th|pre|blockquote|p|br\s*\/?|code)\b/i', $text)) {
            return FORMAT_HTML;
        }
        return FORMAT_MARKDOWN;
    }

    /**
     * Extract a JSON object from the raw LLM string.
     *
     * Handles:
     * - Valid JSON directly in the string.
     * - JSON wrapped in markdown code fences.
     * - JSON with surrounding text or HTML wrappers.
     * - Malformed JSON caused by unescaped LaTeX backslashes (repaired by selective doubling).
     *
     * Falls back to returning the raw string as feedback with null marks if no JSON is found.
     *
     * @param string $text The raw LLM output string.
     * @return \stdClass Object with 'feedback' (string) and 'marks' (float|null).
     */
    public function extract_json(string $text): \stdClass {
        $jsonstring = $this->find_json_substring($text);

        if ($jsonstring === null) {
            return $this->make_fallback_result($text);
        }

        // Try decoding as-is first. Valid JSON from the LLM should be used directly
        // to avoid corrupting already properly escaped backslashes.
        $decoded = json_decode($jsonstring);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $this->validate_decoded_json($decoded, $text);
        }

        // JSON is malformed — attempt LaTeX-aware repair by doubling backslashes
        // inside LaTeX regions so that sequences like \( K_\alpha \) become valid JSON.
        $repaired = $this->repair_latex_in_json($jsonstring);
        $decoded = json_decode($repaired);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $this->validate_decoded_json($decoded, $text);
        }

        return $this->make_fallback_result($text);
    }

    /**
     * Find the first balanced JSON object substring in the given text.
     *
     * Uses brace-depth counting to extract the first top-level {...} block.
     *
     * @param string $text The text to search for a JSON object.
     * @return string|null The JSON substring, or null if no opening brace is found.
     */
    public function find_json_substring(string $text): ?string {
        $start = strpos($text, '{');
        if ($start === false) {
            return null;
        }

        $depth = 0;
        $json = '';
        for ($i = $start, $len = strlen($text); $i < $len; $i++) {
            if ($text[$i] === '{') {
                $depth++;
            }
            if ($depth > 0) {
                $json .= $text[$i];
            }
            if ($text[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    break;
                }
            }
        }

        return !empty($json) ? $json : null;
    }

    /**
     * Repair malformed JSON by doubling backslashes inside LaTeX regions.
     *
     * LLMs sometimes return JSON with unescaped LaTeX like:
     *   {"feedback": "The formula \( K_\alpha \) is..."}
     *
     * This is invalid JSON because \( and \a are not valid escape sequences.
     * We selectively double backslashes only inside LaTeX delimiters to fix this.
     *
     * @param string $json The malformed JSON string.
     * @return string The repaired JSON string.
     */
    public function repair_latex_in_json(string $json): string {
        return preg_replace_callback(
            self::LATEX_DELIMITER_PATTERN,
            function (array $matches): string {
                if (empty($matches[0])) {
                    return $matches[0] ?? '';
                }
                return str_replace('\\', '\\\\', $matches[0]);
            },
            $json
        );
    }

    /**
     * Clean the extracted feedback text.
     *
     * Removes internal marker syntax ([[...]]) that some prompts use,
     * replacing them with double quotes.
     *
     * @param string $feedback The raw feedback text from JSON extraction.
     * @return string The cleaned feedback text.
     */
    public function clean_feedback_text(string $feedback): string {
        $feedback = trim($feedback);
        return preg_replace(['/\[\[/', '/\]\]/'], '"', $feedback);
    }

    /**
     * Protect backslashes inside LaTeX/MathJax expressions for the Markdown parser.
     *
     * The Markdown parser in format_text() treats backslash as an escape character:
     * a bare `\(` becomes `(`, which destroys MathJax delimiters like `\( ... \)`.
     *
     * This method doubles backslashes **only inside LaTeX regions** so they survive
     * the Markdown pass: `\(` -> `\\(` -> (after Markdown) -> `\(` -> (MathJax picks up).
     *
     * Non-LaTeX content is left untouched so the Markdown parser can handle it naturally.
     *
     * @param string $text The feedback text that may contain LaTeX expressions.
     * @return string The text with backslashes protected inside LaTeX regions only.
     */
    public function protect_latex_backslashes(string $text): string {
        return preg_replace_callback(
            self::LATEX_DELIMITER_PATTERN,
            function (array $matches): string {
                $latex = $matches[0];
                // Normalise: collapse any double-backslashes down to singles first.
                $latex = str_replace('\\\\', '\\', $latex);
                // Double every single backslash so the Markdown parser preserves them.
                $latex = str_replace('\\', '\\\\', $latex);
                return $latex;
            },
            $text
        );
    }

    /**
     * Validate a decoded JSON object has the expected feedback/marks structure.
     *
     * @param \stdClass|null $decoded The decoded JSON object.
     * @param string $rawtext The original raw text (used for fallback).
     * @return \stdClass Object with 'feedback' (string) and 'marks' (float|null).
     */
    private function validate_decoded_json(?\stdClass $decoded, string $rawtext): \stdClass {
        if ($decoded === null || !isset($decoded->feedback)) {
            return $this->make_fallback_result($rawtext);
        }
        return $decoded;
    }

    /**
     * Create a fallback result when JSON parsing fails.
     *
     * @param string $rawtext The raw text to use as feedback.
     * @return \stdClass Object with the raw text as feedback and null marks.
     */
    private function make_fallback_result(string $rawtext): \stdClass {
        $result = new \stdClass();
        $result->feedback = $rawtext;
        $result->marks = null;
        return $result;
    }
}
