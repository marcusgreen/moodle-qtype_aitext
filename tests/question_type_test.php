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

use PHPUnit\Framework\ExpectationFailedException;
use qtype_aitext;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/aitext/questiontype.php');


/**
 * Unit tests for the aitext question type class.
 *
 * @package    qtype_aitext
 * @copyright  Marcus Green 2025
 * @author     Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class question_type_test extends \advanced_testcase {
    /**
     * Always aitext
     *
     * @var mixed
     */
    protected $qtype;

    protected function setUp(): void {
        parent::setUp();
        $this->qtype = new qtype_aitext();
    }

    protected function tearDown(): void {
        $this->qtype = null;
        parent::tearDown();
    }

    /**
     * Get data skeleton
     * @todo consolidate into another earlier function
     *
     * @return \stdClass
     */
    protected function get_test_question_data() {
        $q = new \stdClass();
        $q->id = 1;
        return $q;
    }
    /**
     * Expanded version of name
     * @todo confirm and perhaps put more detail into this comment
     *
     * @covers ::name()
     *
     * @return void
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function test_name(): void {
        $this->assertEquals($this->qtype->name(), 'aitext');
    }
    /**
     * Does can_analyse_response work (it will always be false for this qtype)
     *
     * @covers ::can_analyse_responses()
     *
     * @return void
     */
    public function test_can_analyse_responses(): void {
        $this->assertFalse($this->qtype->can_analyse_responses());
    }

    /**
     * An estimate of the score a student would get by guessing randomly.
     * Which unlike a multi choice or similar would be zero or very close to.
     * Used by statistics calculation rather than the actual qtype.
     *
     * @covers ::get_radom_guess_score()
     *
     * @return void
     */
    public function test_get_random_guess_score(): void {
        $q = $this->get_test_question_data();
        $this->assertEquals(0, $this->qtype->get_random_guess_score($q));
    }

    /**
     * Test get_possible_responses
     *
     * @return void
     * @covers ::get_possible_responses()
     */
    public function test_get_possible_responses(): void {
        $q = $this->get_test_question_data();
        $this->assertEquals([], $this->qtype->get_possible_responses($q));
    }

    /**
     * Deleting a question must also delete its sample responses so no orphan
     * rows are left behind in qtype_aitext_sampleresponses.
     *
     * @covers ::delete_question()
     *
     * @return void
     */
    public function test_delete_question_removes_sampleresponses(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $generator->create_question_category();
        // The 'editor' fixture ships one sample response ('response1').
        $question = $generator->create_question('aitext', 'editor', ['category' => $category->id]);

        // Sanity check: the options row and one sample response row exist.
        $this->assertTrue($DB->record_exists('qtype_aitext', ['questionid' => $question->id]));
        $this->assertEquals(
            1,
            $DB->count_records('qtype_aitext_sampleresponses', ['question' => $question->id])
        );

        $this->qtype->delete_question($question->id, $category->contextid);

        // Both the options row and the sample response rows must be gone.
        $this->assertFalse($DB->record_exists('qtype_aitext', ['questionid' => $question->id]));
        $this->assertEquals(
            0,
            $DB->count_records('qtype_aitext_sampleresponses', ['question' => $question->id])
        );
    }

    /**
     * Re-saving the same question must not accumulate duplicate sample response
     * rows. The stored set must match the submitted form, and blank entries are
     * skipped.
     *
     * @covers ::save_question_options()
     *
     * @return void
     */
    public function test_save_question_options_no_duplicate_sampleresponses(): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/question/type/aitext/tests/helper.php');
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $generator->create_question_category();
        // The 'editor' fixture ships one sample response ('response1').
        $question = $generator->create_question('aitext', 'editor', ['category' => $category->id]);

        $this->assertEquals(
            1,
            $DB->count_records('qtype_aitext_sampleresponses', ['question' => $question->id])
        );

        // Build form data for a re-save of the same question record. Two real
        // sample responses plus a blank one that must be skipped.
        $helper = new \qtype_aitext_test_helper();
        $formdata = $helper->get_aitext_question_form_data_editor();
        $formdata->id = $question->id;
        $formdata->context = \context::instance_by_id($category->contextid);
        $formdata->sampleresponses = ['first response', 'second response', '   '];

        $this->qtype->save_question_options($formdata);

        // Exactly two rows: no duplication of the original, blank entry skipped.
        $this->assertEquals(
            2,
            $DB->count_records('qtype_aitext_sampleresponses', ['question' => $question->id])
        );

        // Saving again with the same data keeps the count stable.
        $this->qtype->save_question_options($formdata);
        $this->assertEquals(
            2,
            $DB->count_records('qtype_aitext_sampleresponses', ['question' => $question->id])
        );
    }

    /**
     * Hints written by the generic Moodle XML export must survive import, as they
     * set the number of tries under the interactive behaviour.
     *
     * @covers ::import_from_xml()
     *
     * @return void
     */
    public function test_import_from_xml_imports_hints(): void {
        global $CFG;
        require_once($CFG->dirroot . '/question/format.php');
        require_once($CFG->dirroot . '/question/format/xml/format.php');

        $xml = '<question type="aitext">
    <name><text>Hint import</text></name>
    <questiontext format="html"><text>Describe a cat.</text></questiontext>
    <hint format="html"><text>Nudge only.</text></hint>
    <hint format="html"><text>Give a partial example.</text></hint>
  </question>';
        // The xmlize() function is deprecated from Moodle 5.1, but core\xml_parser does not exist in 4.5.
        if (class_exists(\core\xml_parser::class)) {
            $data = (new \core\xml_parser())->parse($xml);
        } else {
            require_once($CFG->libdir . '/xmlize.php');
            $data = xmlize($xml);
        }

        $qo = $this->qtype->import_from_xml($data['question'], null, new \qformat_xml());

        $this->assertCount(2, $qo->hint);
        $this->assertEquals('Nudge only.', $qo->hint[0]['text']);
        $this->assertEquals('Give a partial example.', $qo->hint[1]['text']);
    }

    /**
     * A loaded question must expose its hints as 0-indexed question_hint objects,
     * as get_hint() and the interactive behaviour expect.
     *
     * @covers ::initialise_question_instance()
     *
     * @return void
     */
    public function test_loaded_question_has_usable_hints(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $generator->create_question_category();
        $question = $generator->create_question('aitext', 'editor', [
            'category' => $category->id,
            'hint' => [
                ['text' => 'First instruction', 'format' => FORMAT_HTML],
                ['text' => 'Second instruction', 'format' => FORMAT_HTML],
            ],
        ]);

        $loaded = \question_bank::load_question($question->id);

        $this->assertSame([0, 1], array_keys($loaded->hints));
        $this->assertInstanceOf(\question_hint::class, $loaded->hints[0]);
        $this->assertEquals('First instruction', $loaded->hints[0]->hint);
        $this->assertEquals('Second instruction', $loaded->hints[1]->hint);
    }
}
