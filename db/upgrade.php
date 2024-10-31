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
 * AI Text question type upgrade code.
 *
 * @package    qtype_aitext
 * @copyright  Marcus Green 2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Upgrade code for the aitext question type.
 *
 * @param int $oldversion the version we are upgrading from.
 */
function xmldb_qtype_aitext_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2024050300) {

        $table = new xmldb_table('qtype_aitext');
        // Used for prompt testing in the edit form.
        $field = new xmldb_field('sampleanswer', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2024050300, 'qtype', 'aitext');

    }

    if ($oldversion < 2024051100) {

        // Define field model to be added to qtype_aitext.
        $table = new xmldb_table('qtype_aitext');
        $field = new xmldb_field('model', XMLDB_TYPE_CHAR, '60', null, null, null, null, 'sampleanswer');

        // Conditionally launch add field model.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Aitext savepoint reached.
        upgrade_plugin_savepoint(true, 2024051100, 'qtype', 'aitext');
    }

    if ($oldversion < 2024051102) {

        // Define field model to be added to qtype_aitext.
        $table = new xmldb_table('qtype_aitext');
        $fields = [];
        $fields[] = new xmldb_field('responselanguage', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, 'en-us');
        $fields[] = new xmldb_field('feedbacklanguage', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, 'en-us');
        $fields[] = new xmldb_field('maxtime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $fields[] = new xmldb_field('relevance', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $fields[] = new xmldb_field('relevanceanswer', XMLDB_TYPE_TEXT, 'small', null, null, null, null);

        // Conditionally add fields
        foreach($fields as $field) {
          if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
          }
        }

        // Aitext savepoint reached.
        upgrade_plugin_savepoint(true, 2024051102, 'qtype', 'aitext');
    }

    if ($oldversion < 2024051103) {
        // JSON prompt upgrade to factor in relevance. If the user has edited the JSON prompt, we don't touch it.
        $originaljsonprompt = 'Return only a JSON object which enumerates a set of 2 elements.';
        $originaljsonprompt .= 'The JSON object should be in this format: {feedback":"string","marks":"number"}';
        $originaljsonprompt .= ' where marks is a single number summing all marks.';
        $originaljsonprompt .= ' Also show the marks as part of the feedback.';
        $originaljsonprompt = preg_replace('/\s+/', ' ', trim($originaljsonprompt));

        $currentjsonprompt = get_config('qtype_aitext', 'jsonprompt');
        $currentjsonprompt = preg_replace('/\s+/', ' ', trim($currentjsonprompt));

        if ($currentjsonprompt == $originaljsonprompt) {
            $newprompt = "Return only a JSON object which enumerates a set of 4 elements.";
            $newprompt .= ' The JSON object should be in this format: {"feedback":"string","correctedtext":"string",marks":"number", "relevance": "number"}';
            $newprompt .= " where marks is a single number summing all marks.";
            set_config('jsonprompt', $newprompt, 'qtype_aitext');
        }


        // Aitext savepoint reached.
        upgrade_plugin_savepoint(true, 2024051103, 'qtype', 'aitext');
    }

    return true;
}
