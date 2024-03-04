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
 * Question type class for the aitext question type.
 *
 * @package    qtype_aitext
 * @subpackage aitext
 * @author     Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');

/**
 * The aitext question type.
 *
 * @copyright  2024 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_aitext extends question_type {
    public function is_manual_graded() {
        return true;
    }

    public function response_file_areas() {
        return array('attachments', 'answer');
    }

    public function get_question_options($question) {
        global $DB;
        $question->options = $DB->get_record('qtype_aitext',
                array('questionid' => $question->id), '*', MUST_EXIST);
        parent::get_question_options($question);
    }

    public function save_defaults_for_new_questions(stdClass $fromform): void {
        parent::save_defaults_for_new_questions($fromform);
        $this->set_default_value('responseformat', $fromform->responseformat);
        $this->set_default_value('responserequired', $fromform->responserequired);
        $this->set_default_value('responsefieldlines', $fromform->responsefieldlines);
        $this->set_default_value('markscheme', $fromform->markscheme);

    }

    public function save_question_options($formdata) {
        global $DB;
        $context = $formdata->context;

        $options = $DB->get_record('qtype_aitext', array('questionid' => $formdata->id));
        if (!$options) {
            $options = new stdClass();
            $options->questionid = $formdata->id;
            $options->id = $DB->insert_record('qtype_aitext', $options);
        }
        $options->aiprompt = $formdata->aiprompt;
        $options->markscheme = $formdata->markscheme;

        $options->responseformat = $formdata->responseformat;
        $options->responserequired = $formdata->responserequired;
        $options->responsefieldlines = $formdata->responsefieldlines;
        $options->minwordlimit = isset($formdata->minwordenabled) ? $formdata->minwordlimit : null;
        $options->maxwordlimit = isset($formdata->maxwordenabled) ? $formdata->maxwordlimit : null;
        $options->maxbytes = $formdata->maxbytes ?? 0;
        if (is_array($formdata->graderinfo)) {
            // Today find out what it should save and ensure it is available as text not arrays.
            $formdata->graderinfo = [
                'text' => '',
                'format' => FORMAT_HTML
            ];
            $options->responsetemplate = $formdata->responsetemplate['text'];
            $options->responsetemplateformat = $formdata->responsetemplate['format'];

        }

        $options->graderinfo = $this->import_or_save_files($formdata->graderinfo,
                $context, 'qtype_aitext', 'graderinfo', $formdata->id);

        $options->graderinfoformat = $formdata->graderinfo['format'];
        $options->responsetemplate = $formdata->responsetemplate['text'];
        $options->responsetemplateformat = $formdata->responsetemplate['format'];
        $DB->update_record('qtype_aitext', $options);
    }
    /**
     *
     * @param qtype_aitext $question
     * @param object $questiondata
     * @return void
     * @throws coding_exception
     */
    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        /**@var qtype_aitext_question  $question */
        $question->responseformat = $questiondata->options->responseformat;
        $question->responserequired = $questiondata->options->responserequired;
        $question->responsefieldlines = $questiondata->options->responsefieldlines;
        $question->minwordlimit = $questiondata->options->minwordlimit;
        $question->maxwordlimit = $questiondata->options->maxwordlimit;
        $question->graderinfo = $questiondata->options->graderinfo;
        $question->graderinfoformat = $questiondata->options->graderinfoformat;
        $question->responsetemplate = $questiondata->options->responsetemplate;
        $question->responsetemplateformat = $questiondata->options->responsetemplateformat;
        $question->aiprompt = $questiondata->options->aiprompt;
        $question->markscheme = $questiondata->options->markscheme;
    }

    public function delete_question($questionid, $contextid) {
        global $DB;

        $DB->delete_records('qtype_aitext', array('questionid' => $questionid));
        parent::delete_question($questionid, $contextid);
    }

    /**
     * @return array the different response formats that the question type supports.
     * internal name => human-readable name.
     */
    public function response_formats() {
        return array(
            'editor' => get_string('formateditor', 'qtype_aitext'),
            'plain' => get_string('formatplain', 'qtype_aitext'),
            'monospaced' => get_string('formatmonospaced', 'qtype_aitext'),
        );
    }

    /**
     * @return array the choices that should be offerd when asking if a response is required
     */
    public function response_required_options() {
        return array(
            1 => get_string('responseisrequired', 'qtype_aitext'),
            0 => get_string('responsenotrequired', 'qtype_aitext'),
        );
    }

    /**
     * @return array the choices that should be offered for the input box size.
     */
    public function response_sizes() {
        $choices = [
            2 => get_string('nlines', 'qtype_aitext', 2),
            3 => get_string('nlines', 'qtype_aitext', 3),
        ];
        for ($lines = 5; $lines <= 40; $lines += 5) {
            $choices[$lines] = get_string('nlines', 'qtype_aitext', $lines);
        }
        return $choices;
    }

    /**
     * @return array the choices that should be offered for the number of attachments.
     */
    public function attachment_options() {
        return array(
            0 => get_string('no'),
            1 => '1',
            2 => '2',
            3 => '3',
            -1 => get_string('unlimited'),
        );
    }

    /**
     * @return array the choices that should be offered for the number of required attachments.
     */
    public function attachments_required_options() {
        return array(
            0 => get_string('attachmentsoptional', 'qtype_aitext'),
            1 => '1',
            2 => '2',
            3 => '3'
        );
    }

    /**
     * data used by export_to_xml
     * @return array
     */
    public function extra_question_fields() {
        return [
            'qtype_aitext',
            'aiprompt',
            'responseformat',
            'responserequired',
            'responsefieldlines',
            'minwordlimit',
            'maxwordlimit',
            'graderinfo',
            'graderinfoformat',
            'responsetemplate',
            'responsetemplateformat',
            'aiprompt',
            'markscheme'
        ];
    }
    public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {
        $questiontype = $data['@']['type'];
        if ($questiontype != $this->name()) {
            return false;
        }

        $extraquestionfields = $this->extra_question_fields();
        if (!is_array($extraquestionfields)) {
            return false;
        }

        // Omit table name.
        array_shift($extraquestionfields);
        $qo = $format->import_headers($data);
        $qo->qtype = $questiontype;

        foreach ($extraquestionfields as $field) {
            $qo->$field = $format->getpath($data, array('#', $field, 0, '#'), '');
        }

        $extraanswersfields = $this->extra_answer_fields();
        if (is_array($extraanswersfields)) {
            array_shift($extraanswersfields);
        }

        return $qo;
    }
    public function export_to_xml($question, qformat_xml $format, $extra = null) {
        $fs = get_file_storage();
        $textfields = $this->get_text_fields();;
        $formatfield = '/^('.implode('|', $textfields).')format$/';
        $fields = $this->extra_question_fields();
        array_shift($fields); // Remove table name.

        $output = '';
        foreach ($fields as $field) {
            if (preg_match($formatfield, $field)) {
                continue;
            }
            if (in_array($field, $textfields)) {
                $files = $fs->get_area_files($question->contextid, 'question', $field, $question->id);
                $output .= "    <$field ".$format->format($question->options->{$field.'format'}).">\n";
                $output .= '      '.$format->writetext($question->options->$field);
                $output .= $format->write_files($files);
                $output .= "    </$field>\n";
            } else {
                $value = $question->options->$field;
                if ($field == 'errorcmid') {
                    $value = $this->export_errorcmid($value);
                }
                $output .= "    <$field>".$format->xml_escape($value)."</$field>\n";
            }
        }

        return $output;
    }
    public function get_text_fields() {
        return array('graderinfo',
                     'responsetemplate',
                     'responsesample',
                     'correctfeedback',
                     'incorrectfeedback',
                     'partiallycorrectfeedback');
    }
    protected function export_errorcmid($cmid) {
        global $PAGE;
        if ($PAGE && $PAGE->course && $cmid) {
            $modinfo = get_fast_modinfo($PAGE->course->id);
            if (isset($modinfo->cms[$cmid])) {
                return $modinfo->cms[$cmid]->name;
            }
        }
        return '';
    }

}
