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
 * @package    qtype
 * @subpackage aitext
 * @author  2023 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * aitext question type editing form.
 *
 * @author  2023 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_aitext_edit_form extends question_edit_form {

    protected function definition_inner($mform) {
        /** @var qtype_aitext $qtype */
        $qtype = question_bank::get_qtype('aitext');
        $mform->removeelement('generalfeedback');
        $mform->removeelement('questiontext');
        $mform->addElement('editor', 'questiontext', get_string('questiontext', 'mod_quiz'),
            ['maxlen' => 50, 'rows' => 5, 'size' => 30], $this->editoroptions);

        $mform->addElement('textarea', 'aiprompt', get_string('aiprompt', 'qtype_aitext'),
             ['maxlen' => 50, 'rows' => 5, 'size' => 30]);
        $mform->setType('aiprompt', PARAM_RAW);
        $mform->addHelpButton('aiprompt', 'aiprompt', 'qtype_aitext');

        $mform->addElement('textarea', 'markscheme', get_string('markscheme', 'qtype_aitext'),
             ['maxlen' => 50, 'rows' => 6, 'size' => 30]);
        $mform->setType('markscheme', PARAM_RAW);
        $mform->addHelpButton('markscheme', 'markscheme', 'qtype_aitext');

        $mform->addElement('editor', 'generalfeedback', get_string('generalfeedback', 'question')
        , ['rows' => 10], $this->editoroptions);
        $mform->addElement('header', 'responseoptions', get_string('responseoptions', 'qtype_aitext'));
        $mform->setExpanded('responseoptions');

        $mform->addElement('select', 'responseformat',
                get_string('responseformat', 'qtype_aitext'), $qtype->response_formats());
        $mform->setDefault('responseformat', $this->get_default_value('responseformat', 'editor'));

        $mform->addElement('select', 'responsefieldlines',
                get_string('responsefieldlines', 'qtype_aitext'), $qtype->response_sizes());
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
        $mform->addElement('editor', 'responsetemplate', get_string('responsetemplate', 'qtype_aitext'),
                array('rows' => 10),  array_merge($this->editoroptions, array('maxfiles' => 0)));
        $mform->addHelpButton('responsetemplate', 'responsetemplate', 'qtype_aitext');

        $mform->addElement('header', 'graderinfoheader', get_string('graderinfoheader', 'qtype_aitext'));
        $mform->setExpanded('graderinfoheader');
        $mform->addElement('editor', 'graderinfo', get_string('graderinfo', 'qtype_aitext'),
                array('rows' => 10), $this->editoroptions);
    }

    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);

        if (empty($question->options)) {
            return $question;
        }

        $question->responseformat = $question->options->responseformat;
        $question->responserequired = $question->options->responserequired;
        $question->responsefieldlines = $question->options->responsefieldlines;
        $question->minwordenabled = $question->options->minwordlimit ? 1 : 0;
        $question->minwordlimit = $question->options->minwordlimit;
        $question->maxwordenabled = $question->options->maxwordlimit ? 1 : 0;
        $question->maxwordlimit = $question->options->maxwordlimit;
        $question->aiprompt = $question->options->aiprompt;

        $question->responsetemplate = array(
            'text' => $question->options->responsetemplate,
            'format' => $question->options->responsetemplateformat,
        );

        return $question;
    }

    public function validation($fromform, $files) {
        $errors = parent::validation($fromform, $files);

        if ($fromform['responserequired']) {
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
        }
        return $errors;
    }

    public function qtype() {
        return 'aitext';
    }
}
