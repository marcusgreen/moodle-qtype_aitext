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
 * Installs the bundled user tour for the question editing form.
 *
 * tool_usertours only imports tours shipped in its own tours directory, so the plugin
 * imports its tour itself. This is called from install, upgrade and, as a fallback, when
 * the editing form is built: on a fresh site install question types are installed before
 * admin tools, so the tool_usertours tables do not exist yet when db/install.php runs.
 *
 * The import happens once per tour version. If an admin edits or deletes the tour it is
 * not recreated or overwritten.
 *
 * @package    qtype_aitext
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tour {
    /** @var string Tour name as stored in tool_usertours_tours, used to detect an existing import. */
    public const NAME = 'tourname,qtype_aitext';

    /** @var int Version of the bundled tour. Increase when tours/qtype_aitext_edit.json changes. */
    public const VERSION = 1;

    /** @var string Path of the tour definition relative to the plugin directory. */
    private const FILE = '/question/type/aitext/tours/qtype_aitext_edit.json';

    /**
     * Import the bundled tour if this version has not been imported yet.
     *
     * @return void
     */
    public static function install(): void {
        global $CFG, $DB;

        if ((int) get_config('qtype_aitext', 'tourversion') >= self::VERSION) {
            return;
        }
        if (!class_exists(\tool_usertours\manager::class)) {
            return;
        }
        // Not available yet during a fresh site install; the form fallback imports it later.
        if (!$DB->get_manager()->table_exists('tool_usertours_tours')) {
            return;
        }

        if (!$DB->record_exists('tool_usertours_tours', ['name' => self::NAME])) {
            $file = $CFG->dirroot . self::FILE;
            if (!is_readable($file)) {
                return;
            }
            $json = file_get_contents($file);
            // The tour ships disabled; admins enable it in Site administration > User tours.
            \tool_usertours\manager::import_tour_from_json($json);
        }

        set_config('tourversion', self::VERSION, 'qtype_aitext');
    }
}
