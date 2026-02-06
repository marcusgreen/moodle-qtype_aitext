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
 * Upgrade helper functions for qtype_aitext.
 *
 * @package    qtype_aitext
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute the full database migration for legacy expert mode prompts.
 *
 * This function finds all qtype_aitext records that contain the legacy
 * [[expert]] or [[response]] syntax and migrates them to the new
 * {{placeholder}} syntax.
 *
 * Conversions performed:
 * - [[expert]] is removed (was only a mode indicator)
 * - [[response]] becomes {{response}}
 * - [[questiontext]] becomes {{questiontext}}
 * - [[userlang]] becomes {{language}}
 * - Multiple whitespace is collapsed to single space
 *
 * @return int The number of prompts that were migrated.
 */
function qtype_aitext_upgrade_migrate_legacy_prompts(): int {
    global $DB;

    $legacyprompts = $DB->get_records_select(
        'qtype_aitext',
        "aiprompt LIKE '%[[expert]]%' OR aiprompt LIKE '%[[response]]%'",
        null,
        '',
        'id, aiprompt'
    );

    $migratedcount = 0;
    foreach ($legacyprompts as $record) {
        $newprompt = qtype_aitext_migrate_legacy_expert_prompt($record->aiprompt);

        if ($newprompt !== $record->aiprompt) {
            $DB->update_record('qtype_aitext', (object)[
                'id' => $record->id,
                'aiprompt' => $newprompt,
            ]);
            $migratedcount++;
        }
    }

    return $migratedcount;
}

/**
 * Migrate a legacy expert mode prompt to the new {{placeholder}} syntax.
 *
 * @param string $aiprompt The original AI prompt with legacy placeholders.
 * @return string The migrated prompt with new placeholder syntax.
 */
function qtype_aitext_migrate_legacy_expert_prompt(string $aiprompt): string {
    $newprompt = str_replace(
        ['[[expert]]', '[[response]]', '[[questiontext]]', '[[userlang]]'],
        ['', '{{response}}', '{{questiontext}}', '{{language}}'],
        $aiprompt
    );

    return preg_replace('/\s+/', ' ', trim($newprompt));
}
