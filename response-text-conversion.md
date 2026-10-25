# qtype_aitext – converting student responses to text for the AI

## Summary

Student responses are passed to the AI through `strip_tags()`. That removes paragraph and list
structure from HTML answers, leaves HTML entities encoded, and can **cut off plain-text answers**
that contain `<` (for example `x<y`). This proposal replaces `strip_tags()` with a format-aware
conversion that keeps line and paragraph breaks and leaves plain text untouched.

## How the answer currently reaches the AI

1. `grade_response()` (`question.php:359`) passes the raw `$response['answer']` to
   `build_full_ai_prompt()`. That raw text is HTML for editor formats and plain text otherwise.
2. `build_template_prompt()` (`question.php:447` and `:458`, standard and expert mode) inserts
   `strip_tags($response)` in place of `{{response}}`.
3. Spellcheck (`question.php:327` → `:525`) sends the **raw** answer, HTML included, with no
   conversion at all.
4. The prompt tester (`classes/external.php:126`) uses the same `build_full_ai_prompt()`.

## What `strip_tags` actually produces

Tested on this site with Moodle's own functions.

Input: `<p>First paragraph.</p><p>Second &amp; one.</p><ul><li>apples</li><li>pears</li></ul>`

`strip_tags` (current):

```
First paragraph.Second &amp; one.applespears
```

`html_to_text($html, 0, false)`:

```
First paragraph.

Second & one.

	* apples
	* pears
```

There are three problems:

- **Structure is lost.** Paragraphs run together and list items become one word (`applespears`).
- **HTML entities stay encoded.** The AI sees `&amp;` rather than `&`.
- **Plain-text answers can be cut short.** `strip_tags("if a<b then")` returns `"if a"`. Anything
  after a `<` with no space after it is treated as the start of a tag and thrown away. This hits
  **plain-format answers** too, so maths or code answers such as `x<y` or `a<=b` lose the rest of
  the text before the AI grades them. This is the most serious of the three.

## Proposed conversion

Add one helper to `qtype_aitext_question` and use it everywhere the answer goes to the AI:

```php
/**
 * Convert a response to plain text for the AI, keeping line and paragraph breaks.
 *
 * @param array $response
 * @return string
 */
protected function get_response_text(array $response): string {
    $text = $response['answer'] ?? '';
    $defaultformat = str_contains($this->responseformat, 'editor') ? FORMAT_HTML : FORMAT_PLAIN;
    $format = (int) ($response['answerformat'] ?? $defaultformat);
    if ($format === FORMAT_PLAIN) {
        // Plain text has no tags to remove; leave "<" intact.
        return trim($text);
    }
    // Width 0 = no hard wrapping; false = drop link URLs.
    return trim(html_to_text($text, 0, false));
}
```

Why it is built this way:

- **Plain format is left alone.** There are no tags to remove, so `<` and `>` reach the AI intact.
  That fixes the cut-off answers.
- **HTML formats use `html_to_text()`.** It is core's html2text: it keeps paragraph breaks, turns
  lists into `* item`, and decodes `&amp;` and similar entities.
- **Width 0 instead of `content_to_text()`.** `content_to_text()` hard-wraps lines at 75
  characters, which would add line breaks the student never typed. Width 0 turns that off.
- **The format fallback matches `summarise_response()`.** When `answerformat` is missing (for
  example from older Moodle App clients), it uses the question's own format: HTML for editor
  formats, plain text for plain and monospaced.

## Where it gets used

1. **`grade_response()`:** pass `$this->get_response_text($response)` to `build_full_ai_prompt()`
   instead of the raw answer.
2. **`build_template_prompt()`:** change `strip_tags($response)` to just `$response` in both modes,
   because the text is already plain. The prompt tester sends plain text from its form, so it is
   unaffected.
3. **`get_spellchecking()`:** pass the converted text, so spellcheck stops receiving HTML tags.
   This also makes it consistent with the renderer's spellcheck diff, which already uses
   `content_to_text` (`renderer.php:166`).

## Optional: question text

`{{questiontext}}` has the same problem with `strip_tags($this->questiontext)`: a teacher's
paragraphs and lists get run together. `html_to_text($this->questiontext, 0, false)` fixes it the
same way. It is teacher content rather than student content, so it could be a separate commit.

## Effects and risks

