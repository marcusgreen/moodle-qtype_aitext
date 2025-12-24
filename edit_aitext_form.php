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
        // Get the question ID from the URL or session.
        $questionid = optional_param('id', 0, PARAM_INT);
        /** @var qtype_aitext $qtype */
        $qtype = question_bank::get_qtype('aitext');
        $mform->removeelement('generalfeedback');
        $mform->removeelement('questiontext');
        $mform->addElement(
            'editor',
            'questiontext',
            get_string('questiontext', 'mod_quiz'),
            ['maxlen' => 50, 'rows' => 8, 'size' => 30],
            $this->editoroptions
        );

        // Spelling correction.
        $mform->addElement('checkbox', 'spellcheck', get_string('automatic_spellcheck', 'qtype_aitext'));

        // Ai prompt.
        $mform->addElement(
            'textarea',
            'aiprompt',
            get_string('aiprompt', 'qtype_aitext'),
            ['maxlen' => 50, 'rows' => 5, 'size' => 30]
        );
        $mform->setType('aiprompt', PARAM_RAW);
        $mform->setDefault('aiprompt', get_config('qtype_aitext', 'defaultprompt'));
        $mform->addHelpButton('aiprompt', 'aiprompt', 'qtype_aitext');
        $mform->addRule('aiprompt', get_string('aipromptmissing', 'qtype_aitext'), 'required');

        // Markscheme.
        $mform->addElement(
            'textarea',
            'markscheme',
            get_string('markscheme', 'qtype_aitext'),
            ['maxlen' => 50, 'rows' => 6, 'size' => 30]
        );
        $mform->setType('markscheme', PARAM_RAW);
        $mform->setDefault('markscheme', get_config('qtype_aitext', 'defaultmarksscheme'));
        $mform->addHelpButton('markscheme', 'markscheme', 'qtype_aitext');
        if (get_config('qtype_aitext', 'markprompt_required') == 1) {
            $mform->addRule('markscheme', get_string('markschememissing', 'qtype_aitext'), 'required');
        }
        $models = explode(",", get_config('tool_aiconnect', 'model'));
        if (count($models) > 1) {
            $models = array_combine($models, $models);
            $mform->addElement('select', 'model', get_string('model', 'qtype_aitext'), $models);
        } else {
            $mform->addElement('hidden', 'model', $models[0]);
        }
        $mform->setType('model', PARAM_RAW);

        // The question_edit_form that this class extends expects a general feedback field.
        $mform->addElement('html', '<div class="hidden">');
        $mform->addElement(
            'editor',
            'generalfeedback',
            get_string('generalfeedback', 'question'),
            ['rows' => 10],
            $this->editoroptions
        );
        $mform->addElement('html', '</div>');

        // Add repeated sample answer options along with the field for returned responses.
        $mform->addElement('header', 'responsetest', get_string('responsetester', 'qtype_aitext'));
        $mform->addHelpButton('responsetest', 'responsetesthelp', 'qtype_aitext');

        $answeroptions = ['maxlen' => 50, 'rows' => 6, 'size' => 30];
        $evaloptions = ['cols' => 50, 'rows' => 5, 'disabled' => 'disabled' ];
        $repeatarray = [
            $mform->createElement('static', 'spinner', '', '<div class =" col-md-9" id="id_spinner"></div>'),
            $mform->createElement('textarea', 'sampleresponses', get_string('sampleresponse', 'qtype_aitext'), $answeroptions),
            // Wrap the evaluation output in the same grid column width as Moodle form items (label + element pattern).
            $mform->createElement(
                'html',
                '<div class="form-group row fitem">'
                    . '<div class="col-md-3 col-form-label d-flex pb-0 pt-0">'
                        . '<label>' . get_string('sampleresponseeval', 'qtype_aitext') . '</label>'
                    . '</div>'
                    . '<div class="col-md-9 form-inline felement">'
                        . '<div id="id_sampleresponseeval" class="form-control w-100" style="min-height:5em; overflow:auto;"></div>'
                    . '</div>'
                . '</div>'
            ),
            // Keep button name/id consistent with JS selectors (sampleresponsebtn_*).
            $mform->createElement('button', 'sampleresponsebtn', get_string('sampleresponseevaluate', 'qtype_aitext')),
            $mform->createElement('submit', 'delete', get_string('deletesample', 'qtype_aitext'), 0),
            $mform->createElement('html', '<hr/>'),
        ];

        $repeateloptions = [];
        $mform->setType('sampleresponses', PARAM_CLEANHTML);
        // Sampleresponseeval is static HTML; no input expected from user.

        $mform->setType('optionid', PARAM_INT);
        $samplecount = $this->get_sample_count();
        $mform->registerNoSubmitButton('delete');
        $this->repeat_elements(
            $repeatarray,
            $samplecount,
            $repeateloptions,
            'option_repeats',
            'option_add_fields',
            1,
            get_string('addsample', 'qtype_aitext'),
            true,
            'delete',
        );

        $mform->setType('option', PARAM_CLEANHTML);

        $mform->addElement('header', 'responseoptions', get_string('responseoptions', 'qtype_aitext'));
        $mform->setExpanded('responseoptions');

        $mform->addElement(
            'select',
            'responseformat',
            get_string('responseformat', 'qtype_aitext'),
            $qtype->response_formats()
        );
        $mform->setDefault('responseformat', get_config('qtype_aitext', 'responseformat'));

        $mform->addElement(
            'select',
            'responsefieldlines',
            get_string('responsefieldlines', 'qtype_aitext'),
            $qtype->response_sizes()
        );
        $mform->setDefault('responsefieldlines', $this->get_default_value('responsefieldlines', 10));
        $mform->hideIf('responsefieldlines', 'responseformat', 'eq', 'noinline');

        // Create a text box that can be enabled/disabled for max/min word limits options.
        $wordlimitoptions = ['size' => '6', 'maxlength' => '6'];
        $mingrp[] = $mform->createElement('text', 'minwordlimit', '', $wordlimitoptions);
        $mform->setType('minwordlimit', PARAM_INT);
        $mingrp[] = $mform->createElement('checkbox', 'minwordenabled', '', get_string('enable'));
        $mform->setDefault('minwordenabled', 0);
        $mform->addGroup($mingrp, 'mingroup', get_string('minwordlimit', 'qtype_aitext'), ' ', false);
        $mform->addHelpButton('mingroup', 'minwordlimit', 'qtype_aitext');
        $mform->disabledIf('minwordlimit', 'minwordenabled', 'notchecked');
        $mform->hideIf('mingroup', 'responseformat', 'eq', 'noinline');

        $maxgrp[] = $mform->createElement('text', 'maxwordlimit', '', $wordlimitoptions);
        $mform->setType('maxwordlimit', PARAM_INT);
        $maxgrp[] = $mform->createElement('checkbox', 'maxwordenabled', '', get_string('enable'));
        $mform->setDefault('maxwordenabled', 0);
        $mform->addGroup($maxgrp, 'maxgroup', get_string('maxwordlimit', 'qtype_aitext'), ' ', false);
        $mform->addHelpButton('maxgroup', 'maxwordlimit', 'qtype_aitext');
        $mform->disabledIf('maxwordlimit', 'maxwordenabled', 'notchecked');
        $mform->hideIf('maxgroup', 'responseformat', 'eq', 'noinline');

        $mform->addElement('header', 'responsetemplateheader', get_string('responsetemplateheader', 'qtype_aitext'));
        $mform->addElement(
            'editor',
            'responsetemplate',
            get_string('responsetemplate', 'qtype_aitext'),
            ['rows' => 10],
            array_merge($this->editoroptions, ['maxfiles' => 0])
        );
        $mform->addHelpButton('responsetemplate', 'responsetemplate', 'qtype_aitext');

        $mform->addElement('header', 'graderinfoheader', get_string('graderinfoheader', 'qtype_aitext'));
        $mform->setExpanded('graderinfoheader');
        $mform->addElement(
            'editor',
            'graderinfo',
            get_string('graderinfo', 'qtype_aitext'),
            ['rows' => 10],
            $this->editoroptions
        );

        // Load any JS that we need to make things happen, specifically the prompt tester.
        $PAGE->requires->js_call_amd('qtype_aitext/responserun', 'init', [$this->context->id]);
    }

    /**
     * retrieved from function get_sampleresponses in class quesitontype
     *
     * @return int count of sample answers
     */
    protected function get_sample_count() {
        if (isset($this->question->id)) {
            if (isset($this->question->options->sampleresponses)) {
                if (count($this->question->options->sampleresponses) > 0) {
                    return count($this->question->options->sampleresponses);
                }
            }
        }
        // Always have one empty sample answer set of fields.
        return 1;
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

        $question->responseformat = $question->options->responseformat;
        $question->responsefieldlines = $question->options->responsefieldlines;
        $question->minwordenabled = $question->options->minwordlimit ? 1 : 0;
        $question->minwordlimit = $question->options->minwordlimit;
        $question->maxwordenabled = $question->options->maxwordlimit ? 1 : 0;
        $question->maxwordlimit = $question->options->maxwordlimit;
        $question->aiprompt = $question->options->aiprompt;
        $question->spellcheck = $question->options->spellcheck;
        // Make the count start from 0 like the repeat array elements.
        $question->sampleresponses = [];
        foreach ($question->options->sampleresponses as $sampleresponse) {
            $question->sampleresponses[] = $sampleresponse->response;
        }

        $question->responsetemplate = [
            'text' => $question->options->responsetemplate,
            'format' => $question->options->responsetemplateformat,
        ];

        $draftid = file_get_submitted_draft_itemid('graderinfo');
        $question->graderinfo = [];
        $question->graderinfo['text'] = file_prepare_draft_area(
            $draftid,
            $this->context->id,
            'qtype_aitext',
            'graderinfo',
            !empty($question->id) ? (int) $question->id : null,
            $this->fileoptions,
            $question->options->graderinfo
        );
        $question->graderinfo['format'] = $question->options->graderinfoformat;
        $question->graderinfo['itemid'] = $draftid;

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
            if (
                $fromform['maxwordlimit'] < $fromform['minwordlimit'] &&
                $fromform['maxwordlimit'] > 0 && $fromform['minwordlimit'] > 0
            ) {
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
