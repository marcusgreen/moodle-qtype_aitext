# qtype_aitext – Moodle App display/layout review

Based on a screenshot of the quiz review screen in the Moodle App (moodlehq/moodleapp docker image,
browser mode) and the mobile code in `mobile/mobile.js`, `mobile/qtype_aitext.html` and
`mobile/qtype_aitext_app.css`.

Confirmed working: the question renders, the AI grade is applied (Correct, 2.00/2.00) and the AI
feedback appears in the Comment box in review.

## Visible in the screenshot

1. **Empty gap under the answer.** The third `<ion-item>` in `mobile/qtype_aitext.html` (for
   `question.itemcount`) is always rendered, but `.itemcount` is never output by the renderer, so it
   shows as an empty white block between the answer and the comment. Remove it, or add
   `*ngIf="question.itemcount"`.
   **Done (7547ff3):** the item and its dead JS were removed.
2. **Answer box is too narrow.** The read-only response box shrinks to fit the text. Longer answers
   will wrap awkwardly or look cramped. Give `.qtype_aitext_response` `width: 100%`, a `min-height`
   of a few lines, and `white-space: pre-wrap` so line breaks are kept.
   **Done (7547ff3):** the answer is in a full-width div; the response box is `width: 100%` and the
   read-only box has `min-height: 3em`. `pre-wrap` was not added: textareas already keep line
   breaks, and the read-only editor response is formatted HTML.
3. **Mixed font sizes in the comment.** The AI feedback text is smaller than the
   "(Response provided by an AI System…)" line, because they are output as different HTML elements.
   Wrap the attribution in a styled element (e.g. `<span class="aiattribution">`, small and muted)
   on the server side and keep the feedback text at normal size. "Marks: 2/2" could go on its own line.

## Theme and dark mode

4. **Colours are hard-coded in JS.** `mobile/mobile.js:51-57` sets inline `#e7f3f5`, `#b8dce2` and
   `#edf6f7`. In the app's dark mode this gives a pale box with low-contrast text. Move the styling
   into `mobile/qtype_aitext_app.css` using Ionic variables (`--ion-color-step-50`,
   `--ion-color-medium`, `--ion-text-color`) and remove the inline styles.
   **Done (7547ff3):** inline styles removed; the CSS uses Ionic variables with fallbacks.
5. **The CSS file does nothing.** Its selectors (`.qtype-aitext .countwords`, `.minwords`) match
   nothing that is rendered. Replace them with real rules, and bump the styles `'version'` in
   `db/mobile.php` so the app reloads the file.
   **Done (7547ff3):** new selectors, styles version bumped to `0.2`.

## Layout structure

6. **`<ion-item>` wrappers.** They add row padding and divider lines, and can interfere with textarea
   focus and width on small phones. Plain `<div class="ion-padding-horizontal">` blocks are cleaner
   for the question text and the answer.
   *Partly done (7547ff3):* the answer now uses a div; the question text is still in an `<ion-item>`.
7. **Attempt screen.** Make the editable textarea full width, with `rows` taken from
   `responsefieldlines`, so students get a proper writing area instead of the browser default.
   Check it on a narrow phone-size viewport.

## Missing content

8. **No word count in review.** The `<p>` with `get_word_count_message_for_review()` is inside
   `.answer` but is thrown away, because `mobile.js` keeps only the textarea. Keep it.
   **Done (7547ff3):** `mobile.js` now keeps the paragraphs directly inside `.answer`.
9. **No min/max word guidance while answering.** Students using the app can't see the limits. Send
   min/max as data attributes, or as text under the answer box.
10. **Spellcheck diff isn't shown in the app.** It depends on AMD JS. If it matters on mobile, the app
    needs a simple static version, for example the corrected text under the answer.

## Related bug (from the earlier code review)

- `mobile/mobile.js:56` keeps only `textarea.outerHTML`, so the hidden `answerformat` input next to
  it is never submitted from the app. `question.php:697` (`summarise_response`) then reads
  `$response['answerformat']` without a check and gives an "Undefined array key" warning, and
  `renderer.php:164` treats plain-text answers as HTML.

**Done (7547ff3):** `mobile.js` now keeps the hidden inputs and any format menu, and
`summarise_response` falls back to the question's response format. The `renderer.php:164`
fallback to `FORMAT_HTML` is unchanged; see `response-text-conversion.md` for the related
conversion work.

## Suggested first batch

**Done in 7547ff3.** Items 1, 2, 4, 5 and 8, plus the `answerformat` fix. They are all in `mobile.js`,
`qtype_aitext.html` and `qtype_aitext_app.css`, and small. Item 3 is a change to the server-side
feedback HTML, separate from the mobile files.

To check changes: pull-to-refresh in the app (or bump the styles version), then compare the review
and attempt screens again.