- **The prompt text changes.** The AI gets better-structured answers, but marks for the same answer
  could differ slightly from before. Mention it in the changelog.
- **Tests:** `tests/question_test.php:189-248` calls `build_full_ai_prompt()` directly. Any test
  that passes HTML and expects it stripped needs updating. New tests to add:
  - an HTML answer keeps its paragraph breaks
  - a plain answer containing `x<y` comes through whole
  - entities are decoded
  - a missing `answerformat` falls back to the question's format
- **Word count** (`count_words`, `question.php:887` and `:924`) is unaffected; it already works on
  the HTML.
- **`summarise_response()`** stays as it is; it uses core's `question_utils::to_plain_text()`.

## Relation to the Moodle App work

This changes AI grading for web users as well as app users, so it is better as its own commit or
branch rather than part of the `fix/mobile-app-layout` layout changes. It also reduces the case for
a rich-text editor in the app: formatting is removed before grading anyway, and what matters is that
line breaks survive, which this conversion keeps.

## Review findings

**Review prompt:** `review response-text-conversion.md`

**Model:** `openai/gpt-6.1-sol` (runtime model ID: `openrouter/openai/gpt-6.1-sol`).

The proposal's central fix is sound: the `strip_tags()` truncation examples and the
paragraph/list conversion were verified using the installed PHP and Moodle converter.
Four points need addressing before implementation.

### 1. [P2] Prompt tester still sends encoded answers

**Proposal reference:** lines 92–94, "Where it gets used".

The claim that the tester is "unaffected" overlooks `classes/external.php:110–114`,
which applies `htmlspecialchars()` to the response before calling
`build_full_ai_prompt()`.

A tester answer of `x<y & z` therefore reaches the AI as `x&lt;y &amp; z`, while
the proposed grading path sends the literal text. Removing `strip_tags()` from
the template builder does not resolve this discrepancy.

**Recommendation:** include the tester's preprocessing in the proposal and add a
test verifying that identical plain-text answers produce identical response
content in tester and grading prompts.

### 2. [P2] Spellcheck conversion will not match the displayed diff

**Proposal reference:** lines 95–97, "Where it gets used".

The renderer uses materially different conversion:

- `renderer.php:164–166` decodes entities **before** parsing HTML and wraps at
  75 characters.
- `renderer.php:183–184` interprets the AI's correction as HTML.

An HTML answer containing literal `x&lt;y` becomes `x<y` with the proposal, but
is truncated to `x` by the renderer's decode-then-convert path. Long paragraphs
also acquire extra line breaks in the diff. These conversion differences were
verified with Moodle's installed converter.

**Recommendation:** extend the plan to align the diff's source conversion with
the AI input and explicitly define how plain-text spellcheck output is handled.
Simply switching the AI input is insufficient.

### 3. [P2] Non-HTML formats are treated as HTML

**Proposal reference:** lines 65–72, proposed `get_response_text()` helper.

Every format except `FORMAT_PLAIN` goes through `html_to_text()`. However, the
question's editor renderer supports Moodle's fallback textarea editor, which
offers `FORMAT_MARKDOWN` and `FORMAT_MOODLE` too.

For Markdown, raw line breaks are collapsed by the HTML converter, and literal
angle-bracket content can still be lost. Core's `content_to_text()` explicitly
converts Markdown to HTML first; the proposed helper omits that step.

**Recommendation:** handle Markdown explicitly before HTML-to-text conversion,
retaining width `0`, and test the supported non-HTML formats.

### 4. [P2] Plain text is trimmed, not left untouched

**Proposal reference:** lines 67–69, proposed `get_response_text()` helper.

`trim($text)` removes leading indentation from the first line of
monospaced/code answers, along with leading and trailing line breaks. Existing
`strip_tags()` preserves those boundary spaces.

**Recommendation:** return `$text` directly for `FORMAT_PLAIN`, consistent with
the stated goal of leaving plain-text answers untouched.

### Review conclusion

This is a worthwhile separate change. Expand its scope to cover tester parity
and spellcheck rendering, and add regression coverage for those paths alongside
the proposed helper tests.

### Edit provenance

- `openai/gpt-6.1-sol`: authored the review findings in this section.
- `opencode/deepseek-v4.1-flash-free`: edited only the model-attribution line
  (line 130); that edit was reverted at the author's request. No other edits by
  this model remain in the file.
