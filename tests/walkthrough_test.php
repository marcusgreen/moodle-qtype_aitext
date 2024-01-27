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

use question_bank;
use question_engine;
use question_state;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');

/**
 * Unit tests for the essay question type.
 *
 * @package   qtype_aitext
 * @copyright 2013 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class walkthrough_test extends \qbehaviour_walkthrough_test_base {

    protected function check_contains_textarea($name, $content = '', $height = 10) {
        $fieldname = $this->quba->get_field_prefix($this->slot) . $name;

        $this->assertTag(array('tag' => 'textarea',
                'attributes' => array('cols' => '60', 'rows' => $height,
                        'name' => $fieldname)),
                $this->currentoutput);

        if ($content) {
            $this->assertMatchesRegularExpression('/' . preg_quote(s($content), '/') . '/', $this->currentoutput);
        }
    }

    /**
     * Helper method: Store a test file with a given name and contents in a
     * draft file area.
     *
     * @param int $usercontextid user context id.
     * @param int $draftitemid draft item id.
     * @param string $filename filename.
     * @param string $contents file contents.
     */
    protected function save_file_to_draft_area($usercontextid, $draftitemid, $filename, $contents) {
        $fs = get_file_storage();

        $filerecord = new \stdClass();
        $filerecord->contextid = $usercontextid;
        $filerecord->component = 'user';
        $filerecord->filearea = 'draft';
        $filerecord->itemid = $draftitemid;
        $filerecord->filepath = '/';
        $filerecord->filename = $filename;
        $fs->create_file_from_string($filerecord, $contents);
    }


    public function test_responsetemplate() {
        global $PAGE;

        // The current text editor depends on the users profile setting - so it needs a valid user.
        $this->setAdminUser();
        // Required to init a text editor.
        $PAGE->set_url('/');

        // Create an essay question.
        $q = \test_question_maker::make_question('essay', 'responsetemplate');
        $this->start_attempt_at_question($q, 'deferredfeedback', 1);

        $prefix = $this->quba->get_field_prefix($this->slot);
        $fieldname = $prefix . 'answer';

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->render();
        $this->check_contains_textarea('answer', 'Once upon a time');
        $this->check_current_output(
                $this->get_contains_question_text_expectation($q),
                $this->get_does_not_contain_feedback_expectation());
        $this->check_step_count(1);

        // Save.
        $this->quba->process_all_actions(null, array(
            'slots'                    => $this->slot,
            $fieldname                 => 'Once upon a time there was a little green frog.',
            $fieldname . 'format'      => FORMAT_HTML,
            $prefix . ':sequencecheck' => '1',
        ));

        // Verify.
        $this->check_current_state(question_state::$complete);
        $this->check_current_mark(null);
        $this->check_step_count(2);
        $this->render();
        $this->check_contains_textarea('answer', 'Once upon a time there was a little green frog.');
        $this->check_current_output(
                $this->get_contains_question_text_expectation($q),
                $this->get_does_not_contain_feedback_expectation());
        $this->check_step_count(2);

        // Finish the attempt.
        $this->quba->finish_all_questions();

        // Verify.
        $this->check_current_state(question_state::$needsgrading);
        $this->check_current_mark(null);
        $this->render();
        $this->assertMatchesRegularExpression(
            '/' . preg_quote(s('Once upon a time there was a little green frog.'), '/') . '/', $this->currentoutput);
        $this->check_current_output(
                $this->get_contains_question_text_expectation($q),
                $this->get_contains_general_feedback_expectation($q));
    }

}
