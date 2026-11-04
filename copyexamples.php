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
 * Copy the built-in AI Text examples into the example category chosen in the plugin settings.
 *
 * @package    qtype_aitext
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

use qtype_aitext\form\copy_examples_form;
use qtype_aitext\local\example_library;

require_admin();

$url = new moodle_url('/question/type/aitext/copyexamples.php');
$settingsurl = new moodle_url('/admin/settings.php', ['section' => 'qtypesettingaitext']);
$title = get_string('examplecopy', 'qtype_aitext');
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$category = example_library::get_category();

$form = new copy_examples_form($url);
if ($form->is_cancelled()) {
    redirect($settingsurl);
}
if ($category && $form->get_data()) {
    $counts = example_library::copy_builtin($category);
    if ($form->get_data()->disablebuiltin) {
        set_config('examplebuiltin', 0, 'qtype_aitext');
        add_to_config_log('examplebuiltin', '1', '0', 'qtype_aitext');
    }
    redirect(
        $settingsurl,
        get_string('examplecopydone', 'qtype_aitext', $counts) . example_library::get_category_links(),
        null,
        $counts->failed ? \core\output\notification::NOTIFY_WARNING : \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
if (!$category) {
    echo $OUTPUT->notification(get_string('examplecopynocategory', 'qtype_aitext'), \core\output\notification::NOTIFY_WARNING);
    echo $OUTPUT->single_button($settingsurl, get_string('back'), 'get');
} else {
    $choices = example_library::get_category_choices();
    echo html_writer::tag('p', get_string('examplecopyintro', 'qtype_aitext', (object) [
        'count' => count(example_library::get_builtin_examples()),
        'category' => $choices[$category->id] ?? format_string($category->name),
    ]));
    $form->display();
}
echo $OUTPUT->footer();
