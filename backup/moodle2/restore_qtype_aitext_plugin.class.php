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

        $paths = [];

        // This qtype uses question_answers, add them.
        $this->add_question_question_answers($paths);

        // Add own qtype stuff.
        $elename = 'aitext';
        // We use get_recommended_name() so this works.
        $elepath = $this->get_pathfor('/aitext');
        $paths[] = new restore_path_element($elename, $elepath);

        $elename = 'sampleresponse';
        $elepath = $this->get_pathfor('/sampleresponses/sampleresponse');
        $paths[] = new restore_path_element($elename, $elepath);
        return $paths;

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
     * Process the settings for sampleresponses
     *
     * @param array $data
     */
    public function process_sampleresponse($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        // Detect if the question is created or mapped.
        $oldquestionid = $this->get_old_parentid('question');
        $newquestionid = $this->get_new_parentid('question');
        $questioncreated = $this->get_mappingid('question_created', $oldquestionid) ? true : false;

        // If the question has been created by restore, we need to create its question_aitext too.
        if ($questioncreated) {
            // Adjust value to link back to the questions table.
            $data->question = $newquestionid;
            // Insert record.
            $newitemid = $DB->insert_record('qtype_aitext_sampleresponses', $data);
            // Create mapping (needed for decoding links).
            $this->set_mapping('qtype_aitext_sampleresponses', $oldid, $newitemid);
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
        $qwithoutoptions = $DB->get_records_sql("
                    SELECT q.*
                      FROM {question} q
                      JOIN {backup_ids_temp} bi ON bi.newitemid = q.id
                 LEFT JOIN {qtype_aitext} qeo ON qeo.questionid = q.id
                     WHERE q.qtype = ?
                       AND qeo.id IS NULL
                       AND bi.backupid = ?
                       AND bi.itemname = ?
                ", ['aitext', $this->get_restoreid(), 'question_created']);

        foreach ($qwithoutoptions as $q) {
            $defaultoptions = new stdClass();
            $defaultoptions->questionid = $q->id;
            $defaultoptions->aiprompt = '';
            $defaultoptions->markscheme = '';
            $defaultoptions->sampleresponses = [];
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
