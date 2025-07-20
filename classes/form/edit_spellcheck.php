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

use context;
use context_module;
use core_form\dynamic_form;
use moodle_url;

/**
 * From for editing a card.
 *
 * @package    qtype_aitext
 * @copyright  2024 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_spellcheck extends dynamic_form {

    /** @var context|null Variable to store the context because it is expensive to retrieve. */
    private ?context $context = null;

    /**
     * Define the form
     */
    public function definition() {

        $mform = &$this->_form;

        $mform->addElement('hidden', 'attemptstepid');
        $mform->setType('attemptstepid', PARAM_INT);

        $mform->addElement('hidden', 'answerstepid');
        $mform->setType('answerstepid', PARAM_INT);

        $mform->addElement('static', 'student_answer', get_string('spellcheck_student_anser_desc', 'qtype_aitext'));
        $mform->setType('student_answer', PARAM_RAW);

        $mform->addElement(
            'editor',
            'spellcheck_editor',
            get_string('spellcheck_editor_desc', 'qtype_aitext'),
            null,
            ['maxfiles' => -1]
        );
        $mform->setType('spellcheck_editor', PARAM_RAW);
    }

    /**
     * Returns context where this form is used
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        $attemptstepid = $this->_ajaxformdata['attemptstepid'];
        return $this->get_context_from_attemptstepid($attemptstepid);
    }

    /**
     * Checks if current user has access to this form, otherwise throws exception
     *
     * @throws \moodle_exception User does not have capability to access the form
     */
    protected function check_access_for_dynamic_submission(): void {
        global $USER;
        $context = $this->get_context_for_dynamic_submission();

        if ($context->contextlevel === CONTEXT_USER) {
            // This will happen in preview mode.
            // In preview mode we just check if the user context belongs to the current user.
            if (intval($context->instanceid) !== intval($USER->id)) {
                throw new \moodle_exception('nocapabilitytousethisservice');
            }
            return;
        }
        // We usually end up with a course module context otherwise. Even if not we just check for
        // decent capabilities to edit the result of the AI.
        if (
            !has_capability('mod/quiz:grade', $context) &&
            !has_capability('mod/quiz:regrade', $context)
        ) {
            throw new \moodle_exception('nocapabilitytousethisservice');
        }

    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     *
     * @return object Returns the updated object.
     */
    public function process_dynamic_submission(): object {
        global $DB;
        $formdata = $this->get_data();

        $conditions = [
            'attemptstepid' => $formdata->attemptstepid,
            'name' => '-spellcheckresponse',
        ];

        $record = $DB->get_record('question_attempt_step_data', $conditions, '*', MUST_EXIST);

        $record->value = $formdata->spellcheck_editor['text'];
        $DB->update_record('question_attempt_step_data', $record);

        return $record;
    }

    /**
     * Load in existing data as form defaults
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;

        $conditions = ['attemptstepid' => $this->optional_param('attemptstepid', 0, PARAM_INT), 'name' => '-spellcheckresponse'];
        $spellcheckrecord = $DB->get_record('question_attempt_step_data', $conditions);

        $conditions = ['attemptstepid' => $this->optional_param('answerstepid', 0, PARAM_INT), 'name' => 'answer'];
        $answerrecord = $DB->get_record('question_attempt_step_data', $conditions);

        $draftitemid = file_get_submitted_draft_itemid('attachments');

        $this->set_data((object)[
            'test' => $spellcheckrecord->value,
            'spellcheck_editor' => ['text' => $spellcheckrecord->value, 'format' => FORMAT_HTML, 'itemid' => $draftitemid],
            'attemptstepid' => $this->optional_param('attemptstepid', 0, PARAM_INT),
            'student_answer' => $answerrecord->value,
        ]);
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $params = [
            'cmid' => $this->optional_param('cmid', null, PARAM_INT),
            'attempt' => $this->optional_param('attempt', null, PARAM_INT),
        ];
        return new moodle_url('/mod/quiz/review.php', $params);
    }

    /**
     * Retrieves the context related to the given attemptstepid.
     *
     * First checks if a context has already been retrieved, if not, it retrieves
     * the question usage context from the attempt step and the question attempt
     * and finally creates a new context based on the usage context ID.
     *
     * @param int $attemptstepid The ID of the attempt step.
     * @return context The context object.
     */
    private function get_context_from_attemptstepid(int $attemptstepid) {
        global $DB;
        if (!is_null($this->context)) {
            return $this->context;
        }
        $attemptstep = $DB->get_record('question_attempt_steps', ['id' => $attemptstepid]);
        $attempt = $DB->get_record('question_attempts', ['id' => $attemptstep->questionattemptid]);
        $questionusage = $DB->get_record('question_usages', ['id' => $attempt->questionusageid]);
        $this->context = context::instance_by_id($questionusage->contextid);
        return $this->context;
    }
}
