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

use advanced_testcase;
use backup_controller;
use restore_controller;
use quiz_question_helper_test_trait;
use backup;
use PHPUnit\Framework\Attributes\CoversClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/quiz/tests/quiz_question_helper_test_trait.php');
require_once($CFG->dirroot . '/question/type/aitext/tests/helper.php');


/**
 * Test repeated restore functionality specifically for aitext question type.
 *
 * @package    qtype_aitext
 * @category   test
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\restore_questions_parser_processor::class)]
#[CoversClass(\restore_create_categories_and_questions::class)]
final class aitext_repeated_restore_test extends advanced_testcase {
    use quiz_question_helper_test_trait;

    /**
     * Test restore a quiz with duplicate aitext questions (same stamp and questions) into the same course.
     *
     * This test specifically calls the test_restore_quiz_with_duplicate_questions method from
     * mod_quiz\backup\repeated_restore_test and passes in details for the aitext question type.
     * It verifies that the hashing process will match an identical aitext question during restore.
     * This requirement is explained at https://moodledev.io/docs/5.0/apis/plugintypes/qtype/restore.
     */
    public function test_restore_quiz_with_duplicate_aitext_questions(): void {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a course and a user with editing teacher capabilities.
        $generator = $this->getDataGenerator();
        $course1 = $generator->create_course();
        $teacher = $USER;
        $generator->enrol_user($teacher->id, $course1->id, 'editingteacher');
        $qbank = $generator->get_plugin_generator('mod_qbank')->create_instance(['course' => $course1->id]);
        $context = \context_module::instance($qbank->cmid);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        // Create a question category.
        $cat = $questiongenerator->create_question_category(['contextid' => $context->id]);

        // Create a quiz with 2 identical but separate aitext questions.
        $quiz1 = $this->create_test_quiz($course1);
        $question1 = $questiongenerator->create_question('aitext', 'editor', ['category' => $cat->id]);
        quiz_add_quiz_question($question1->id, $quiz1, 0);
        $question2 = $questiongenerator->create_question('aitext', 'editor', ['category' => $cat->id]);
        quiz_add_quiz_question($question2->id, $quiz1, 0);

        // Update question2 to have the same times and stamp as question1.
        $DB->update_record('question', [
            'id' => $question2->id,
            'stamp' => $question1->stamp,
            'timecreated' => $question1->timecreated,
            'timemodified' => $question1->timemodified,
        ]);

        // Backup quiz.
        $bc = new backup_controller(
            backup::TYPE_1ACTIVITY,
            $quiz1->cmid,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $teacher->id
        );
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        // Restore the backup into the same course.
        $rc = new restore_controller(
            $backupid,
            $course1->id,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $teacher->id,
            backup::TARGET_CURRENT_ADDING
        );
        $rc->execute_precheck();
        $rc->execute_plan();
        $rc->destroy();

        // Expect that the restored quiz will have the second question in both its slots
        // by virtue of identical stamp, version, and hash of question answer texts.
        $modules = get_fast_modinfo($course1->id)->get_instances_of('quiz');
        $this->assertCount(2, $modules);
        $quiz2 = end($modules);
        $quiz2structure = \mod_quiz\question\bank\qbank_helper::get_question_structure($quiz2->instance, $quiz2->context);
        $this->assertEquals($quiz2structure[1]->questionid, $quiz2structure[2]->questionid);
    }
}
