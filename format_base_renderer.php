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
 * Abstract base renderer for different response formats.
 *
 * @package    qtype_aitext
 * @subpackage aitext
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Abstract out the differences between different type of response format.
 *
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class qtype_aitext_format_renderer_base extends plugin_renderer_base {
    /** @var question_display_options Question display options instance for any necessary information for rendering the question. */
    protected $displayoptions;

    /**
     * Display options setter.
     *
     * @param question_display_options $displayoptions
     */
    public function set_displayoptions(question_display_options $displayoptions): void {
        $this->displayoptions = $displayoptions;
    }

    /**
     * Render the students response when the question is in read-only mode.
     * @param question_attempt $qa the question attempt being display.
     * @param question_attempt_step $step the current step.
     * @param int $lines approximate size of input box to display.
     * @param object $context the context teh output belongs to.
     * @return string html to display the response.
     */
    abstract public function response_area_read_only(
        question_attempt $qa,
        question_attempt_step $step,
        $lines,
        $context
    );

    /**
     * Render the students response when the question is in input mode.
     * @param question_attempt $qa the question attempt being display.
     * @param question_attempt_step $step the current step.
     * @param int $lines approximate size of input box to display.
     * @param object $context the context teh output belongs to.
     * @return string html to display the response for editing.
     */
    abstract public function response_area_input(
        question_attempt $qa,
        question_attempt_step $step,
        $lines,
        $context
    );

    /**
     * Specific class name to add to the input element.
     *
     * @return string
     */
    abstract protected function class_name();
}
