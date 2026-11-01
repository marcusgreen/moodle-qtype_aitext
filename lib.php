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
 * Serve question type files
 *
 * @package    qtype_aitext
 * @copyright  Marcus Green 2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Checks file access for aitext questions.
 *
 * @package  qtype_aitext
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool
 */
function qtype_aitext_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $CFG;
    require_once($CFG->libdir . '/questionlib.php');
    question_pluginfile($course, $context, 'qtype_aitext', $filearea, $args, $forcedownload, $options);
}

/**
 * Fragment callback rendering the preview of one example question for the example library.
 *
 * @param array $args context and path (example path relative to the examples folder)
 * @return string HTML
 */
function qtype_aitext_output_fragment_example_preview(array $args): string {
    global $OUTPUT;
    require_capability('moodle/question:add', $args['context']);
    $preview = \qtype_aitext\local\example_library::get_preview((string) ($args['path'] ?? ''));
    if (!$preview) {
        throw new moodle_exception('invalidparameter', 'debug');
    }
    return $OUTPUT->render_from_template('qtype_aitext/examplepreview', $preview);
}
