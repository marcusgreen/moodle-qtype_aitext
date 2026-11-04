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
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        example_library::reset_caches();
    }

    #[\Override]
    protected function tearDown(): void {
        example_library::reset_caches();
        parent::tearDown();
    }

    /**
     * Make a category of example questions and choose it in the plugin settings.
     *
     * The chosen category holds one question; subcategory Biology holds one and has a subcategory
     * Cells holding another; subcategory Health & Safety holds one.
     *
     * @return \stdClass[] questions keyed by short name: general, heart, cells, fire
     */
    protected function make_category_library(): array {
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $top = $generator->create_question_category(['name' => 'Examples']);
        $biology = $generator->create_question_category(['name' => 'Biology', 'parent' => $top->id]);
        $cells = $generator->create_question_category(['name' => 'Cells', 'parent' => $biology->id]);
        $safety = $generator->create_question_category(['name' => 'Health & Safety', 'parent' => $top->id]);
        $make = function (\stdClass $category, string $name, array $tags) use ($generator): \stdClass {
            $question = $generator->create_question('aitext', 'plain', ['category' => $category->id, 'name' => $name]);
            foreach ($tags as $tag) {
                $generator->create_question_tag(['questionid' => $question->id, 'tag' => $tag]);
            }
            return $question;
        };
        $questions = [
            'general' => $make($top, 'Study skills', ['level:gcse']),
            'heart' => $make($biology, 'The heart in detail', ['level:gcse', 'topic:circulation']),
            'cells' => $make($cells, 'Cell structure', ['level:alevel', 'topic:cell biology']),
            'fire' => $make($safety, 'Fire drill', ['level:professional']),
        ];
        set_config('examplecategory', $top->id, 'qtype_aitext');
        example_library::reset_caches();
        return $questions;
    }

    /**
     * Path of a question in the example category.
     *
     * @param \stdClass $question
     * @return string
     */
    protected function bank_path(\stdClass $question): string {
        global $DB;
        $entryid = $DB->get_field('question_versions', 'questionbankentryid', ['questionid' => $question->id]);
        return 'bank/' . $entryid;
    }
    /**
     * Every example file is listed, keyed by its path, with its metadata.
     */
    public function test_get_examples(): void {
        $examples = example_library::get_examples();
        $this->assertCount(count(glob(example_library::get_folder() . '/*/*.xml')), $examples);

        $example = example_library::get_example('english/future-tense');
        $this->assertSame('english/future-tense', $example->path);
        $this->assertSame(example_library::SOURCE_BUILTIN, $example->source);
        $this->assertFalse($example->hasfiles);
        $this->assertTrue(example_library::get_example('medicine/ecg-rhythm-strip')->hasfiles);
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
        $this->assertFalse($preview['canprefill']);
        $this->assertTrue(example_library::get_preview('english/future-tense')['canprefill']);
    }

    /**
     * Examples without images can prefill the new question form, as imported question data.
     */
    public function test_get_question_data(): void {
        $this->assertTrue(example_library::can_prefill(example_library::get_example('english/future-tense')));
        $this->assertFalse(example_library::can_prefill(example_library::get_example('medicine/ecg-rhythm-strip')));
        $this->assertNull(example_library::get_question_data('medicine/ecg-rhythm-strip'));
        $this->assertNull(example_library::get_question_data('../../../../config'));

        $question = example_library::get_question_data('english/future-tense');
        $this->assertSame('aitext', $question->qtype);
        $this->assertSame('The future tense', $question->name);
        $this->assertEquals(3, $question->defaultmark);
        $this->assertSame('plain', $question->responseformat);
        $this->assertStringContainsString('future tense', $question->aiprompt);
        $this->assertCount(3, $question->sampleresponses);
        $this->assertContains('level:esl', $question->tags);
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

    /**
     * Questions in the example category are listed with the built-in examples, under the subject of
     * their top-level subcategory, or General.
     */
    public function test_category_examples(): void {
        $this->resetAfterTest();
        $questions = $this->make_category_library();
        $builtin = count(glob(example_library::get_folder() . '/*/*.xml'));
        $this->assertCount($builtin + 4, example_library::get_examples());

        $heart = example_library::get_example($this->bank_path($questions['heart']));
        $this->assertSame(example_library::SOURCE_CATEGORY, $heart->source);
        $this->assertSame('biology', $heart->subject);
        $this->assertSame('The heart in detail', $heart->name);
        $this->assertSame((int) $questions['heart']->id, $heart->questionid);
        $this->assertSame(['circulation'], $heart->facets['topic']);
        $this->assertFalse($heart->hasfiles);

        // A deeper subcategory is part of its top-level subcategory's subject.
        $cells = example_library::get_example($this->bank_path($questions['cells']));
        $this->assertSame('biology', $cells->subject);
        $this->assertSame(['cell biology'], $cells->facets['topic']);
        $this->assertSame('general', example_library::get_example($this->bank_path($questions['general']))->subject);
        $this->assertSame('health-safety', example_library::get_example($this->bank_path($questions['fire']))->subject);

        // Category subjects are merged with built-in subjects of the same name.
        $biology = array_column(example_library::get_examples_by_subject()['biology'], 'source');
        $this->assertContains(example_library::SOURCE_BUILTIN, $biology);
        $this->assertContains(example_library::SOURCE_CATEGORY, $biology);
        $this->assertContains($this->bank_path($questions['heart']), example_library::get_subject_paths('biology'));
    }

    /**
     * Only the latest version of a question is used, and only if it is ready.
     */
    public function test_category_latest_ready_version(): void {
        $this->resetAfterTest();
        $questions = $this->make_category_library();
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $path = $this->bank_path($questions['heart']);

        $generator->update_question($questions['heart'], null, ['name' => 'The heart, version 2']);
        example_library::reset_caches();
        $this->assertSame('The heart, version 2', example_library::get_example($path)->name);

        $generator->update_question(
            $questions['fire'],
            null,
            ['status' => \core_question\local\bank\question_version_status::QUESTION_STATUS_HIDDEN]
        );
        example_library::reset_caches();
        $this->assertNull(example_library::get_example($this->bank_path($questions['fire'])));
    }

    /**
     * Without a category, or when it has been deleted, only the built-in examples are listed.
     */
    public function test_category_missing(): void {
        global $DB;
        $this->resetAfterTest();
        $questions = $this->make_category_library();
        $builtin = count(glob(example_library::get_folder() . '/*/*.xml'));

        set_config('examplecategory', 0, 'qtype_aitext');
        example_library::reset_caches();
        $this->assertCount($builtin, example_library::get_examples());
        $this->assertNull(example_library::get_example($this->bank_path($questions['heart'])));

        set_config('examplecategory', $DB->get_field_sql('SELECT MAX(id) + 1 FROM {question_categories}'), 'qtype_aitext');
        example_library::reset_caches();
        $this->assertCount($builtin, example_library::get_examples());
    }

    /**
     * Built-in examples can be turned off altogether or by subject.
     */
    public function test_builtin_settings(): void {
        $this->resetAfterTest();
        $questions = $this->make_category_library();

        set_config('examplehiddensubjects', 'english,medicine', 'qtype_aitext');
        example_library::reset_caches();
        $this->assertNull(example_library::get_example('english/future-tense'));
        $this->assertNull(example_library::get_preview('medicine/ecg-rhythm-strip'));
        $this->assertSame([], example_library::get_subject_paths('english'));
        $this->assertNotNull(example_library::get_example('maths/range-and-mode'));

        set_config('examplebuiltin', 0, 'qtype_aitext');
        example_library::reset_caches();
        $examples = example_library::get_examples();
        $this->assertCount(4, $examples);
        $this->assertSame([example_library::SOURCE_CATEGORY], array_unique(array_column($examples, 'source')));
        $this->assertNotNull(example_library::get_example($this->bank_path($questions['heart'])));
    }

    /**
     * Category questions are previewed with their text cleaned and their images as data URIs.
     */
    public function test_category_preview(): void {
        global $DB;
        $this->resetAfterTest();
        $questions = $this->make_category_library();
        $question = $questions['heart'];
        $questiontext = '<p>Label the diagram.</p><img src="@@PLUGINFILE@@/heart%20diagram.png" alt="Heart">' .
            '<script>alert(1)</script><p onclick="alert(2)">Then explain.</p>';
        $DB->set_field('question', 'questiontext', $questiontext, ['id' => $question->id]);
        get_file_storage()->create_file_from_string([
            'contextid' => $DB->get_field('question_categories', 'contextid', ['id' => $question->category]),
            'component' => 'question',
            'filearea' => 'questiontext',
            'itemid' => $question->id,
            'filepath' => '/',
            'filename' => 'heart diagram.png',
        ], 'PNGDATA');
        \question_bank::notify_question_edited($question->id);
        example_library::reset_caches();

        $path = $this->bank_path($question);
        $preview = example_library::get_preview($path);
        $this->assertTrue($preview['fromcategory']);
        $this->assertSame('The heart in detail', $preview['name']);
        $this->assertSame('biology', $preview['subject']);
        $this->assertStringContainsString('Label the diagram.', $preview['questiontext']);
        $this->assertStringContainsString('src="data:image/png;base64,' . base64_encode('PNGDATA') . '"', $preview['questiontext']);
        $this->assertStringNotContainsString('<script', $preview['questiontext']);
        $this->assertStringNotContainsString('onclick', $preview['questiontext']);
        $this->assertSame('Evaluate this', $preview['aiprompt']);
        $this->assertSame([['response' => 'response1']], $preview['samples']);

        // A question with files can be imported but not used to prefill the form.
        $this->assertTrue(example_library::get_example($path)->hasfiles);
        $this->assertFalse($preview['canprefill']);
        $this->assertNull(example_library::get_question_data($path));
        $this->assertFalse(example_library::get_preview('english/future-tense')['fromcategory']);
    }

    /**
     * Category questions prefill the new question form as if imported.
     */
    public function test_category_question_data(): void {
        $this->resetAfterTest();
        $questions = $this->make_category_library();

        $question = example_library::get_question_data($this->bank_path($questions['heart']));
        $this->assertSame('aitext', $question->qtype);
        $this->assertSame('The heart in detail', $question->name);
        $this->assertSame('Evaluate this', $question->aiprompt);
        $this->assertSame(['response1'], $question->sampleresponses);
        $this->assertEqualsCanonicalizing(['level:gcse', 'topic:circulation'], $question->tags);
    }

    /**
     * Category questions are copied into the destination category with their tags and files.
     */
    public function test_category_import(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $questions = $this->make_category_library();
        $question = $questions['heart'];
        $DB->set_field('question', 'questiontext', '<img src="@@PLUGINFILE@@/heart.png" alt="">', ['id' => $question->id]);
        get_file_storage()->create_file_from_string([
            'contextid' => $DB->get_field('question_categories', 'contextid', ['id' => $question->category]),
            'component' => 'question', 'filearea' => 'questiontext',
            'itemid' => $question->id, 'filepath' => '/', 'filename' => 'heart.png',
        ], 'PNGDATA');
        \question_bank::notify_question_edited($question->id);
        example_library::reset_caches();
        $destination = $this->getDataGenerator()->get_plugin_generator('core_question')->create_question_category();

        $results = example_library::import([$this->bank_path($question), $this->bank_path($questions['fire'])], $destination);

        $this->assertTrue($results[0]->success);
        $this->assertSame('The heart in detail', $results[0]->name);
        $this->assertTrue($results[1]->success);
        $copy = \question_bank::load_question($results[0]->questionid);
        $this->assertNotEquals($question->id, $copy->id);
        $this->assertSame((int) $destination->id, (int) $copy->category);
        $this->assertSame('Evaluate this', $copy->aiprompt);
        $tags = \core_tag_tag::get_item_tags_array('core_question', 'question', $copy->id);
        $this->assertEqualsCanonicalizing(['level:gcse', 'topic:circulation'], array_values($tags));
        $files = get_file_storage()->get_area_files($destination->contextid, 'question', 'questiontext', $copy->id, '', false);
        $this->assertCount(1, $files);
        $this->assertSame('PNGDATA', reset($files)->get_content());
        // The source question is left as it was.
        $this->assertSame(1, $DB->count_records('question_bank_entries', ['questioncategoryid' => $question->category]));
    }

    /**
     * The tag filter lists each facet's values from all examples, including the category's.
     */
    public function test_get_facet_filters(): void {
        $this->resetAfterTest();
        $this->make_category_library();
        $filters = array_column(example_library::get_facet_filters(), null, 'facet');

        $this->assertSame(['topic', 'level', 'feature'], array_keys($filters));
        $this->assertSame('Level', $filters['level']['label']);
        $levels = array_column($filters['level']['values'], 'label', 'key');
        $this->assertSame('Esl', $levels['level:esl']);
        $this->assertSame('Professional', $levels['level:professional']);
        $this->assertArrayHasKey('topic:cell biology', array_column($filters['topic']['values'], 'label', 'key'));

        $baseurl = new \moodle_url('/question/type/aitext/examples.php');
        $items = array_merge(...array_column(example_library::get_tree($baseurl, null), 'examples'));
        $items = array_column($items, null, 'path');
        $this->assertContains('level:esl', json_decode($items['english/future-tense']['facets']));
        $this->assertFalse($items['english/future-tense']['fromcategory']);
    }

    /**
     * The category setting offers every category but the top ones, labelled with their bank and path.
     */
    public function test_get_category_choices(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $top = $generator->create_question_category(['name' => 'Examples']);
        $sub = $generator->create_question_category(['name' => 'Biology', 'parent' => $top->id]);

        $choices = example_library::get_category_choices();
        $this->assertSame(get_string('none'), $choices[0]);
        $this->assertStringEndsWith(': Examples / Biology', $choices[$sub->id]);
        $this->assertStringEndsWith(': Examples', $choices[$top->id]);
        $this->assertArrayNotHasKey($top->parent, $choices);
    }

    /**
     * Category names become subject keys in the style of the example folders.
     */
    public function test_slug(): void {
        $this->assertSame('health-safety', example_library::slug('Health & Safety'));
        $this->assertSame('biology', example_library::slug(' Biology '));
        $this->assertSame('general', example_library::slug('!!!'));
    }

    /**
     * Turning the library off hides every example, built-in and from the category.
     */
    public function test_enable_example_library(): void {
        $this->resetAfterTest();
        $questions = $this->make_category_library();
        $this->assertTrue(example_library::has_examples());

        set_config('enableexamplelibrary', 0, 'qtype_aitext');
        example_library::reset_caches();
        $this->assertFalse(example_library::is_enabled());
        $this->assertFalse(example_library::has_examples());
        $this->assertNull(example_library::get_example('english/future-tense'));
        $this->assertNull(example_library::get_example($this->bank_path($questions['heart'])));
        $this->assertNull(example_library::get_question_data('english/future-tense'));
        $this->assertSame([], example_library::get_facet_filters());
        // The built-in examples can still be copied by an admin.
        $this->assertCount(count(glob(example_library::get_folder() . '/*/*.xml')), example_library::get_builtin_examples());
    }

    /**
     * With built-in examples turned off and no category there is nothing to show.
     */
    public function test_has_examples(): void {
        $this->resetAfterTest();
        $this->assertTrue(example_library::has_examples());
        set_config('examplebuiltin', 0, 'qtype_aitext');
        example_library::reset_caches();
        $this->assertFalse(example_library::has_examples());
    }

    /**
     * Built-in examples are copied into a subcategory per subject, including hidden subjects, once.
     */
    public function test_copy_builtin(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->make_category_library();
        $top = $DB->get_record('question_categories', ['id' => get_config('qtype_aitext', 'examplecategory')]);
        set_config('examplehiddensubjects', 'medicine', 'qtype_aitext');
        $builtin = example_library::get_builtin_examples();

        $counts = example_library::copy_builtin($top);
        $this->assertEquals((object) ['copied' => count($builtin), 'skipped' => 0, 'failed' => 0], $counts);

        // The existing Biology subcategory is reused; others are created and named after their subject.
        $this->assertSame(1, $DB->count_records('question_categories', ['parent' => $top->id, 'name' => 'Biology']));
        $safety = $DB->get_record('question_categories', ['parent' => $top->id, 'name' => 'Health safety'], '*', MUST_EXIST);
        $this->assertSame(1, $DB->count_records('question_bank_entries', ['questioncategoryid' => $safety->id]));

        // Copies are listed under the same subjects as the built-in examples they came from.
        set_config('examplebuiltin', 0, 'qtype_aitext');
        example_library::reset_caches();
        $examples = example_library::get_examples();
        $this->assertCount(count($builtin) + 4, $examples);
        $copies = array_filter($examples, fn($example) => $example->idnumber === 'eng-future-tense');
        $this->assertSame('english', reset($copies)->subject);
        $this->assertContains('level:esl', reset($copies)->tags);
        $ecg = array_filter($examples, fn($example) => $example->subject === 'medicine');
        $this->assertCount(2, $ecg);

        // Copying again adds nothing.
        $counts = example_library::copy_builtin($top);
        $this->assertEquals((object) ['copied' => 0, 'skipped' => count($builtin), 'failed' => 0], $counts);
    }

    /**
     * Once a category is set, admins get links to it in the question bank and to the library.
     */
    public function test_get_category_links(): void {
        global $DB;
        $this->resetAfterTest();
        $this->assertSame('', example_library::get_category_links());

        $this->make_category_library();
        $category = example_library::get_category();
        $cmid = \context::instance_by_id($category->contextid)->instanceid;
        $links = example_library::get_category_links();
        $this->assertStringContainsString('/question/edit.php?cat=' . $category->id . '%2C' . $category->contextid, $links);
        $this->assertStringContainsString('cmid=' . $cmid, $links);
        $this->assertStringContainsString('/question/type/aitext/examples.php', $links);

        $DB->delete_records('question_categories', ['id' => $category->id]);
        $this->assertNull(example_library::get_category());
        $this->assertSame('', example_library::get_category_links());
    }
}
