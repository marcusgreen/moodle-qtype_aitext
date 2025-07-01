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
 * Test helpers for the aitext question type.
 *
 * @package    qtype_aitext
 * @copyright  2013 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use Random\RandomException;

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');

/**
 * Test helper class for the aitext question type.
 *
 * @copyright  2013 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_aitext_test_helper extends question_test_helper {
    /**
     * What test questions to import
     * @return array
     */
    public function get_test_questions() {
        return ['editor', 'plain', 'monospaced', 'responsetemplate', 'noinline'];
    }
    /**
     * Make an aitext question for testing
     * @param array $options
     * @return qtype_aitext_question
     */
    public static function make_aitext_question(array $options) {
        question_bank::load_question_definition_classes('aitext');
        $question = new qtype_aitext_question();
        $question->questiontext = $options['questiontext'] ?? '';
        $question->model = $options['model'] ?? '';
        $question->sampleanswer = $options['sampleanswer'] ?? '';
        $question->markscheme = $options['markscheme'] ?? '';
        $question->aiprompt = $options['aiprompt'] ?? '';
        $question->contextid = 1;

        test_question_maker::initialise_a_question($question);
        $question->qtype = question_bank::get_qtype('aitext');
        return $question;
    }
    /**
     * Helper method to reduce duplication.
     * @return qtype_aitext_question
     */
    protected function initialise_aitext_question() {
        question_bank::load_question_definition_classes('aitext');
        $q = new qtype_aitext_question();
        test_question_maker::initialise_a_question($q);
        $q->name = 'aitext question (HTML editor)';
        $q->questiontext = 'Please write a story about a frog.';
        $q->generalfeedback = 'I hope your story had a beginning, a middle and an end.';
        $q->responseformat = 'editor';
        $q->responsefieldlines = 10;
        $q->minwordlimit = null;
        $q->maxwordlimit = null;
        $q->sampleanswer = '';
        $q->model = 'gpt4';
        $q->graderinfo = '';
        $q->graderinfoformat = FORMAT_HTML;
        $q->qtype = question_bank::get_qtype('aitext');

        return $q;
    }

    /**
     * Makes an aitext question using the HTML editor as input.
     * @return qtype_aitext_question
     */
    public function make_aitext_question_editor() {
        return $this->initialise_aitext_question();
    }

    /**
     * Make the data what would be received from the editing form for an aitext
     * question using the HTML editor allowing embedded files as input, and up
     * to three attachments.
     *
     * @return stdClass the data that would be returned by $form->get_gata();
     */
    public function get_aitext_question_form_data_editor() {
        $fromform = new stdClass();

        $fromform->name = 'aitext question (HTML editor)';
        $fromform->questiontext = ['text' => 'Please write a story about a frog.', 'format' => FORMAT_HTML];
        $fromform->defaultmark = 1.0;
        $fromform->generalfeedback = ['text' => 'I hope your story had a beginning, a middle and an end.',
             'format' => FORMAT_HTML];
        $fromform->responseformat = 'editor';
        $fromform->responsefieldlines = 10;
        $fromform->attachments = 0;
        $fromform->graderinfo = ['text' => '', 'format' => FORMAT_HTML];
        $fromform->responsetemplate = ['text' => '', 'format' => FORMAT_HTML];
        $fromform->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;
        $fromform->aiprompt = 'A prompt for the LLM';
        $fromform->markscheme = 'Give one mark if the answer is correct';
        $fromform->sampleanswer = '';
        $fromform->model = 'gpt-4';
        return $fromform;
    }

    /**
     * Makes an aitext question using plain text input.
     * @return qtype_aitext_question
     */
    public function make_aitext_question_plain() {
        $q = $this->initialise_aitext_question();
        $q->responseformat = 'plain';
        return $q;
    }

    /**
     * Make the data what would be received from the editing form for an aitext
     * question using the HTML editor allowing embedded files as input, and up
     * to three attachments.
     *
     * @return stdClass the data that would be returned by $form->get_gata();
     */
    public function get_aitext_question_form_data_plain() {
        $fromform = new stdClass();

        $fromform->name = 'aitext question with filepicker and attachments';
        $fromform->questiontext = ['text' => 'Please write a story about a frog.', 'format' => FORMAT_HTML];
        $fromform->defaultmark = 1.0;
        $fromform->generalfeedback = ['text' => 'I hope your story had a beginning, a middle and an end.',
             'format' => FORMAT_HTML];
        $fromform->responseformat = 'plain';
        $fromform->responsefieldlines = 10;
        $fromform->aiprompt = 'Evaluate this';
        $fromform->markscheme = 'One mark if correct';
        $fromform->maxbytes = 0;
        $fromform->graderinfo = ['text' => '', 'format' => FORMAT_HTML];
        $fromform->responsetemplate = ['text' => '', 'format' => FORMAT_HTML];
        $fromform->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;
        $fromform->sampleanswer = '';
        $fromform->model = 'gpt-4';
        return $fromform;
    }

    /**
     * Makes an aitext question using monospaced input.
     * @return qtype_aitext_question
     */
    public function make_aitext_question_monospaced() {
        $q = $this->initialise_aitext_question();
        $q->responseformat = 'monospaced';
        return $q;
    }

    /**
     * Create a aitext question with a response template for testing
     * @return qtype_aitext_question
     */
    public function make_aitext_question_responsetemplate() {
        $q = $this->initialise_aitext_question();
        $q->responsetemplate = 'Once upon a time';
        $q->responsetemplateformat = FORMAT_HTML;
        return $q;
    }

    /**
     * Makes an aitext question without an online text editor.
     * @return qtype_aitext_question
     */
    public function make_aitext_question_noinline() {
        $q = $this->initialise_aitext_question();
        $q->responseformat = 'noinline';
        return $q;
    }

}
