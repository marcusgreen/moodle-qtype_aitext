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

/**
 * Based on core Moodle qtype_essay originating at the UK Open University
 *
 * @package    qtype_aitext
 * @subpackage aitext
 * @copyright  2024 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Where the student use the HTML editor
 *
 * @author     Marcus Green 2024 building on work by the UK OU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_aitext_format_editor_renderer extends qtype_aitext_format_renderer_base {
    /**
     * Specific class name to add to the input element.
     *
     * @return string
     */
    protected function class_name() {
        return 'qtype_aitext_editor';
    }

    /**
     * Render the response area as read-only.
     *
     * @param question_attempt $qa the question attempt being displayed.
     * @param question_attempt_step $step the current step.
     * @param int $lines the number of lines for the input area.
     * @param object $context the context the attempt belongs to.
     * @return string HTML fragment.
     */
    public function response_area_read_only($qa, $step, $lines, $context) {
        $labelbyid = $qa->get_qt_field_name('answer') . '_label';

        $responselabel = $this->displayoptions->add_question_identifier_to_label(get_string('answertext', 'qtype_aitext'));
        $output = html_writer::tag('h4', $responselabel, ['id' => $labelbyid, 'class' => 'visually-hidden']);
        $output .= html_writer::tag('div', $this->prepare_response($step, $context), [
            'role' => 'textbox',
            'aria-readonly' => 'true',
            'aria-labelledby' => $labelbyid,
            'class' => $this->class_name() . ' qtype_aitext_response readonly',
        ]);

        return $output;
    }

    /**
     * Where the student types in their response.
     *
     * The editor, and the format the response is (re-)saved under, are deliberately pinned to
     * FORMAT_HTML so the answer is stored as HTML regardless of the user's preferred editor. A
     * response previously saved under a different format (e.g. carried in the database from before
     * this pin was introduced) is rendered to HTML with format_text, so the forced HTML editor shows
     * it correctly instead of its raw source; it is then re-saved as HTML. format_text runs with
     * filters and cleaning off so only the format conversion is applied.
     * Note FORMAT_MOODLE === '0', so the (int) comparison (not empty()/falsy checks) is used here.
     *
     * @param question_attempt $qa
     * @param question_attempt_step $step
     * @param int $lines lines available to type in response
     * @param object $context
     * @return string
     * @throws coding_exception
     */
    public function response_area_input($qa, $step, $lines, $context) {
        global $CFG;
        require_once($CFG->dirroot . '/repository/lib.php');

        $inputname = $qa->get_qt_field_name('answer');
        $id = $inputname . '_id';

        // The editor and the re-saved format are pinned to FORMAT_HTML.
        $responseformat = FORMAT_HTML;
        $editor = editors_get_preferred_editor($responseformat);

        $response = $step->get_qt_var('answer') ?? '';
        $storedformat = $step->get_qt_var('answerformat');
        // Interpret the answer using its stored (possibly legacy) format so format_text converts it to HTML,
        // letting the forced HTML editor display it correctly instead of its raw source.
        if ($storedformat !== null && $storedformat !== '' && (int) $storedformat !== FORMAT_HTML && $response !== '') {
            $response = format_text($response, $storedformat, [
                'context' => $context,
                'para' => false,
                'filter' => false,
                'noclean' => true,
            ]);
        }

        $editor->set_text($response);
        $editor->use_editor(
            $id,
            $this->get_editor_options($context),
        );

        $responselabel = $this->displayoptions->add_question_identifier_to_label(get_string('answertext', 'qtype_aitext'));
        $output = html_writer::tag('label', $responselabel, [
            'class' => 'visually-hidden',
            'for' => $id,
        ]);
        $output .= html_writer::start_tag('div', ['class' =>
                $this->class_name() . ' qtype_aitext_response']);
        $output .= html_writer::tag('div', html_writer::tag(
            'textarea',
            s($response),
            ['id' => $id, 'name' => $inputname, 'rows' => $lines, 'cols' => 60, 'class' => 'form-control']
        ));

        $output .= html_writer::start_tag('div');
        $output .= html_writer::empty_tag('input', ['type' => 'hidden',
                'name' => $inputname . 'format', 'value' => $responseformat]);
        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('div');
        return $output;
    }

    /**
     * Prepare the response for read-only display.
     *
     * Render the answer with filters enabled (in the question's
     * own context) so content filters such as LaTeX/MathJax are applied. This is the
     * opposite of the editing path, where the raw source must be preserved unfiltered.
     *
     * @param question_attempt_step $step the current step.
     * @param object $context the context the attempt belongs to.
     * @return string the response prepared for display.
     */
    protected function prepare_response(
        question_attempt_step $step,
        $context
    ) {
        if (!$step->has_qt_var('answer')) {
            return '';
        }
        $formatoptions = new stdClass();
        $formatoptions->para = false;
        $formatoptions->context = $context;
        return format_text(
            $step->get_qt_var('answer'),
            $step->get_qt_var('answerformat') ?? FORMAT_HTML,
            $formatoptions
        );
    }

    /**
     * Fixed options for context and autosave is always false
     *
     * @param object $context the context the attempt belongs to.
     * @return array options for the editor.
     */
    protected function get_editor_options($context) {
        // Disable the text-editor autosave because quiz has it's own auto save function.
        return ['context' => $context, 'autosave' => false];
    }
}
