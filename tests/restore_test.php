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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . "/phpunit/classes/restore_date_testcase.php");

/**
 * Test restore logic.
 *
 * @package    qtype_aitext
 * @copyright  2019 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class restore_test extends \restore_date_testcase {

    /**
     * Test missing qtype_aitext_options creation.
     *
     * @covers ::restore()
     *
     * Old backup files may contain aitext with no qtype_aitext_options record.
     * During restore, we add default options for any questions like that.
     * That is what is tested in this file.
     */
    public function test_restore_create_missing_qtype_aitext_options(): void {
        global $DB;

        // Create a course with one essay question in its question bank.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $contexts = new \core_question\local\bank\question_edit_contexts(\context_course::instance($course->id));
        $category = question_make_default_categories($contexts->all());
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $aitext = $questiongenerator->create_question('aitext', null, ['category' => $category->id]);

        // Remove the options record, which means that the backup will look like a backup made in an old Moodle.
        $DB->delete_records('qtype_aitext', ['questionid' => $aitext->id]);

        // Do backup and restore.
        $newcourseid = $this->backup_and_restore($course);

        // Verify that the restored question has options.
        $contexts = new \core_question\local\bank\question_edit_contexts(\context_course::instance($newcourseid));
        $newcategory = question_make_default_categories($contexts->all());
        $newaitext = $DB->get_record_sql('SELECT q.*
                                              FROM {question} q
                                              JOIN {question_versions} qv ON qv.questionid = q.id
                                              JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                                             WHERE qbe.questioncategoryid = ?
                                               AND q.qtype = ?', [$newcategory->id, 'aitext']);
        $this->assertTrue($DB->record_exists('qtype_aitext', ['questionid' => $newaitext->id]));
    }
}
