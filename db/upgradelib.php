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

/**
 * Migrate legacy question attempts stored with the 'interactivecountback' behaviour.
 *
 * Historically qtype_aitext_question extended question_graded_automatically_with_countback,
 * so an attempt started under the archetypal 'interactive' preferred behaviour was resolved
 * and stored (in question_attempts.behaviour) as 'interactivecountback'. Since qtype_aitext
 * no longer implements question_automatically_gradable_with_countback, reconstructing that
 * stored behaviour now fails with a coding_exception ("This behaviour (interactivecountback)
 * cannot work with this question"), breaking review and regrade of those legacy attempts.
 *
 * A new attempt started under 'interactive' is now resolved to 'immediate_for_aitext'
 * (see qtype_aitext_question::make_behaviour()), so this migration rewrites the stored
 * behaviour of the affected legacy attempts to the same value for consistency.
 *
 * @return int The number of question attempts that were migrated.
 */
function qtype_aitext_upgrade_migrate_countback_attempts(): int {
    global $DB;

    // Identify the affected attempts then update them via get_in_or_equal().
    $selectsql = "SELECT qa.id
                    FROM {question_attempts} qa
                    JOIN {question} q ON q.id = qa.questionid
                   WHERE qa.behaviour = :oldbehaviour
                     AND q.qtype = :qtype";
    $selectparams = [
        'oldbehaviour' => 'interactivecountback',
        'qtype' => 'aitext',
    ];

    $attemptids = $DB->get_fieldset_sql($selectsql, $selectparams);

    if (empty($attemptids)) {
        return 0;
    }

    [$insql, $inparams] = $DB->get_in_or_equal($attemptids, SQL_PARAMS_NAMED);
    $DB->set_field_select('question_attempts', 'behaviour', 'immediate_for_aitext', "id {$insql}", $inparams);

    return count($attemptids);
}

/**
 * Reset the JSON prompt setting if it still holds the old, malformed default.
 *
 * Until 2026101400 the default JSON prompt showed the LLM the format
 * {feedback":"string","marks":"number"}, with the opening quote of the first key missing.
 * Models copy that example and return {feedback:"...","marks":3}, which is not valid JSON,
 * so the raw reply ended up as the comment and the question stayed in "Requires grading".
 * Only an unedited default is reset (compared with whitespace collapsed); the upgrade then
 * applies the corrected default from settings.php. A customised prompt is left alone.
 *
 * @return bool true if the setting was reset.
 */
function qtype_aitext_upgrade_reset_old_jsonprompt(): bool {
    $olddefault = 'Return only a JSON object which enumerates a set of 2 elements.The JSON object should be in ' .
        'this format: {feedback":"string","marks":"number"} where marks is a single number summing all marks. ' .
        'Also show the marks as part of the feedback.';
    $current = get_config('qtype_aitext', 'jsonprompt');
    if ($current === false) {
        return false;
    }
    $normalise = fn(string $text): string => trim(preg_replace('/\s+/', ' ', $text));
    if ($normalise($current) !== $normalise($olddefault)) {
        return false;
    }
    unset_config('jsonprompt', 'qtype_aitext');
    return true;
}
