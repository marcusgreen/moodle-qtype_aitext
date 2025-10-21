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

defined('MOODLE_INTERNAL') || die();

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
     * Where the student types in their response
     *
     * @param string $name
     * @param question_attempt $qa
     * @param question_attempt_step $step
     * @param int $lines lines available to type in response
     * @param object $context
     * @return string
     * @throws coding_exception
     */
    public function response_area_input($name, $qa, $step, $lines, $context) {
        global $CFG;
        require_once($CFG->dirroot . '/repository/lib.php');

        $inputname = $qa->get_qt_field_name($name);
        $responseformat = $step->get_qt_var($name . 'format');
        $id = $inputname . '_id';

        $editor = editors_get_preferred_editor($responseformat);
        $strformats = format_text_menu();
        $formats = $editor->get_supported_formats();
        foreach ($formats as $fid) {
            $formats[$fid] = $strformats[$fid];
        }

        [$draftitemid, $response] = $this->prepare_response_for_editing(
            $name,
            $step,
            $context
        );

        $editor->set_text($response);
        $editor->use_editor(
            $id,
            $this->get_editor_options($context),
            $this->get_filepicker_options($context, $draftitemid)
        );

        $responselabel = $this->displayoptions->add_question_identifier_to_label(get_string('answertext', 'qtype_aitext'));
        $output = html_writer::tag('label', $responselabel, [
            'class' => 'sr-only',
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
        if (count($formats) == 1) {
            reset($formats);
            $output .= html_writer::empty_tag('input', ['type' => 'hidden',
                    'name' => $inputname . 'format', 'value' => key($formats)]);
        } else {
            $output .= html_writer::label(get_string('format'), 'menu' . $inputname . 'format', false);
            $output .= ' ';
            $output .= html_writer::select($formats, $inputname . 'format', $responseformat, '');
        }
        $output .= html_writer::end_tag('div');

        $output .= $this->filepicker_html($inputname, $draftitemid);

        $output .= html_writer::end_tag('div');
        return $output;
    }

    /**
     * Prepare the response for read-only display.
     * @param string $name the variable name this input edits.
     * @param question_attempt $qa the question
     *  being display.
     * @param question_attempt_step $step the current step.
     * @param object $context the context the attempt belongs to.
     * @return string the response prepared for display.
     */
    protected function prepare_response(
        $name,
        question_attempt $qa,
        question_attempt_step $step,
        $context
    ) {
        if (!$step->has_qt_var($name)) {
            return '';
        }

        $formatoptions = new stdClass();
        $formatoptions->para = false;
        return format_text(
            $step->get_qt_var($name),
            $step->get_qt_var($name . 'format'),
            $formatoptions
        );
    }

    /**
     * Prepare the response for editing.
     * @param string $name the variable name this input edits.
     * @param question_attempt_step $step the current step.
     * @param object $context the context the attempt belongs to.
     * @return array the response prepared for display.
     */
    protected function prepare_response_for_editing(
        $name,
        question_attempt_step $step,
        $context
    ) {
        return [0, $step->get_qt_var($name)];
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

    /**
     * Redunant with the removal of the file submission option
     *
     * @todo remove calls to this then remove this
     *
     * @param object $context the context the attempt belongs to.
     * @param int $draftitemid draft item id.
     * @return array filepicker options for the editor.
     */
    protected function get_filepicker_options($context, $draftitemid) {
        return ['return_types'  => FILE_INTERNAL | FILE_EXTERNAL];
    }

    /**
     * Redundant with the removal of file submission
     *
     * @todo remove along with calls to it
     *
     * @param string $inputname input field name.
     * @param int $draftitemid draft file area itemid.
     * @return string HTML for the filepicker, if used.
     */
    protected function filepicker_html($inputname, $draftitemid) {
        return '';
    }
}
