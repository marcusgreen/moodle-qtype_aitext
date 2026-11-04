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
 * Library of example AI Text questions, from the examples folder and from a question category.
 *
 * A built-in example is examples/<subject>/<slug>.xml holding one aitext question, identified by
 * its path relative to the examples folder without the extension, for example
 * english/future-tense. A category example is an aitext question in the category chosen in the
 * plugin settings, identified as bank/<question bank entry id>. Only paths found by scanning the
 * folder and category are accepted, so a path from user input can never point outside them.
 *
 * Any user who can add questions may preview and import category examples; choosing the category
 * in the plugin settings is what makes its questions available to them.
 *
 * @package    qtype_aitext
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class example_library {
    /** @var string[] Tag facets, in display order. */
    const FACETS = ['subject', 'topic', 'level', 'feature'];

    /** @var string[] Facets offered in the tag filter; subjects are already the groups of the browse tree. */
    const FILTER_FACETS = ['topic', 'level', 'feature'];

    /** @var string Source of examples shipped in the examples folder. */
    const SOURCE_BUILTIN = 'builtin';

    /** @var string Source of examples held in the question category chosen in the plugin settings. */
    const SOURCE_CATEGORY = 'category';

    /** @var string First part of the path of examples from the category, followed by the question bank entry id. */
    const BANK_PREFIX = 'bank';

    /** @var string Subject of category examples that are not in a subcategory. */
    const GENERAL = 'general';

    /** @var string[] Question file areas whose files are carried in an example's XML. */
    const FILEAREAS = ['questiontext', 'generalfeedback', 'graderinfo', 'responsetemplate'];

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
     * Subject folders in the examples folder.
     *
     * @return string[]
     */
    public static function get_builtin_subjects(): array {
        return array_map('basename', glob(self::get_folder() . '/*', GLOB_ONLYDIR));
    }

    /**
     * Choices for the setting picking the category to take examples from.
     *
     * Every question category except the hidden top categories, labelled with the name of the bank
     * (its context) and the category's path within it.
     *
     * @return string[] category id => label, with 0 for none
     */
    public static function get_category_choices(): array {
        global $DB;
        $categories = $DB->get_records('question_categories', null, 'contextid, sortorder, name', 'id, name, parent, contextid');
        $choices = [];
        foreach ($categories as $category) {
            if (!$category->parent) {
                continue;
            }
            $names = [format_string($category->name)];
            $node = $category;
            while (isset($categories[$node->parent]) && $categories[$node->parent]->parent) {
                $node = $categories[$node->parent];
                array_unshift($names, format_string($node->name));
            }
            $context = context::instance_by_id($category->contextid, IGNORE_MISSING);
            if ($context) {
                $choices[$category->id] = $context->get_context_name(false, true) . ': ' . implode(' / ', $names);
            }
        }
        \core_collator::asort($choices);
        return [0 => get_string('none')] + $choices;
    }

    /**
     * The example category chosen in the plugin settings, if it still exists.
     *
     * @return stdClass|null question_categories record
     */
    public static function get_category(): ?stdClass {
        global $DB;
        $categoryid = (int) get_config('qtype_aitext', 'examplecategory');
        return $categoryid ? ($DB->get_record('question_categories', ['id' => $categoryid]) ?: null) : null;
    }

    /**
     * Links to the example category in the question bank and to the example library, for admins.
     *
     * @return string HTML, empty if no example category is set
     */
    public static function get_category_links(): string {
        $category = self::get_category();
        if (!$category) {
            return '';
        }
        $context = context::instance_by_id($category->contextid, IGNORE_MISSING);
        if (!$context) {
            return '';
        }
        $params = ['cat' => $category->id . ',' . $category->contextid];
        if ($context->contextlevel == CONTEXT_MODULE) {
            $params['cmid'] = $context->instanceid;
        } else {
            $params['courseid'] = $context->contextlevel == CONTEXT_COURSE ? $context->instanceid : SITEID;
        }
        $links = [
            \html_writer::link(
                new \moodle_url('/question/edit.php', $params),
                get_string('examplecategoryedit', 'qtype_aitext')
            ),
            \html_writer::link(
                new \moodle_url('/question/type/aitext/examples.php'),
                get_string('examplelibrarybrowse', 'qtype_aitext')
            ),
        ];
        return \html_writer::div(implode(' | ', $links), 'qtype_aitext-examplecategorylinks mt-1');
    }

    /**
     * Forget the examples loaded in this request, so changed settings or questions are picked up.
     */
    public static function reset_caches(): void {
        self::$examples = null;
    }

    /**
     * All examples, sorted by subject then name.
     *
     * These are the files in the examples folder, less any turned off in the plugin settings, and
     * the latest version of each aitext question in the category chosen in the plugin settings.
     *
     * Each item has path, source, subject, name, description, idnumber, questiontext, hasfiles,
     * tags (all tag strings) and facets (facet => values, e.g. 'level' => ['gcse']). Built-in
     * examples also have file; category examples have questionid.
     *
     * @return stdClass[] keyed by path
     */
    public static function get_examples(): array {
        if (self::$examples !== null) {
            return self::$examples;
        }
        if (!self::is_enabled()) {
            self::$examples = [];
            return [];
        }
        $examples = self::load_builtin() + self::load_category();
        uasort($examples, function ($a, $b) {
            return [$a->subject, $a->name] <=> [$b->subject, $b->name];
        });
        self::$examples = $examples;
        return $examples;
    }

    /**
     * Whether the example library is turned on in the plugin settings.
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        return self::config_on('enableexamplelibrary');
    }

    /**
     * Whether a checkbox setting is on, counting it as on until it has been saved, as on a site
     * that has not run the upgrade yet.
     *
     * @param string $name setting name
     * @return bool
     */
    protected static function config_on(string $name): bool {
        $value = get_config('qtype_aitext', $name);
        return $value === false || (bool) $value;
    }

    /**
     * Whether there are any examples to show, so the library is worth linking to.
     *
     * @return bool
     */
    public static function has_examples(): bool {
        return !empty(self::get_examples());
    }

    /**
     * Examples from the files in the examples folder, unless turned off in the plugin settings.
     *
     * @return stdClass[] keyed by path
     */
    protected static function load_builtin(): array {
        if (!self::config_on('examplebuiltin')) {
            return [];
        }
        $hidden = explode(',', (string) get_config('qtype_aitext', 'examplehiddensubjects'));
        return array_filter(self::get_builtin_examples(), fn($example) => !in_array($example->subject, $hidden));
    }

    /**
     * Every example in the examples folder, whatever the plugin settings.
     *
     * Only for copying the built-in examples into the example category; anything shown to users
     * goes through get_examples().
     *
     * @return stdClass[] keyed by path
     */
    public static function get_builtin_examples(): array {
        $folder = self::get_folder();
        $examples = [];
        foreach (glob($folder . '/*/*.xml') as $file) {
            $example = self::load_example($file);
            if ($example) {
                $example->path = substr($file, strlen($folder) + 1, -4);
                $examples[$example->path] = $example;
            }
        }
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
        $example->source = self::SOURCE_BUILTIN;
        $example->file = $file;
        $example->subject = basename(dirname($file));
        $example->name = trim((string) $question->name->text);
        $example->description = trim((string) $question->exampledescription);
        $example->idnumber = trim((string) $question->idnumber);
        $example->questiontext = (string) $question->questiontext->text;
        $example->hasfiles = !empty($question->xpath('.//file'));
        $tags = [];
        if (isset($question->tags)) {
            foreach ($question->tags->tag as $tag) {
                $tags[] = (string) $tag->text;
            }
        }
        self::add_tags($example, $tags);
        return $example;
    }

    /**
     * Examples from the question category chosen in the plugin settings.
     *
     * The latest version of each aitext question in the category or its subcategories is used, if
     * that version is ready. Each subcategory directly below the chosen one is a subject, taking in
     * any subcategories of its own; questions in the chosen category itself are under General.
     *
     * @return stdClass[] keyed by path
     */
    protected static function load_category(): array {
        global $CFG, $DB;
        $categoryid = (int) get_config('qtype_aitext', 'examplecategory');
        if (!$categoryid || !$DB->record_exists('question_categories', ['id' => $categoryid])) {
            return [];
        }
        require_once($CFG->libdir . '/questionlib.php');
        $categoryids = question_categorylist($categoryid);
        $categories = $DB->get_records_list('question_categories', 'id', $categoryids, '', 'id, name, parent');

        // Subject of each category: the name of its ancestor directly below the chosen category.
        $subjects = [];
        foreach ($categories as $category) {
            $node = $category;
            while ($node->id != $categoryid && $node->parent != $categoryid && isset($categories[$node->parent])) {
                $node = $categories[$node->parent];
            }
            $subjects[$category->id] = $node->id == $categoryid ? self::GENERAL : self::slug($node->name);
        }

        [$insql, $params] = $DB->get_in_or_equal(array_keys($categories), SQL_PARAMS_NAMED);
        $params += ['qtype' => 'aitext', 'ready' => \core_question\local\bank\question_version_status::QUESTION_STATUS_READY];
        $questions = $DB->get_records_sql(
            "SELECT q.id, q.name, q.questiontext, qbe.id AS entryid, qbe.idnumber, qbe.questioncategoryid
               FROM {question_bank_entries} qbe
               JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
               JOIN {question} q ON q.id = qv.questionid
              WHERE qbe.questioncategoryid $insql
                AND q.qtype = :qtype
                AND qv.status = :ready
                AND qv.version = (SELECT MAX(v.version)
                                    FROM {question_versions} v
                                   WHERE v.questionbankentryid = qbe.id)",
            $params
        );
        if (!$questions) {
            return [];
        }

        [$qsql, $qparams] = $DB->get_in_or_equal(array_keys($questions), SQL_PARAMS_NAMED);
        [$asql, $aparams] = $DB->get_in_or_equal(self::FILEAREAS, SQL_PARAMS_NAMED, 'area');
        $withfiles = $DB->get_fieldset_sql(
            "SELECT DISTINCT itemid
               FROM {files}
              WHERE component = 'question' AND filearea $asql AND itemid $qsql AND filename <> '.'",
            $aparams + $qparams
        );
        $tags = \core_tag_tag::get_items_tags('core_question', 'question', array_keys($questions));

        $examples = [];
        foreach ($questions as $question) {
            $example = new stdClass();
            $example->source = self::SOURCE_CATEGORY;
            $example->path = self::BANK_PREFIX . '/' . $question->entryid;
            $example->questionid = (int) $question->id;
            $example->subject = $subjects[$question->questioncategoryid];
            $example->name = trim($question->name);
            $example->description = '';
            $example->idnumber = (string) $question->idnumber;
            $example->questiontext = $question->questiontext;
            $example->hasfiles = in_array($question->id, $withfiles);
            self::add_tags($example, array_map(fn($tag) => $tag->rawname, $tags[$question->id] ?? []));
            $examples[$example->path] = $example;
        }
        return $examples;
    }

    /**
     * Set an example's tags, and its facets from tags of the form facet:value.
     *
     * @param stdClass $example
     * @param string[] $tags
     */
    protected static function add_tags(stdClass $example, array $tags): void {
        $example->tags = [];
        $example->facets = array_fill_keys(self::FACETS, []);
        foreach ($tags as $tag) {
            $text = trim($tag);
            $example->tags[] = $text;
            [$facet, $value] = array_pad(explode(':', $text, 2), 2, '');
            if (isset($example->facets[$facet]) && $value !== '') {
                $example->facets[$facet][] = $value;
            }
        }
    }

    /**
     * Subject key for a category name, matching the style of the example folder names, e.g.
     * Health & Safety => health-safety, so a category and a folder for the same subject are merged.
     *
     * @param string $name
     * @return string
     */
    public static function slug(string $name): string {
        $slug = trim(preg_replace('~[^a-z0-9]+~', '-', \core_text::strtolower($name)), '-');
        return $slug === '' ? self::GENERAL : $slug;
    }

    /**
     * The example's question as Moodle XML, as a file holding just that question.
     *
     * Category examples are exported, so any files are embedded in the XML as they are in the
     * example files.
     *
     * @param stdClass $example from get_example()
     * @return string
     */
    public static function get_xml(stdClass $example): string {
        global $CFG;
        if ($example->source === self::SOURCE_BUILTIN) {
            return file_get_contents($example->file);
        }
        require_once($CFG->libdir . '/questionlib.php');
        require_once($CFG->dirroot . '/question/format.php');
        require_once($CFG->dirroot . '/question/format/xml/format.php');
        $questiondata = \question_bank::load_question_data($example->questionid);
        $qformat = new qformat_xml();
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<quiz>\n" . $qformat->writequestion($questiondata) . "</quiz>\n";
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
                    'fromcategory' => $example->source === self::SOURCE_CATEGORY,
                    'facets' => json_encode(self::facet_keys($example)),
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
     * Template context for the tag filter: each filter facet with the values used by any example.
     *
     * Facets with no values are left out. Each value has a key (facet:value) matching the facets
     * listed on the examples in get_tree().
     *
     * @return array
     */
    public static function get_facet_filters(): array {
        $values = array_fill_keys(self::FILTER_FACETS, []);
        foreach (self::get_examples() as $example) {
            foreach (self::FILTER_FACETS as $facet) {
                foreach ($example->facets[$facet] as $value) {
                    $values[$facet][$value] = true;
                }
            }
        }
        $filters = [];
        foreach ($values as $facet => $facetvalues) {
            if (!$facetvalues) {
                continue;
            }
            ksort($facetvalues);
            $filters[] = [
                'facet' => $facet,
                'label' => get_string('examplesfacet_' . $facet, 'qtype_aitext'),
                'values' => array_map(fn($value) => [
                    'key' => $facet . ':' . $value,
                    'label' => self::label($value),
                ], array_keys($facetvalues)),
            ];
        }
        return $filters;
    }

    /**
     * An example's facet values as facet:value keys.
     *
     * @param stdClass $example
     * @return string[]
     */
    protected static function facet_keys(stdClass $example): array {
        $keys = [];
        foreach ($example->facets as $facet => $values) {
            foreach ($values as $value) {
                $keys[] = $facet . ':' . $value;
            }
        }
        return $keys;
    }

    /**
     * Template context for previewing one example (qtype_aitext/examplepreview).
     *
     * Embedded images are shown as data URIs. A built-in example is not stored in Moodle, so has no
     * pluginfile URL, and the user may have no access to the bank holding a category example.
     * Built-in examples ship with the plugin, so their text is trusted as plugin code is and not
     * cleaned. Category examples were written by teachers, so their text is cleaned, before the
     * data URIs are put in as cleaning would strip them.
     *
     * @param string $path example path
     * @return array|null null if there is no such example
     */
    public static function get_preview(string $path): ?array {
        $example = self::get_example($path);
        if (!$example) {
            return null;
        }
        $question = simplexml_load_string(self::get_xml($example), null, LIBXML_NOCDATA)->question[0];
        $trusted = $example->source === self::SOURCE_BUILTIN;
        // Text of a question area as HTML, with its files embedded as data URIs.
        $html = function (\SimpleXMLElement $element) use ($trusted): string {
            $text = trim((string) $element->text);
            if ($text === '') {
                return '';
            }
            if (!$trusted) {
                $text = clean_text($text, FORMAT_HTML);
            }
            foreach ($element->file as $file) {
                $name = (string) $file['name'];
                $datauri = 'data:' . mimeinfo('type', $name) . ';base64,' . trim((string) $file);
                $text = str_replace('@@PLUGINFILE@@/' . rawurlencode($name), $datauri, $text);
                $text = str_replace('@@PLUGINFILE@@/' . $name, $datauri, $text);
            }
            return format_text($text, FORMAT_HTML, ['noclean' => true, 'para' => false]);
        };

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
        return [
            'path' => $example->path,
            'fromcategory' => $example->source === self::SOURCE_CATEGORY,
            'subject' => $example->subject,
            'subjectlabel' => self::label($example->subject),
            'name' => format_string($example->name),
            'description' => $example->description,
            'tags' => $tags,
            'questiontext' => $html($question->questiontext),
            'responselines' => max(1, (int) $question->responsefieldlines),
            'defaultgrade' => format_float((float) $question->defaultgrade, -1),
            'minwordlimit' => (int) $question->minwordlimit ?: '',
            'maxwordlimit' => (int) $question->maxwordlimit ?: '',
            'aiprompt' => trim((string) $question->aiprompt),
            'markscheme' => trim((string) $question->markscheme),
            'samples' => $samples,
            'hassamples' => !empty($samples),
            'canprefill' => self::can_prefill($example),
            'generalfeedback' => $html($question->generalfeedback),
            'graderinfo' => $html($question->graderinfo),
        ];
    }

    /**
     * Whether an example can prefill the new question form.
     *
     * Examples with embedded images or other files are import only, because their files would need
     * copying into the form's draft areas.
     *
     * @param stdClass $example from get_example()
     * @return bool
     */
    public static function can_prefill(stdClass $example): bool {
        return !$example->hasfiles && !in_array('image', $example->facets['feature']);
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
        $questions = $qformat->readquestions(preg_split('~(?<=\n)~', self::get_xml($example)));
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
        $examples = [];
        foreach ($paths as $path) {
            $examples[$path] = self::get_example($path);
        }
        return self::import_examples($examples, $category);
    }

    /**
     * Import examples into a question category.
     *
     * @param (stdClass|null)[] $examples examples keyed by path; null for a path that was not found
     * @param stdClass $category question_categories record
     * @return stdClass[] one result per example, as for import()
     */
    protected static function import_examples(array $examples, stdClass $category): array {
        global $CFG;
        require_once($CFG->libdir . '/questionlib.php');
        require_once($CFG->dirroot . '/question/format.php');
        require_once($CFG->dirroot . '/question/format/xml/format.php');

        $context = context::instance_by_id($category->contextid);
        require_capability('moodle/question:add', $context);
        $contexts = (new question_edit_contexts($context))->having_cap('moodle/question:add');

        $results = [];
        $imported = false;
        foreach ($examples as $path => $example) {
            $result = (object) ['path' => $path, 'name' => $path, 'success' => false, 'questionid' => 0];
            $results[] = $result;
            if (!$example) {
                continue;
            }
            $result->name = $example->name;
            $file = $example->file ?? self::write_temp_file($example);

            $qformat = new qformat_xml();
            $qformat->set_display_progress(false);
            $qformat->setCategory($category);
            $qformat->setCatfromfile(false);
            $qformat->setContextfromfile(false);
            $qformat->setStoponerror(true);
            $qformat->setFilename($file);
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

    /**
     * Copy every built-in example into the example category, so they can be edited there.
     *
     * Each subject is copied into a subcategory named after it, created if needed, so it is still a
     * subject in the library. Examples whose idnumber is already in that subcategory are skipped, so
     * copying again only adds what is missing. Subjects hidden in the settings are copied too.
     *
     * @param stdClass $category question_categories record of the example category
     * @return stdClass counts of copied, skipped and failed examples
     */
    public static function copy_builtin(stdClass $category): stdClass {
        global $DB;
        $counts = (object) ['copied' => 0, 'skipped' => 0, 'failed' => 0];
        $bysubject = [];
        foreach (self::get_builtin_examples() as $path => $example) {
            $bysubject[$example->subject][$path] = $example;
        }
        foreach ($bysubject as $subject => $examples) {
            $subcategory = self::get_subcategory($category, self::label($subject));
            $existing = $DB->get_fieldset_select(
                'question_bank_entries',
                'idnumber',
                'questioncategoryid = ? AND idnumber IS NOT NULL',
                [$subcategory->id]
            );
            $examples = array_filter($examples, fn($example) => !in_array($example->idnumber, $existing));
            $counts->skipped += count($bysubject[$subject]) - count($examples);
            foreach (self::import_examples($examples, $subcategory) as $result) {
                $result->success ? $counts->copied++ : $counts->failed++;
            }
        }
        self::reset_caches();
        return $counts;
    }

    /**
     * A subcategory of a category with the given name, created if there is none.
     *
     * @param stdClass $parent question_categories record
     * @param string $name
     * @return stdClass question_categories record
     */
    protected static function get_subcategory(stdClass $parent, string $name): stdClass {
        global $DB;
        $subcategory = $DB->get_record('question_categories', ['parent' => $parent->id, 'name' => $name]);
        if ($subcategory) {
            return $subcategory;
        }
        $subcategory = (object) [
            'name' => $name,
            'contextid' => $parent->contextid,
            'info' => '',
            'infoformat' => FORMAT_HTML,
            'parent' => $parent->id,
            'sortorder' => 999,
            'stamp' => make_unique_id_code(),
        ];
        $subcategory->id = $DB->insert_record('question_categories', $subcategory);
        \core\event\question_category_created::create_from_question_category_instance(
            $subcategory,
            context::instance_by_id($parent->contextid)
        )->trigger();
        return $subcategory;
    }

    /**
     * Write an example's XML to a file for the XML import to read.
     *
     * @param stdClass $example from get_example()
     * @return string full path to the file, removed at the end of the request
     */
    protected static function write_temp_file(stdClass $example): string {
        $file = make_request_directory() . '/example.xml';
        file_put_contents($file, self::get_xml($example));
        return $file;
    }
}
