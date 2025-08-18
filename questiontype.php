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
 *
 * Question type class for the aitext question type.
 *
 * @package    qtype_aitext
 * @subpackage aitext
 * @copyright  Marcus Green
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
    /**
     * value from id column of quesiton table
     *
     * @var int
     */
    public $id;

    /**
     * Get the URL for questiontestrun.php for a question.
     *
     * @param stdClass|qtype_stack_question $question question data, as from question_bank::load_question
     *      or question_bank::load_question_data.
     * @return moodle_url the URL.
     */
    public function get_question_test_url($question) {
        $linkparams = $this->get_question_url_params($question);
        return new moodle_url('/question/type/aitext/questiontestrun.php', $linkparams);
    }

    /**
     * Question type is manually graded, though with this type it is
     * marked by the AI/LLM system
     *
     * @return bool
     */
    public function is_manual_graded() {
        return true;
    }
    /**
     * Probably redundant with the removal of
     * files as a response type
     *
     * @return array
     */
    public function response_file_areas() {
        return ['attachments', 'answer'];
    }
    /**
     * Loads the question type specific options for the question
     *
     * @param object $question
     * @return bool
     */
    public function get_question_options($question) {
        global $DB;
        $question->options = $DB->get_record(
            'qtype_aitext',
            ['questionid' => $question->id],
            '*',
            MUST_EXIST
        );
        $question->options->sampleresponses = $DB->get_records(
            'qtype_aitext_sampleresponses',
            ['question' => $question->id],
            'id ASC',
            '*'
        );
        parent::get_question_options($question);
    }

    /**
     * Markscheme may not be required here
     *
     * @param stdClass $fromform
     * @return void
     */
    public function save_defaults_for_new_questions(stdClass $fromform): void {
        parent::save_defaults_for_new_questions($fromform);
        $this->set_default_value('responseformat', $fromform->responseformat);
        $this->set_default_value('responsefieldlines', $fromform->responsefieldlines);
        $this->set_default_value('markscheme', $fromform->markscheme);
    }
    /**
     * Write the question data from the editing form to the database
     *
     * @param object $formdata
     * @return object
     */
    public function save_question_options($formdata) {
        global $DB;
        $context = $formdata->context;
        $options = $DB->get_record('qtype_aitext', ['questionid' => $formdata->id]);
        if (!$options) {
            $options = new stdClass();
            $options->questionid = $formdata->id;
            $options->id = $DB->insert_record('qtype_aitext', $options);
        }

        $options->spellcheck = !empty($formdata->spellcheck);
        $options->aiprompt = $formdata->aiprompt;
        $options->markscheme = $formdata->markscheme;
        $options->model = trim($formdata->model);
        $options->responseformat = $formdata->responseformat;
        $options->responsefieldlines = $formdata->responsefieldlines;
        $options->minwordlimit = isset($formdata->minwordenabled) ? $formdata->minwordlimit : null;
        $options->maxwordlimit = isset($formdata->maxwordenabled) ? $formdata->maxwordlimit : null;

        $options->maxbytes = $formdata->maxbytes ?? 0;
        if (is_array($formdata->graderinfo)) {
            $formdata->graderinfo = [
                'text' => '',
                'format' => FORMAT_HTML,
            ];
            $options->responsetemplate = $formdata->responsetemplate['text'];
            $options->responsetemplateformat = $formdata->responsetemplate['format'];
        }

        $options->graderinfo = $this->import_or_save_files(
            $formdata->graderinfo,
            $context,
            'qtype_aitext',
            'graderinfo',
            $formdata->id
        );

        $options->graderinfoformat = $formdata->graderinfo['format'];
        $options->responsetemplate = $formdata->responsetemplate['text'];
        $options->responsetemplateformat = $formdata->responsetemplate['format'];

        $DB->update_record('qtype_aitext', $options);
        foreach ($formdata->sampleresponses as $sr) {
            $sampleresponse['question'] = $formdata->id;
            $sampleresponse['response'] = $sr;
            $DB->insert_record('qtype_aitext_sampleresponses', $sampleresponse);
        }
    }
    /**
     * Called when previewing a question or when displayed in a quiz
     *  (not from within the editing form)
     * @param question_definition $question
     * @param object $questiondata
     * @return void
     */
    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        /**@var qtype_aitext_question  $question */
        $question->responseformat = $questiondata->options->responseformat;
        $question->responsefieldlines = $questiondata->options->responsefieldlines;
        $question->minwordlimit = $questiondata->options->minwordlimit;
        $question->maxwordlimit = $questiondata->options->maxwordlimit;
        $question->graderinfo = $questiondata->options->graderinfo;
        $question->graderinfoformat = $questiondata->options->graderinfoformat;
        $question->responsetemplate = $questiondata->options->responsetemplate;
        $question->responsetemplateformat = $questiondata->options->responsetemplateformat;
        $question->aiprompt = $questiondata->options->aiprompt;
        $question->markscheme = $questiondata->options->markscheme;
        parent::get_question_options($question);
        $question->sampleresponses = $this->get_sampleresponses($question);

        /* Legacy quesitons may not have a model set, so assign the first in the settings */
        if (empty($question->model)) {
            $model = explode(",", get_config('tool_aiconnect', 'model'))[0];
            $question->model = $model;
        } else {
            $question->model = $questiondata->options->model;
        }
    }

    /**
     * Get the structure from the database
     *
     * @param qtype_aitext $question
     * @return array
     */
    public function get_sampleresponses($question) {
        global $DB;
        $sampleresponses = $DB->get_records('qtype_aitext_sampleresponses', ['question' => $question->id]);
        return $sampleresponses;
    }

    /**
     * Delete a question from the database
     *
     * @param int $questionid
     * @param int $contextid
     * @return void
     */
    public function delete_question($questionid, $contextid) {
        global $DB;

        $DB->delete_records('qtype_aitext', ['questionid' => $questionid]);
        parent::delete_question($questionid, $contextid);
    }

    /**
     * The different response formats that the question type supports.
     * internal name => human-readable name.
     *
     * @return array
     */
    public function response_formats() {
        return [
            'editor' => get_string('formateditor', 'qtype_aitext'),
            'plain' => get_string('formatplain', 'qtype_aitext'),
            'monospaced' => get_string('formatmonospaced', 'qtype_aitext'),
        ];
    }

    /**
     * The choices that should be offerd when asking if a response is required
     *
     * @return array
     */
    public function response_required_options() {
        return [
            1 => get_string('responseisrequired', 'qtype_aitext'),
            0 => get_string('responsenotrequired', 'qtype_aitext'),
        ];
    }

    /**
     * The choices that should be offered for the input box size.
     *
     * @return array
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
     * The choices that should be offered for the number of attachments.
     * Note: remove method and calls to it as file is no longer a submit option
     *
     * @return array
     */
    public function attachment_options() {
        return [
            0 => get_string('no'),
            1 => '1',
            2 => '2',
            3 => '3',
            -1 => get_string('unlimited'),
        ];
    }

    /**
     * The choices that should be offered for the number of required attachments.
     * Note: remove as file has been removed as a submission type
     *
     * @return array
     */
    public function attachments_required_options() {
        return [
            0 => get_string('attachmentsoptional', 'qtype_aitext'),
            1 => '1',
            2 => '2',
            3 => '3',
        ];
    }

    /**
     * data used by export_to_xml
     * @return array
     */
    public function extra_question_fields() {
        return [
            'qtype_aitext',
            'responseformat',
            'responsefieldlines',
            'minwordlimit',
            'maxwordlimit',
            'graderinfo',
            'graderinfoformat',
            'responsetemplate',
            'responsetemplateformat',
            'aiprompt',
            'markscheme',
            'model',
            'spellcheck',
        ];
    }
    /**
     * Create a question from reading in a file in Moodle xml format
     *
     * @param array $data
     * @param stdClass $question (might be an array)
     * @param qformat_xml $format
     * @param stdClass $extra
     * @return boolean
     */
    public function import_from_xml($data, $question, qformat_xml $format, $extra = null) {
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
            $qo->$field = $format->getpath($data, ['#', $field, 0, '#'], '');
        }
        // Note: add in other text fields.
        $textfields = ['responsetemplate'];
        foreach ($textfields as $field) {
            $fmt = $format->get_format($format->getpath($data, ['#', $field . 'format', 0, '#'], 0));
            $qo->$field = $format->import_text_with_files($data, ['#', $field, 0], '', $fmt);
        }
        if (isset($qo->maxwordlimit) && $qo->maxwordlimit > "") {
            $qo->maxwordenabled = true;
        }
        if (isset($qo->minwordlimit) && $qo->minwordlimit > "") {
            $qo->minwordenabled = true;
        }

        $extraanswersfields = $this->extra_answer_fields();
        if (is_array($extraanswersfields)) {
            array_shift($extraanswersfields);
        }
        if (isset($data['#']['sampleresponse'])) {
            $sampleresponses = $data['#']['sampleresponse'];
            foreach ($sampleresponses as $key => $srxml) {
                $qo->sampleresponses[$key] = $format->getpath($srxml, ['#', 'response', 0, '#'], 0);
            }
        }
        return $qo;
    }
    /**
     * Export question to the Moodle XML format
     *
     * @param object $question
     * @param qformat_xml $format
     * @param object $extra
     * @return string
     */
    public function export_to_xml($question, qformat_xml $format, $extra = null) {
        $fs = get_file_storage();
        $textfields = $this->get_text_fields();
        ;
        $formatfield = '/^(' . implode('|', $textfields) . ')format$/';
        $fields = $this->extra_question_fields();
        array_shift($fields); // Remove table name.

        $output = '';
        foreach ($fields as $field) {
            if (preg_match($formatfield, $field)) {
                continue;
            }
            if (in_array($field, $textfields)) {
                $files = $fs->get_area_files($question->contextid, 'question', $field, $question->id);
                $output .= "    <$field " . $format->format($question->options->{$field . 'format'}) . ">\n";
                $output .= '      ' . $format->writetext($question->options->$field);
                $output .= $format->write_files($files);
                $output .= "    </$field>\n";
            } else {
                $value = $question->options->$field;
                if ($field == 'errorcmid') {
                    $value = $this->export_errorcmid($value);
                }
                $output .= "    <$field>" . $format->xml_escape($value) . "</$field>\n";
            }
        }
        foreach ($question->options->sampleresponses as $sampleresponse) {
            $output .= "     <sampleresponse>\n";
            $output .= '      <question>' . $sampleresponse->question . "</question>\n";
            $output .= '      <response>' . $sampleresponse->response . "</response>\n";
            $output .= "     </sampleresponse>\n";
        }
        return $output;
    }
    /**
     * Editing form text fields
     *
     * @return string[]
     */
    public function get_text_fields() {
        return [
                'graderinfo',
                'responsetemplate',
                'responsesample',
                'correctfeedback',
                'incorrectfeedback',
                'partiallycorrectfeedback',
        ];
    }
    /**
     * Thrown on error   when exporting xml
     * @param mixed $cmid
     * @return string
     */
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
    /**
     * The name this question should appear as in the create new question
     * dropdown.
     *
     * When using local_ai_manager the availability ai
     * is controlled by the tenant setting.
     *
     * @return mixed
     */
    public function menu_name() {
        if (get_config('qtype_aitext', 'backend') === 'local_ai_manager' && class_exists('\local_ai_manager\local\tenant')) {
            $tenant = \core\di::get(\local_ai_manager\local\tenant::class);
            if (!$tenant->is_tenant_allowed()) {
                return '';
            }
        }
        return parent::menu_name();
    }
}
