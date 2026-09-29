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

namespace qtype_aitext\form;

/**
 * Tests for the spellcheck edit form.
 *
 * @package    qtype_aitext
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \qtype_aitext\form\edit_spellcheck
 */
final class edit_spellcheck_test extends \advanced_testcase {
    #[\PHPUnit\Framework\Attributes\Group('baseline')]
    /**
     * Markup in the student answer must not reach the modal as HTML.
     */
    public function test_student_answer_markup_is_escaped(): void {
        $html = $this->render_form_for_answer('<img src=x onerror=alert(1)>');

        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }

    #[\PHPUnit\Framework\Attributes\Group('baseline')]
    /**
     * A regular student answer is still shown in the modal.
     */
    public function test_student_answer_text_is_shown(): void {
        $html = $this->render_form_for_answer('Der Frosch sprang über den Teich.');

        $this->assertStringContainsString('Der Frosch sprang über den Teich.', $html);
    }

    /**
     * Save a plain text answer in a question preview and render the spellcheck form for it.
     *
     * @param string $answer Student answer
     * @return string Rendered form HTML
     */
    private function render_form_for_answer(string $answer): string {
        global $PAGE, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category([
            'contextid' => \context_course::instance($course->id)->id,
        ]);
        $questionrecord = $questiongenerator->create_question('aitext', 'plain', ['category' => $category->id]);

        $usercontext = \context_user::instance($USER->id);
        $quba = \question_engine::make_questions_usage_by_activity('core_question_preview', $usercontext);
        $quba->set_preferred_behaviour('deferredfeedback');
        $slot = $quba->add_question(\question_bank::load_question($questionrecord->id), 1);
        $quba->start_all_questions();
        $quba->process_action($slot, ['answer' => $answer, 'answerformat' => FORMAT_PLAIN]);
        \question_engine::save_questions_usage_by_activity($quba);
        $questionattemptid = $quba->get_question_attempt($slot)->get_database_id();

        $PAGE->set_context($usercontext);
        $form = new edit_spellcheck(null, null, 'post', '', [], true, ['questionattemptid' => $questionattemptid]);
        $form->set_data_for_dynamic_submission();
        return $form->render();
    }
}
