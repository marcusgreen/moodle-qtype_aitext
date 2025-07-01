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
 * Aitext question type backup
 *
 * @package    qtype_aitext
 * @subpackage backup-moodle2
 * @copyright  2024 Marcus Green

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Provide the necessary information needed to restore one aitext instance
 *
 * restore plugin class that provides the necessary information
 * needed to restore one aitext qtype plugin
 *
 * @copyright  2024 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_qtype_aitext_plugin extends restore_qtype_plugin {

    /**
     * Returns the paths to be handled by the plugin at question level
     */
    protected function define_question_plugin_structure() {
        return [
            new restore_path_element('aitext', $this->get_pathfor('/aitext')),
        ];
    }


    /**
     * Convert the backup structure of this question type into a structure matching its question data
     *
     * This should take the hierarchical array of tags from the question's backup structure, and return a structure that matches
     * that returned when calling {@see get_question_options()} for this question type.
     * See https://docs.moodle.org/dev/Question_data_structures#Representation_1:_%24questiondata for an explanation of this
     * structure.
     *
     * This data will then be used to produce an identity hash for comparison with questions in the database.
     *
     * This base implementation deals with all common backup elements created by the add_question_*_options() methods in this class,
     * plus elements added by ::define_question_plugin_structure() named for the qtype. The question type will need to extend
     * this function if ::define_question_plugin_structure() adds any other elements to the backup.
     *
     * @param array $backupdata The hierarchical array of tags from the backup.
     * @return \stdClass The questiondata object.
     */
    public static function convert_backup_to_questiondata(array $backupdata): \stdClass {
        global $DB;

        $questiondata = parent::convert_backup_to_questiondata($backupdata);

        $questiondata->options = $DB->get_record('qtype_aitext',
            ['questionid' => $questiondata->id], '*', MUST_EXIST);
        $questiondata->options->sampleresponses = $DB->get_records(
            'qtype_aitext_sampleresponses',
            ['question' => $questiondata->id],
            'id ASC',
            '*'
        );

        return $questiondata;
    }

    /**
     * Return a list of paths to fields to be removed from questiondata before creating an identity hash.
     *
     * Fields that should be excluded from common elements such as answers or numerical units that are used by the plugin will
     * be excluded automatically. This method just needs to define any specific to this plugin, such as foreign keys used in the
     * plugin's tables.
     *
     * The returned array should be a list of slash-delimited paths to locate the fields to be removed from the questiondata object.
     * For example, if you want to remove the field `$questiondata->options->questionid`, the path would be '/options/questionid'.
     * If a field in the path is an array, the rest of the path will be applied to each object in the array. So if you have
     * `$questiondata->options->answers[]`, the path '/options/answers/id' will remove the 'id' field from each element of the
     * 'answers' array.
     *
     * @return array
     */
    protected function define_excluded_identity_hash_fields(): array {
        return [
            '/id',
            '/questionid',
            '/sampleresponses/id',
            '/sampleresponses/question',
        ];
    }

    /**
     * Process the qtype/aitext element
     *
     * @param array $data
     * @return void
     */
    public function process_aitext($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        if (!isset($data->responsetemplate)) {
            $data->responsetemplate = '';
        }
        if (!isset($data->responsetemplateformat)) {
            $data->responsetemplateformat = FORMAT_HTML;
        }

        if (!isset($data->minwordlimit)) {
            $data->minwordlimit = null;
        }
        if (!isset($data->maxwordlimit)) {
            $data->maxwordlimit = null;
        }
        if (!isset($data->attachmentsrequired)) {
            $data->attachmentsrequired = 0;
        }

        // Detect if the question is created or mapped.
        $questioncreated = $this->get_mappingid('question_created',
                $this->get_old_parentid('question')) ? true : false;

        // If the question has been created by restore, we need to create its
        // qtype_aitext too.
        if ($questioncreated) {
            $data->questionid = $this->get_new_parentid('question');
            $newitemid = $DB->insert_record('qtype_aitext', $data);
            $this->set_mapping('qtype_aitext', $oldid, $newitemid);
        }
    }

    /**
     * Return the contents of this qtype to be processed by the links decoder
     */
    public static function define_decode_contents() {
        return [
            new restore_decode_content('qtype_aitext', 'graderinfo', 'qtype_aitext'),
        ];
    }

    /**
     * When restoring old data, that does not have the aitext options information
     * in the XML, supply defaults.
     */
    protected function after_execute_question() {
        global $DB;

        $aitextswithoutoptions = $DB->get_records_sql("
                    SELECT q.*
                      FROM {question} q
                      JOIN {backup_ids_temp} bi ON bi.newitemid = q.id
                 LEFT JOIN {qtype_aitext} qeo ON qeo.questionid = q.id
                     WHERE q.qtype = ?
                       AND qeo.id IS NULL
                       AND bi.backupid = ?
                       AND bi.itemname = ?
                ", ['aitext', $this->get_restoreid(), 'question_created']);

        foreach ($aitextswithoutoptions as $q) {
            $defaultoptions = new stdClass();
            $defaultoptions->questionid = $q->id;
            $defaultoptions->aiprompt = '';
            $defaultoptions->markscheme = '';
            $defaultoptions->sampleanswer = '';
            $defaultoptions->responseformat = 'editor';
            $defaultoptions->responsefieldlines = 15;
            $defaultoptions->minwordlimit = null;
            $defaultoptions->maxwordlimit = null;
            $defaultoptions->attachments = 0;
            $defaultoptions->attachmentsrequired = 0;
            $defaultoptions->graderinfo = '';
            $defaultoptions->model = '';
            $defaultoptions->graderinfoformat = FORMAT_HTML;
            $defaultoptions->responsetemplate = '';
            $defaultoptions->responsetemplateformat = FORMAT_HTML;
            $DB->insert_record('qtype_aitext', $defaultoptions);
        }
    }
}
