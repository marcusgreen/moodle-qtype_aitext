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

namespace qtype_aitext\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

use qtype_aitext\local\example_library;

/**
 * Import panel of the example library: destination category and import buttons.
 *
 * The example being previewed is held in the hidden example field, which the page's JavaScript
 * updates when another example is chosen.
 *
 * Custom data: contexts (contexts the user can add questions to), example (selected path or '').
 *
 * @package    qtype_aitext
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class examples_form extends \moodleform {
    /**
     * Define the form.
     */
    protected function definition() {
        $mform = $this->_form;
        $mform->disable_form_change_checker();

        foreach (['cmid', 'courseid'] as $param) {
            $mform->addElement('hidden', $param);
            $mform->setType($param, PARAM_INT);
        }
        $mform->addElement('hidden', 'returnurl');
        $mform->setType('returnurl', PARAM_LOCALURL);
        $mform->addElement('hidden', 'example');
        $mform->setType('example', PARAM_PATH);

        $mform->addElement(
            'questioncategory',
            'category',
            get_string('examplesdestination', 'qtype_aitext'),
            ['contexts' => $this->_customdata['contexts']]
        );

        $attributes = empty($this->_customdata['example']) ? ['disabled' => 'disabled'] : [];
        $buttons = [
            $mform->createElement('submit', 'importone', get_string('examplesimportone', 'qtype_aitext'), $attributes),
            $mform->createElement('submit', 'importsubject', get_string('examplesimportsubject', 'qtype_aitext'), $attributes),
        ];
        $mform->addGroup($buttons, 'buttons', '', ' ', false);
    }

    /**
     * The chosen example must exist.
     *
     * @param array $data
     * @param array $files
     * @return array errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (!example_library::get_example($data['example'] ?? '')) {
            $errors['buttons'] = get_string('examplesnoneselected', 'qtype_aitext');
        }
        return $errors;
    }

    /**
     * Paths to import: the chosen example, or every example in its subject.
     *
     * @param \stdClass $data from get_data()
     * @return string[]
     */
    public static function selected_paths(\stdClass $data): array {
        if (!empty($data->importsubject)) {
            return example_library::get_subject_paths(example_library::get_example($data->example)->subject);
        }
        return [$data->example];
    }
}
