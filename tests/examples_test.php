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

use core_question\local\bank\question_edit_contexts;
use qformat_xml;
use question_bank;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/format.php');
require_once($CFG->dirroot . '/question/format/xml/format.php');

/**
 * Checks the example questions in the examples folder follow the library conventions and import cleanly.
 *
 * Each example is examples/<subject>/<slug>.xml holding exactly one aitext question, with an
 * exampledescription element, a unique idnumber and subject:, topic:, level: and feature: tags.
 *
 * @package    qtype_aitext
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \qtype_aitext
 */
final class examples_test extends \advanced_testcase {
    /** @var string[] Allowed values for the level: tag. */
    const LEVELS = ['esl', 'gcse', 'alevel', 'university', 'professional'];

    /** @var string[] Allowed values for the feature: tag. */
    const FEATURES = ['image', 'samples', 'spellcheck', 'expertmode'];

    /**
     * Data provider listing every example file.
     *
     * @return array
     */
    public static function example_files_provider(): array {
        $root = dirname(__DIR__) . '/examples';
        $files = glob($root . '/*/*.xml');
        $cases = [];
        foreach ($files as $file) {
            $cases[substr($file, strlen($root) + 1)] = [$file];
        }
        return $cases;
    }

    /**
     * There are examples, and nothing is left loose in the top of the examples folder.
     */
    public function test_examples_folder_layout(): void {
        $root = dirname(__DIR__) . '/examples';
        $this->assertNotEmpty(glob($root . '/*/*.xml'));
        $this->assertEmpty(glob($root . '/*.xml'), 'Example files must be inside a subject folder');
        $this->assertDirectoryDoesNotExist(
            $root . '/' . \qtype_aitext\local\example_library::BANK_PREFIX,
            'This folder name is used for the paths of examples from the example category'
        );
    }

    /**
     * Each file holds one aitext question with the required metadata.
     *
     * @dataProvider example_files_provider
     * @param string $file full path to the example file
     */
    public function test_example_structure(string $file): void {
        $this->assertMatchesRegularExpression('~/[a-z0-9-]+/[a-z0-9-]+\.xml$~', $file);
        $xml = simplexml_load_file($file);
        $this->assertNotFalse($xml, 'File is not valid XML');
        $this->assertCount(1, $xml->question, 'Exactly one question per file');
        $question = $xml->question[0];
        $this->assertSame('aitext', (string) $question['type']);
        $this->assertNotSame('', trim((string) $question->exampledescription));
        $this->assertNotSame('', trim((string) $question->idnumber));

        $tags = [];
        foreach ($question->tags->tag as $tag) {
            [$facet, $value] = array_pad(explode(':', (string) $tag->text, 2), 2, '');
            $this->assertMatchesRegularExpression('~^[a-z0-9-]+$~', $value, "Bad tag value in {$tag->text}");
            $tags[$facet][] = $value;
        }
        $this->assertEqualsCanonicalizing(['subject', 'topic', 'level', 'feature'], array_keys($tags));
        foreach (['subject', 'topic', 'level'] as $facet) {
            $this->assertCount(1, $tags[$facet], "Exactly one {$facet}: tag");
        }
        $this->assertSame(basename(dirname($file)), $tags['subject'][0], 'subject: tag must match folder');
        $this->assertContains($tags['level'][0], self::LEVELS);
        foreach ($tags['feature'] as $feature) {
            $this->assertContains($feature, self::FEATURES);
        }
        $this->assertSame(
            isset($question->questiontext->file),
            in_array('image', $tags['feature']),
            'feature:image must be set exactly when the question text has embedded files'
        );
        $this->assertSame(
            isset($question->sampleresponse),
            in_array('samples', $tags['feature']),
            'feature:samples must be set exactly when there are sample responses'
        );
    }

    /**
     * Idnumbers are unique across all examples.
     */
    public function test_example_idnumbers_unique(): void {
        $idnumbers = [];
        foreach (self::example_files_provider() as [$file]) {
            $idnumbers[] = (string) simplexml_load_file($file)->question[0]->idnumber;
        }
        $this->assertSame(array_unique($idnumbers), $idnumbers);
    }

    /**
     * Each example imports as an aitext question with its tags and sample responses.
     *
     * @dataProvider example_files_provider
     * @param string $file full path to the example file
     */
    public function test_example_imports(string $file): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $category = $this->getDataGenerator()->get_plugin_generator('core_question')->create_question_category();
        $context = \context::instance_by_id($category->contextid);

        $qformat = new qformat_xml();
        $qformat->set_display_progress(false);
        $qformat->setCategory($category);
        $qformat->setCatfromfile(false);
        $qformat->setContextfromfile(false);
        $qformat->setStoponerror(true);
        $qformat->setFilename($file);
        $qformat->setContexts((new question_edit_contexts($context))->all());
        ob_start();
        $ok = $qformat->importpreprocess() && $qformat->importprocess() && $qformat->importpostprocess();
        $output = ob_get_clean();
        $this->assertTrue($ok, $output);
        $this->assertCount(1, $qformat->questionids);

        $source = simplexml_load_file($file)->question[0];
        $question = question_bank::load_question($qformat->questionids[0]);
        $this->assertSame('aitext', $question->get_type_name());
        $this->assertSame((string) $source->name->text, $question->name);
        $this->assertSame(trim((string) $source->aiprompt), trim($question->aiprompt));
        $this->assertCount(count($source->sampleresponse), $question->sampleresponses);

        $expectedtags = [];
        foreach ($source->tags->tag as $tag) {
            $expectedtags[] = (string) $tag->text;
        }
        $tags = \core_tag_tag::get_item_tags_array('core_question', 'question', $question->id);
        $this->assertEqualsCanonicalizing($expectedtags, array_values($tags));
    }
}
