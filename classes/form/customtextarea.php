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
 * Custom HTML form element for qtype_aitext.
 *
 * This element allows rendering custom HTML content within a Moodle form,
 * displaying HTML content in a div rather than as escaped text in a textarea.
 *
 * @package    qtype_aitext
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("HTML/QuickForm/static.php");

/**
 * Custom textarea form element that renders HTML content.
 *
 * Extends the textarea form element to display HTML content in a div
 * instead of showing HTML tags as text in a textarea.
 *
 * WARNING: Content passed to this element MUST be properly sanitized
 * before being set as the value to prevent XSS attacks.
 *
 * @package    qtype_aitext
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_aitext_form_customtextarea extends HTML_QuickForm_static {

    /**
     * Returns HTML for the form element.
     *
     * Overrides parent to render content as HTML in a div rather than
     * as escaped text in a textarea.
     *
     * @return string The HTML to display
     */
    public function toHtml() {
        $value = $this->getValue();
        if (empty($value)) {
            $value = '';
        }
        $el = '<div "col-md-9 d-flex flex-wrap align-items-start felement" data-fieldtype="textarea">';
        $el .= html_writer::tag(
            'div',
            $value,
            [
                'id'    => $this->getAttribute('id'),
                'class' => 'form-control',
                'style' => 'min-height:5em; overflow:auto; background-color:#f8f9fa; padding:.375rem .75rem;',
            ]
        );
        $el .= '</div>';
        return $el;

    }




}

// Register the element with Moodle's form system.
MoodleQuickForm::registerElementType(
    'customtextarea',
    __FILE__,
    'qtype_aitext_form_customtextarea'
);