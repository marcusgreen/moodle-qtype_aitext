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
 * For aitexts with a plain text input box but with a proportional font
 *
 * @copyright  2024 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_aitext_format_plain_renderer extends qtype_aitext_format_renderer_base {
    /**
     * Where the student keys in the response
     *
     * @param string $response
     * @param mixed $lines
     * @param mixed $attributes
     * @return string
     */
    protected function textarea($response, $lines, $attributes) {
        $attributes['class'] = $this->class_name() . ' qtype_aitext_response form-control';
        $attributes['rows'] = $lines;
        $attributes['cols'] = 60;
        return html_writer::tag('textarea', s($response), $attributes);
    }

    /**
     * Specific class name to add to the input element.
     *
     * @return string
     */
    protected function class_name() {
        return 'qtype_aitext_plain';
    }

    /**
     * Text area for response to be keyed in
     *
     * @param string $name
     * @param question_attempt $qa
     * @param question_attempt_step $step
     * @param int $lines
     * @param object $context
     * @return string
     * @throws coding_exception
     */
    public function response_area_input($name, $qa, $step, $lines, $context) {
        $inputname = $qa->get_qt_field_name($name);
        $id = $inputname . '_id';

        $responselabel = $this->displayoptions->add_question_identifier_to_label(get_string('answertext', 'qtype_aitext'));
        $output = html_writer::tag('label', $responselabel, ['class' => 'sr-only', 'for' => $id]);
        $output .= $this->textarea($step->get_qt_var($name), $lines, ['name' => $inputname, 'id' => $id]);
        $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $inputname . 'format', 'value' => FORMAT_PLAIN]);
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

        return format_text($step->get_qt_var($name), $step->get_qt_var($name . 'format'), ['para' => false]);
    }
}
