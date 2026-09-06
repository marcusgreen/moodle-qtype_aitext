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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');

/**
 * Rendering tests for the aitext "editor" response format.
 *
 * The editor, and the format the response is saved under, are pinned to FORMAT_HTML (via a
 * hidden field) regardless of the user's editor preference; a response previously saved under
 * another format is rendered to HTML for editing. No student-facing format dropdown is exposed.
 *
 * @package    qtype_aitext
 * @copyright  2026 ISB Bayern
 * @author     Paola Maneggia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \qtype_aitext_format_editor_renderer::response_area_input
 */
final class format_editor_renderer_test extends \qbehaviour_walkthrough_test_base {
    /**
     * A fresh editor response pins the answer format to FORMAT_HTML using a hidden
     * field, and never exposes a student-facing format dropdown.
     */
    public function test_fresh_editor_response_pins_html_and_has_no_dropdown(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        // Required to init a text editor.
        $PAGE->set_url('/');

        $q = \test_question_maker::make_question('aitext', 'editor');
        $this->start_attempt_at_question($q, 'deferredfeedback', 1);

        $prefix = $this->quba->get_field_prefix($this->slot);
        $formatfield = preg_quote($prefix . 'answerformat', '/');

        $this->render();

        // A hidden field pins the format to FORMAT_HTML.
        $this->assertMatchesRegularExpression(
            '/<input[^>]*type="hidden"[^>]*name="' . $formatfield . '"[^>]*value="' . FORMAT_HTML . '"/',
            $this->currentoutput
        );
        // No student-facing format dropdown is rendered.
        $this->assertDoesNotMatchRegularExpression(
            '/<select[^>]*name="' . $formatfield . '"/',
            $this->currentoutput
        );
    }

    /**
     * A response previously saved under a non-HTML format (e.g. carried in the database from before
     * the HTML pin was introduced) is rendered to HTML for editing, and the pinned format stays
     * FORMAT_HTML. Uses FORMAT_MOODLE, whose value '0' also guards the int-comparison of a falsy
     * stored format.
     */
    public function test_existing_non_html_response_is_rendered_to_html(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        // Required to init a text editor.
        $PAGE->set_url('/');

        $q = \test_question_maker::make_question('aitext', 'editor');
        $this->start_attempt_at_question($q, 'deferredfeedback', 1);

        $prefix = $this->quba->get_field_prefix($this->slot);
        $fieldname = $prefix . 'answer';
        $formatfield = preg_quote($prefix . 'answerformat', '/');

        // Save a response stored as FORMAT_MOODLE with a line break, as could pre-date the HTML pin.
        $this->quba->process_all_actions(null, [
            'slots'                    => $this->slot,
            $fieldname                 => "first line\nsecond line",
            $fieldname . 'format'      => FORMAT_MOODLE,
            $prefix . ':sequencecheck' => '1',
        ]);

        $this->render();

        // The pinned format stays FORMAT_HTML, not the stored FORMAT_MOODLE.
        $this->assertMatchesRegularExpression(
            '/<input[^>]*type="hidden"[^>]*name="' . $formatfield . '"[^>]*value="' . FORMAT_HTML . '"/',
            $this->currentoutput
        );
        // The MOODLE content has been rendered to HTML: the newline became a <br> (shown escaped
        // inside the textarea source), which the raw stored text would not contain.
        $this->assertMatchesRegularExpression('/first line&lt;br\s*\/?&gt;/', $this->currentoutput);
    }
}
