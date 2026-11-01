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

namespace qtype_aitext\local;

/**
 * Tests for the example question library.
 *
 * @package    qtype_aitext
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \qtype_aitext\local\example_library
 */
final class example_library_test extends \advanced_testcase {
    /**
     * Every example file is listed, keyed by its path, with its metadata.
     */
    public function test_get_examples(): void {
        $examples = example_library::get_examples();
        $this->assertCount(count(glob(example_library::get_folder() . '/*/*.xml')), $examples);

        $example = example_library::get_example('english/future-tense');
        $this->assertSame('english/future-tense', $example->path);
        $this->assertSame('english', $example->subject);
        $this->assertSame('The future tense', $example->name);
        $this->assertSame('eng-future-tense', $example->idnumber);
        $this->assertNotEmpty($example->description);
        $this->assertSame(['esl'], $example->facets['level']);
        $this->assertSame(['grammar'], $example->facets['topic']);
        $this->assertContains('subject:english', $example->tags);
    }

    /**
     * Only paths found in the examples folder are accepted.
     */
    public function test_get_example_rejects_unknown_paths(): void {
        $this->assertNull(example_library::get_example('english/no-such-example'));
        $this->assertNull(example_library::get_example('../examples/english/future-tense'));
        $this->assertNull(example_library::get_example('english/future-tense.xml'));
        $this->assertNull(example_library::get_example('../../../../config'));
    }

    /**
     * Examples are grouped by subject folder.
     */
    public function test_get_examples_by_subject(): void {
        $grouped = example_library::get_examples_by_subject();
        $this->assertArrayHasKey('medicine', $grouped);
        foreach ($grouped as $subject => $examples) {
            foreach ($examples as $example) {
                $this->assertSame($subject, $example->subject);
            }
        }
    }

    /**
     * Subject paths cover exactly the examples in that folder.
     */
    public function test_get_subject_paths(): void {
        $paths = example_library::get_subject_paths('english');
        $this->assertContains('english/future-tense', $paths);
        foreach ($paths as $path) {
            $this->assertStringStartsWith('english/', $path);
        }
        $this->assertSame([], example_library::get_subject_paths('no-such-subject'));
    }

    /**
     * The tree lists every example under its subject and expands the selected one's subject.
     */
    public function test_get_tree(): void {
        $baseurl = new \moodle_url('/question/type/aitext/examples.php', ['cmid' => 5]);
        $tree = example_library::get_tree($baseurl, 'medicine/ecg-rhythm-strip');

        $this->assertSame(count(example_library::get_examples()), array_sum(array_column($tree, 'count')));
        $expanded = array_values(array_filter($tree, fn($s) => $s['expanded']));
        $this->assertCount(1, $expanded);
        $this->assertSame('medicine', $expanded[0]['subject']);
        $selected = array_values(array_filter($expanded[0]['examples'], fn($e) => $e['selected']));
        $this->assertSame('medicine/ecg-rhythm-strip', $selected[0]['path']);
        $this->assertStringContainsString('cmid=5', $selected[0]['url']);
        $this->assertStringContainsString('example=medicine%2Fecg-rhythm-strip', $selected[0]['url']);
    }

    /**
     * The preview has the question set-up, with embedded images as data URIs.
     */
    public function test_get_preview(): void {
        $this->assertNull(example_library::get_preview('../../../../config'));

        $preview = example_library::get_preview('english/future-tense');
        $this->assertSame('The future tense', $preview['name']);
        $this->assertSame('english', $preview['subject']);
        $this->assertStringContainsString('next weekend', $preview['questiontext']);
        $this->assertStringContainsString('future tense', $preview['aiprompt']);
        $this->assertNotEmpty($preview['markscheme']);
        $this->assertCount(3, $preview['samples']);
        $this->assertTrue($preview['hassamples']);
        $this->assertSame('3', $preview['defaultgrade']);

        $preview = example_library::get_preview('engineering/damaged-spur-gear');
        $this->assertStringContainsString('src="data:image/svg+xml;base64,', $preview['questiontext']);
        $this->assertStringNotContainsString('@@PLUGINFILE@@', $preview['questiontext']);
        $this->assertNotEmpty($preview['generalfeedback']);
        $this->assertContains(['facet' => 'feature', 'label' => 'Image'], $preview['tags']);
    }

    /**
     * The preview fragment renders for users who can add questions, and rejects unknown paths.
     *
     * @covers ::qtype_aitext_output_fragment_example_preview
     */
    public function test_preview_fragment(): void {
        global $CFG;
        require_once($CFG->dirroot . '/question/type/aitext/lib.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        $context = \context_system::instance();

        $html = qtype_aitext_output_fragment_example_preview(['context' => $context, 'path' => 'english/future-tense']);
        $this->assertStringContainsString('The future tense', $html);
        $this->assertStringContainsString('data-path="english/future-tense"', $html);

        try {
            qtype_aitext_output_fragment_example_preview(['context' => $context, 'path' => '../../../../config']);
            $this->fail('Unknown path accepted');
        } catch (\moodle_exception $e) {
            $this->assertSame('invalidparameter', $e->errorcode);
        }

        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\required_capability_exception::class);
        qtype_aitext_output_fragment_example_preview(['context' => $context, 'path' => 'english/future-tense']);
    }

    /**
     * Folder and tag values get readable labels.
     */
    public function test_label(): void {
        $this->assertSame('Health safety', example_library::label('health-safety'));
        $this->assertSame('Gcse', example_library::label('gcse'));
    }

    /**
     * Chosen examples are imported into the category; unknown paths are reported as failures.
     */
    public function test_import(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $category = $this->getDataGenerator()->get_plugin_generator('core_question')->create_question_category();
        $sink = $this->redirectEvents();

        $results = example_library::import(
            ['english/future-tense', 'medicine/ecg-rhythm-strip', '../../../../config'],
            $category
        );

        $this->assertCount(3, $results);
        $this->assertTrue($results[0]->success);
        $this->assertSame('The future tense', $results[0]->name);
        $this->assertTrue($results[1]->success);
        $this->assertFalse($results[2]->success);
        $this->assertSame(0, $results[2]->questionid);

        $count = $DB->count_records_sql(
            'SELECT COUNT(1) FROM {question_bank_entries} WHERE questioncategoryid = ?',
            [$category->id]
        );
        $this->assertSame(2, $count);
        $question = \question_bank::load_question($results[0]->questionid);
        $this->assertSame('aitext', $question->get_type_name());

        $events = array_filter($sink->get_events(), fn($e) => $e instanceof \core\event\questions_imported);
        $this->assertCount(1, $events);
    }

    /**
     * Users who cannot add questions to the category cannot import.
     */
    public function test_import_requires_capability(): void {
        $this->resetAfterTest();
        $category = $this->getDataGenerator()->get_plugin_generator('core_question')->create_question_category();
        $this->setUser($this->getDataGenerator()->create_user());

        $this->expectException(\required_capability_exception::class);
        example_library::import(['english/future-tense'], $category);
    }
}
