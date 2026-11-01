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

use context;
use core_question\local\bank\question_edit_contexts;
use qformat_xml;
use stdClass;

/**
 * Library of example AI Text questions shipped in the examples folder.
 *
 * Each example is examples/<subject>/<slug>.xml holding one aitext question. An example is
 * identified by its path relative to the examples folder without the extension, for example
 * english/future-tense. Only paths found by scanning the folder are accepted, so a path from
 * user input can never point outside it.
 *
 * @package    qtype_aitext
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class example_library {
    /** @var string[] Tag facets, in display order. */
    const FACETS = ['subject', 'topic', 'level', 'feature'];

    /** @var stdClass[]|null Examples keyed by path, cached for the request. */
    protected static $examples = null;

    /**
     * Full path to the examples folder.
     *
     * @return string
     */
    public static function get_folder(): string {
        global $CFG;
        return $CFG->dirroot . '/question/type/aitext/examples';
    }

    /**
     * All examples, sorted by subject then name.
     *
     * Each item has path, subject, name, description, idnumber, questiontext, tags (all tag
     * strings) and facets (facet => values, e.g. 'level' => ['gcse']).
     *
     * @return stdClass[] keyed by path
     */
    public static function get_examples(): array {
        if (self::$examples !== null) {
            return self::$examples;
        }
        $folder = self::get_folder();
        $examples = [];
        foreach (glob($folder . '/*/*.xml') as $file) {
            $example = self::load_example($file);
            if ($example) {
                $example->path = substr($file, strlen($folder) + 1, -4);
                $examples[$example->path] = $example;
            }
        }
        uasort($examples, function ($a, $b) {
            return [$a->subject, $a->name] <=> [$b->subject, $b->name];
        });
        self::$examples = $examples;
        return $examples;
    }

    /**
     * Read the browse details of one example file.
     *
     * @param string $file full path to the file
     * @return stdClass|null null if the file does not hold an aitext question
     */
    protected static function load_example(string $file): ?stdClass {
        $xml = simplexml_load_file($file, null, LIBXML_NOCDATA);
        if (!$xml || !isset($xml->question[0]) || (string) $xml->question[0]['type'] !== 'aitext') {
            return null;
        }
        $question = $xml->question[0];
        $example = new stdClass();
        $example->file = $file;
        $example->subject = basename(dirname($file));
        $example->name = trim((string) $question->name->text);
        $example->description = trim((string) $question->exampledescription);
        $example->idnumber = trim((string) $question->idnumber);
        $example->questiontext = (string) $question->questiontext->text;
        $example->tags = [];
        $example->facets = array_fill_keys(self::FACETS, []);
        if (isset($question->tags)) {
            foreach ($question->tags->tag as $tag) {
                $text = trim((string) $tag->text);
                $example->tags[] = $text;
                [$facet, $value] = array_pad(explode(':', $text, 2), 2, '');
                if (isset($example->facets[$facet]) && $value !== '') {
                    $example->facets[$facet][] = $value;
                }
            }
        }
        return $example;
    }

    /**
     * Get one example by path.
     *
     * @param string $path path relative to the examples folder without extension
     * @return stdClass|null null if there is no such example
     */
    public static function get_example(string $path): ?stdClass {
        return self::get_examples()[$path] ?? null;
    }

    /**
     * Examples grouped by subject folder.
     *
     * @return stdClass[][] subject => examples
     */
    public static function get_examples_by_subject(): array {
        $grouped = [];
        foreach (self::get_examples() as $example) {
            $grouped[$example->subject][] = $example;
        }
        return $grouped;
    }

    /**
     * Paths of all examples in a subject folder.
     *
     * @param string $subject subject folder name
     * @return string[]
     */
    public static function get_subject_paths(string $subject): array {
        return array_column(self::get_examples_by_subject()[$subject] ?? [], 'path');
    }

    /**
     * Template context for the browse tree: subjects, each with its examples.
     *
     * @param \moodle_url $baseurl page URL; each example links to it with an example param
     * @param string|null $selected path of the selected example, whose subject is expanded
     * @return array
     */
    public static function get_tree(\moodle_url $baseurl, ?string $selected): array {
        $subjects = [];
        foreach (self::get_examples_by_subject() as $subject => $examples) {
            $items = [];
            $expanded = false;
            foreach ($examples as $example) {
                $isselected = $example->path === $selected;
                $expanded = $expanded || $isselected;
                $items[] = [
                    'path' => $example->path,
                    'name' => format_string($example->name),
                    'url' => (new \moodle_url($baseurl, ['example' => $example->path]))->out(false),
                    'selected' => $isselected,
                ];
            }
            $subjects[] = [
                'subject' => $subject,
                'label' => self::label($subject),
                'count' => count($items),
                'expanded' => $expanded,
                'examples' => $items,
            ];
        }
        return $subjects;
    }

    /**
     * Template context for previewing one example (qtype_aitext/examplepreview).
     *
     * Images embedded in the question text are shown as data URIs, because the example is not
     * stored in Moodle so there is no pluginfile URL for them. The example files ship with the
     * plugin, so the question text is trusted as plugin code is and not cleaned (data URIs would
     * otherwise be stripped).
     *
     * @param string $path example path
     * @return array|null null if there is no such example
     */
    public static function get_preview(string $path): ?array {
        $example = self::get_example($path);
        if (!$example) {
            return null;
        }
        $question = simplexml_load_file($example->file, null, LIBXML_NOCDATA)->question[0];

        $questiontext = (string) $question->questiontext->text;
        foreach ($question->questiontext->file as $file) {
            $name = (string) $file['name'];
            $datauri = 'data:' . mimeinfo('type', $name) . ';base64,' . trim((string) $file);
            $questiontext = str_replace('@@PLUGINFILE@@/' . $name, $datauri, $questiontext);
        }
        $trusted = ['noclean' => true, 'para' => false];

        $tags = [];
        foreach (self::FACETS as $facet) {
            foreach ($example->facets[$facet] as $value) {
                $tags[] = ['facet' => $facet, 'label' => self::label($value)];
            }
        }
        $samples = [];
        foreach ($question->sampleresponse as $sample) {
            $samples[] = ['response' => trim((string) $sample->response)];
        }
        $optional = function (\SimpleXMLElement $element) use ($trusted): string {
            $text = trim((string) $element->text);
            return $text === '' ? '' : format_text($text, FORMAT_HTML, $trusted);
        };

        return [
            'path' => $example->path,
            'subject' => $example->subject,
            'subjectlabel' => self::label($example->subject),
            'name' => format_string($example->name),
            'description' => $example->description,
            'tags' => $tags,
            'questiontext' => format_text($questiontext, FORMAT_HTML, $trusted),
            'responselines' => max(1, (int) $question->responsefieldlines),
            'defaultgrade' => format_float((float) $question->defaultgrade, -1),
            'minwordlimit' => (int) $question->minwordlimit ?: '',
            'maxwordlimit' => (int) $question->maxwordlimit ?: '',
            'aiprompt' => trim((string) $question->aiprompt),
            'markscheme' => trim((string) $question->markscheme),
            'samples' => $samples,
            'hassamples' => !empty($samples),
            'canprefill' => self::can_prefill($example),
            'generalfeedback' => $optional($question->generalfeedback),
            'graderinfo' => $optional($question->graderinfo),
        ];
    }

    /**
     * Whether an example can prefill the new question form.
     *
     * Examples with embedded images are import only, because their files would need copying
     * into the form's draft areas.
     *
     * @param stdClass $example from get_example()
     * @return bool
     */
    public static function can_prefill(stdClass $example): bool {
        return !in_array('image', $example->facets['feature']);
    }

    /**
     * An example read as question data, as the XML import would create it.
     *
     * @param string $path example path
     * @return stdClass|null null if there is no such example or it cannot prefill the form
     */
    public static function get_question_data(string $path): ?stdClass {
        global $CFG;
        $example = self::get_example($path);
        if (!$example || !self::can_prefill($example)) {
            return null;
        }
        require_once($CFG->libdir . '/questionlib.php');
        require_once($CFG->dirroot . '/question/format.php');
        require_once($CFG->dirroot . '/question/format/xml/format.php');
        $qformat = new qformat_xml();
        ob_start();
        $questions = $qformat->readquestions(file($example->file));
        ob_end_clean();
        $question = reset($questions);
        return ($question && $question->qtype === 'aitext') ? $question : null;
    }

    /**
     * Human readable label for a subject folder or tag value, e.g. health-safety => Health safety.
     *
     * @param string $value
     * @return string
     */
    public static function label(string $value): string {
        return ucfirst(str_replace('-', ' ', $value));
    }

    /**
     * Import examples into a question category.
     *
     * Unknown paths are reported as failures rather than throwing, so one bad entry does not stop
     * the others. Tags are kept. The caller must check the user may add questions to the category;
     * this is checked again here.
     *
     * @param string[] $paths example paths
     * @param stdClass $category question_categories record
     * @return stdClass[] one result per path with path, name, success and questionid
     */
    public static function import(array $paths, stdClass $category): array {
        global $CFG;
        require_once($CFG->libdir . '/questionlib.php');
        require_once($CFG->dirroot . '/question/format.php');
        require_once($CFG->dirroot . '/question/format/xml/format.php');

        $context = context::instance_by_id($category->contextid);
        require_capability('moodle/question:add', $context);
        $contexts = (new question_edit_contexts($context))->having_cap('moodle/question:add');

        $results = [];
        $imported = false;
        foreach ($paths as $path) {
            $result = (object) ['path' => $path, 'name' => $path, 'success' => false, 'questionid' => 0];
            $results[] = $result;
            $example = self::get_example($path);
            if (!$example) {
                continue;
            }
            $result->name = $example->name;

            $qformat = new qformat_xml();
            $qformat->set_display_progress(false);
            $qformat->setCategory($category);
            $qformat->setCatfromfile(false);
            $qformat->setContextfromfile(false);
            $qformat->setStoponerror(true);
            $qformat->setFilename($example->file);
            $qformat->setContexts($contexts);
            // The import process writes progress and errors straight to output.
            ob_start();
            $ok = $qformat->importpreprocess() && $qformat->importprocess() && $qformat->importpostprocess();
            ob_end_clean();
            if ($ok && count($qformat->questionids) === 1) {
                $result->success = true;
                $result->questionid = (int) $qformat->questionids[0];
                $imported = true;
            }
        }

        if ($imported) {
            \core\event\questions_imported::create([
                'contextid' => $category->contextid,
                'other' => ['format' => 'xml', 'categoryid' => $category->id],
            ])->trigger();
        }
        return $results;
    }
}
