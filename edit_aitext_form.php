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
 * Defines the editing form for the aitext question type.
 *
 * @package    qtype_aitext
 * @subpackage aitext
 * @copyright  2023 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use qtype_aitext\constants;

/**
 * aitext question type editing form.
 *
 * @author  2023 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_aitext_edit_form extends question_edit_form {
    /**
     * Add aitext specific form fields for editing
     *
     * @param object $mform
     * @return void
     */
    protected function definition_inner($mform) {
        global $PAGE;

        /** @var qtype_aitext $qtype */
        $qtype = question_bank::get_qtype('aitext');
        $mform->removeelement('generalfeedback');
        $mform->removeelement('questiontext');
        $mform->addElement('editor', 'questiontext', get_string('questiontext', 'mod_quiz'),
            ['maxlen' => 50, 'rows' => 8, 'size' => 30], $this->editoroptions);

        $mform->addElement('textarea', 'aiprompt', get_string('aiprompt', 'qtype_aitext'),
             ['maxlen' => 50, 'rows' => 5, 'size' => 30]);
        $mform->setType('aiprompt', PARAM_RAW);
        $mform->setDefault('aiprompt', get_config('qtype_aitext', 'defaultprompt'));
        $mform->addHelpButton('aiprompt', 'aiprompt', 'qtype_aitext');
        $mform->addRule('aiprompt', get_string('aipromptmissing', 'qtype_aitext'), 'required');

        $mform->addElement('textarea', 'markscheme', get_string('markscheme', 'qtype_aitext'),
             ['maxlen' => 50, 'rows' => 6, 'size' => 30]);
        $mform->setType('markscheme', PARAM_RAW);
        $mform->setDefault('markscheme', get_config('qtype_aitext', 'defaultmarksscheme'));
        $mform->addHelpButton('markscheme', 'markscheme', 'qtype_aitext');
        $models = explode(",", get_config('tool_aiconnect', 'model'));
        if (count($models) > 1 ) {
            $models = array_combine($models, $models);
            $mform->addElement('select', 'model', get_string('model', 'qtype_aitext'), $models);

        } else {
            $mform->addElement('hidden', 'model', $models[0]);
        }
        $mform->setType('model', PARAM_RAW);
        // The question_edit_form that this class extends expects a general feedback field.
        $mform->addElement('html', '<div class="hidden">');
        $mform->addElement('editor', 'generalfeedback', get_string('generalfeedback', 'question')
        , ['rows' => 10], $this->editoroptions);
        $mform->addElement('html', '</div>');

        $mform->addElement('header', 'prompttester', get_string('prompttester', 'qtype_aitext'));
        $mform->addElement('textarea', 'sampleanswer', get_string('sampleanswer', 'qtype_aitext'),
            ['maxlen' => 50, 'rows' => 6, 'size' => 30]);
        $mform->setType('sampleanswer', PARAM_RAW);
        $mform->setDefault('sampleanswer', '');
        $mform->addHelpButton('sampleanswer', 'sampleanswer', 'qtype_aitext');
        $mform->addElement('static', 'sampleanswereval', '',  '<a class="qtype_aitext_sampleanswerbtn btn btn-secondary"
                id="id_sampleanswerbtn">'
            . get_string('sampleanswerevaluate', 'qtype_aitext') . '</a>' .
             '<div class="qtype_aitext_sampleanswereval" id="id_sampleanswereval"></div>');
        $mform->addElement('header', 'responseoptions', get_string('responseoptions', 'qtype_aitext'));
        $mform->setExpanded('responseoptions');

        $mform->addElement('select', 'responseformat',
                get_string('responseformat', 'qtype_aitext'), constants::get_response_formats());
        $mform->setDefault('responseformat', get_config('qtype_aitext', 'responseformat'));

        $mform->addElement('select', 'responselanguage',
            get_string('responselanguage', 'qtype_aitext'), constants::get_languages());
        $mform->setDefault('responselanguage', get_config('qtype_aitext', 'responselanguage'));

        $mform->addElement('select', 'feedbacklanguage',
            get_string('feedbacklanguage', 'qtype_aitext'), constants::get_languages(true));
        $mform->setDefault('feedbacklanguage', get_config('qtype_aitext', 'feedbacklanguage'));

        $mform->addElement('select', 'responsefieldlines',
                get_string('responsefieldlines', 'qtype_aitext'), $qtype->response_sizes());
        $mform->setDefault('responsefieldlines', $this->get_default_value('responsefieldlines', 10));
        $mform->hideIf('responsefieldlines', 'responseformat', 'eq', 'audio');

        // Create a text box that can be enabled/disabled for max/min word limits options.
        $wordlimitoptions = ['size' => '6', 'maxlength' => '6'];
        $mingrp[] = $mform->createElement('text', 'minwordlimit', '', $wordlimitoptions);
        $mform->setType('minwordlimit', PARAM_INT);
        $mingrp[] = $mform->createElement('checkbox', 'minwordenabled', '', get_string('enable'));
        $mform->setDefault('minwordenabled', 0);
        $mform->addGroup($mingrp, 'mingroup', get_string('minwordlimit', 'qtype_aitext'), ' ', false);
        $mform->addHelpButton('mingroup', 'minwordlimit', 'qtype_aitext');
        $mform->disabledIf('minwordlimit', 'minwordenabled', 'notchecked');
        $mform->hideIf('mingroup', 'responseformat', 'eq', 'audio');

        $maxgrp[] = $mform->createElement('text', 'maxwordlimit', '', $wordlimitoptions);
        $mform->setType('maxwordlimit', PARAM_INT);
        $maxgrp[] = $mform->createElement('checkbox', 'maxwordenabled', '', get_string('enable'));
        $mform->setDefault('maxwordenabled', 0);
        $mform->addGroup($maxgrp, 'maxgroup', get_string('maxwordlimit', 'qtype_aitext'), ' ', false);
        $mform->addHelpButton('maxgroup', 'maxwordlimit', 'qtype_aitext');
        $mform->disabledIf('maxwordlimit', 'maxwordenabled', 'notchecked');
        $mform->hideIf('maxgroup', 'responseformat', 'eq', 'audio');

        // timelimit
        $mform->addElement('select', 'maxtime', get_string('maxtime', 'qtype_aitext'), constants::get_time_limits());
        $mform->setDefault('maxtime',  get_config('qtype_aitext', 'maxtime'));
        $mform->hideIf('maxtime', 'responseformat', 'neq', 'audio');

        // Relevance
        $mform->addElement('header', 'relevanceheader', get_string('relevanceheader', 'qtype_aitext'));
        $mform->addElement('select', 'relevance', get_string('relevance', 'qtype_aitext'), constants::get_relevance_opts());
        $mform->setDefault('relevance',  get_config('qtype_aitext', 'relevance'));
        // Relevance answer
        $mform->addElement('textarea', 'relevanceanswer', get_string('relevanceanswer', 'qtype_aitext'),
            ['maxlen' => 50, 'rows' => 6, 'size' => 30]);
        $mform->hideIf('relevanceanswer', 'relevance', 'neq', constants::RELEVANCE_COMPARISON);

        $mform->addElement('header', 'responsetemplateheader', get_string('responsetemplateheader', 'qtype_aitext'));
        $mform->addElement('editor', 'responsetemplate', get_string('responsetemplate', 'qtype_aitext'),
                ['rows' => 10],  array_merge($this->editoroptions, ['maxfiles' => 0]));
        $mform->addHelpButton('responsetemplate', 'responsetemplate', 'qtype_aitext');

        $mform->addElement('header', 'graderinfoheader', get_string('graderinfoheader', 'qtype_aitext'));
        $mform->setExpanded('graderinfoheader');
        $mform->addElement('editor', 'graderinfo', get_string('graderinfo', 'qtype_aitext'),
                ['rows' => 10], $this->editoroptions);

        // Load any JS that we need to make things happen, specifically the prompt tester.
        $PAGE->requires->js_call_amd('qtype_aitext/editformhelper', 'init', []);
    }

    /**
     * Perform any preprocessing needed on the data passed in
     * before it is used to initialise the form.
     * @param object $question the data being passed to the form.
     * @return object $question the modified data.
     */
    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);

        if (empty($question->options)) {
            return $question;
        }

        foreach (constants::EXTRA_FIELDS as $field) {
            switch ($field) {
                case 'minwordenabled':
                    $question->minwordenabled = $question->options->minwordlimit ? 1 : 0;
                    break;
                case 'maxwordenabled':
                    $question->maxwordenabled = $question->options->maxwordlimit ? 1 : 0;
                    break;
                case 'responsetemplate':
                    $question->responsetemplate = [
                        'text' => $question->options->responsetemplate,
                        'format' => $question->options->responsetemplateformat,
                    ];
                    break;
                default:
                    $question->{$field} = $question->options->{$field};
            }
        }

        return $question;
    }

    /**
     * Check the question text is valid, specifically that
     * any word limits make sense.
     *
     * @param array $fromform
     * @param array $data
     * @return array
     */
    public function validation($fromform, $data) {
        $errors = parent::validation($fromform, $data);

        if (isset($fromform['minwordenabled'])) {
            if (!is_numeric($fromform['minwordlimit'])) {
                $errors['mingroup'] = get_string('err_numeric', 'form');
            }
            if ($fromform['minwordlimit'] < 0) {
                $errors['mingroup'] = get_string('err_minwordlimitnegative', 'qtype_aitext');
            }
            if (!$fromform['minwordlimit']) {
                $errors['mingroup'] = get_string('err_minwordlimit', 'qtype_aitext');
            }
        }
        if (isset($fromform['maxwordenabled'])) {
            if (!is_numeric($fromform['maxwordlimit'])) {
                $errors['maxgroup'] = get_string('err_numeric', 'form');
            }
            if ($fromform['maxwordlimit'] < 0) {
                $errors['maxgroup'] = get_string('err_maxwordlimitnegative', 'qtype_aitext');
            }
            if (!$fromform['maxwordlimit']) {
                $errors['maxgroup'] = get_string('err_maxwordlimit', 'qtype_aitext');
            }
        }
        if (isset($fromform['maxwordenabled']) && isset($fromform['minwordenabled'])) {
            if ($fromform['maxwordlimit'] < $fromform['minwordlimit'] &&
                $fromform['maxwordlimit'] > 0 && $fromform['minwordlimit'] > 0) {
                $errors['maxgroup'] = get_string('err_maxminmismatch', 'qtype_aitext');
            }
        }

        return $errors;
    }

    /**
     * Name of this question type
     * @return string
     */
    public function qtype() {
        return 'aitext';
    }
}
