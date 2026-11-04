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
 * Browse the example AI Text questions by subject, preview them and import them into a category.
 *
 * @package    qtype_aitext
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/questionlib.php');

use core_question\local\bank\question_edit_contexts;
use qtype_aitext\form\examples_form;
use qtype_aitext\local\example_library;

$cmid = optional_param('cmid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$categoryparam = optional_param('category', '', PARAM_SEQUENCE);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$selected = optional_param('example', '', PARAM_PATH);

$urlparams = [];
if ($cmid) {
    $cm = get_coursemodule_from_id(false, $cmid, 0, false, MUST_EXIST);
    require_login($cm->course, false, $cm);
    $context = context_module::instance($cmid);
    $urlparams['cmid'] = $cmid;
} else if ($courseid) {
    require_login($courseid);
    $context = context_course::instance($courseid);
    $urlparams['courseid'] = $courseid;
} else {
    require_login();
    $context = context_system::instance();
}
require_capability('moodle/question:add', $context);
$contexts = (new question_edit_contexts($context))->having_cap('moodle/question:add');

if ($returnurl) {
    $urlparams['returnurl'] = $returnurl;
    $backurl = new moodle_url($returnurl);
} else {
    $backurl = new moodle_url('/question/edit.php', $urlparams);
}
if (!example_library::get_example($selected)) {
    $selected = '';
}

$baseurl = new moodle_url('/question/type/aitext/examples.php', $urlparams);
$url = new moodle_url($baseurl, $selected ? ['example' => $selected] : []);
$title = get_string('examplelibrary', 'qtype_aitext');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$form = new examples_form($baseurl, ['contexts' => $contexts, 'example' => $selected]);
$defaults = ['cmid' => $cmid, 'courseid' => $courseid, 'returnurl' => $returnurl, 'example' => $selected];
if ($categoryparam) {
    $defaults['category'] = $categoryparam;
}
$form->set_data($defaults);

$results = [];
if ($data = $form->get_data()) {
    [$categoryid] = explode(',', $data->category);
    $category = $DB->get_record('question_categories', ['id' => $categoryid], '*', MUST_EXIST);
    // The category must be one offered to the user, not just any id posted.
    $allowed = array_map(fn($c) => $c->id, $contexts);
    if (!in_array($category->contextid, $allowed)) {
        throw new moodle_exception('nopermissions', 'error', '', 'moodle/question:add');
    }
    if (!empty($data->prefill)) {
        if (!example_library::can_prefill(example_library::get_example($data->example))) {
            throw new moodle_exception('invalidparameter', 'debug');
        }
        // Open the new question form filled in from the example; nothing is saved until the form is.
        redirect(new moodle_url('/question/bank/editquestion/question.php', [
            'qtype' => 'aitext',
            'category' => $category->id,
            'example' => $data->example,
            'returnurl' => $returnurl ?: $backurl->out_as_local_url(false),
        ] + array_intersect_key($urlparams, ['cmid' => 1, 'courseid' => 1])));
    }
    $results = example_library::import(examples_form::selected_paths($data), $category);
}

$PAGE->requires->js_call_amd('qtype_aitext/examplelibrary', 'init');

echo $OUTPUT->header();
echo html_writer::link($backurl, $OUTPUT->pix_icon('i/arrow-left', '') . get_string('back'));
echo $OUTPUT->heading($title);

if ($results) {
    $imported = [];
    $failed = [];
    foreach ($results as $result) {
        if ($result->success) {
            $editurl = new moodle_url('/question/bank/editquestion/question.php', $urlparams + ['id' => $result->questionid]);
            $imported[] = html_writer::link($editurl, format_string($result->name));
        } else {
            $failed[] = s($result->name);
        }
    }
    if ($imported) {
        echo $OUTPUT->notification(get_string('examplesimported', 'qtype_aitext', count($imported)) .
            html_writer::alist($imported), \core\output\notification::NOTIFY_SUCCESS);
    }
    if ($failed) {
        echo $OUTPUT->notification(get_string('examplesimportfailed', 'qtype_aitext') .
            html_writer::alist($failed), \core\output\notification::NOTIFY_ERROR);
    }
}

if (!example_library::has_examples()) {
    echo $OUTPUT->notification(get_string('examplesnone', 'qtype_aitext'), \core\output\notification::NOTIFY_INFO);
    echo $OUTPUT->footer();
    die();
}

echo html_writer::tag('p', get_string('examplelibraryintro', 'qtype_aitext'));
echo $OUTPUT->render_from_template('qtype_aitext/examplelibrary', [
    'contextid' => $context->id,
    'subjects' => example_library::get_tree($baseurl, $selected ?: null),
    'filters' => example_library::get_facet_filters(),
    'importform' => $form->render(),
    'preview' => $selected
        ? $OUTPUT->render_from_template('qtype_aitext/examplepreview', example_library::get_preview($selected))
        : '',
]);
echo $OUTPUT->footer();
