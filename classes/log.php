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

/**
 * Class logging
 *
 * @package    qtype_aitext
 * @copyright  2025 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class log {
    public function insert(int $questionid, string $prompt) :bool {
        global $DB, $USER;
        if(get_config('qtype_aitext', 'logallprompts') == '1') {
            $record = new \stdClass();
            $record->aitext = $questionid;
            $record->userid = $USER->id;
            $record->prompt = $prompt;
            $record->regex = '';
            $record->timecreated = time();

            $DB->insert_record('qtype_aitext_log', $record);
            return true;
        }

        if(get_config('qtype_aitext', 'regularexpressions') == '1') {
            $patterns = explode("\n", get_config('qtype_aitext', 'injectionprompts'));
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $prompt)) {
                    // Typically a prompt injection attempt.
                    $record = new \stdClass();
                    $record->aitext = $questionid;
                    $record->userid = $USER->id;
                    $record->prompt = $prompt;
                    $record->regex = $pattern;
                    $record->timecreated = time();
                    $DB->insert_record('qtype_aitext_log', $record);
                    return true;
                }
            }
        }
        return false;
    }

}
