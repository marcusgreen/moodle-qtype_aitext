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

namespace qtype_aitext\output\form;

/**
 * Class response_test
 *
 * @package    qtype_aitext
 * @copyright  2025 2024 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class response_test extends \moodleform{
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('textarea', 'response', get_string('response', 'qtype_aitext'));
        $mform->addElement('hidden', 'qaid', $this->_customdata['qaid']);
        $mform->setType('qaid', PARAM_INT);
        $mform->addElement('hidden', 'attemptid', $this->_customdata['attemptid']);
        $mform->setType('attemptid', PARAM_INT);
        $mform->addElement('hidden', 'questionid', $this->_customdata['questionid']);
        $mform->setType('questionid', PARAM_INT);
        $mform->addElement('hidden', 'responseid', $this->_customdata['responseid']);
        $mform->setType('responseid', PARAM_INT);
        $mform->addElement('hidden', 'response', $this->_customdata['response']);
        $mform->setType('response', PARAM_RAW);
        $mform->addElement('hidden', 'attemptid', $this
    }
}
