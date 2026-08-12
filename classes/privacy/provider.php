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
 * Privacy Subsystem implementation for qtype_aitext.
 *
 * @package    qtype_aitext
 * @copyright  2024 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_aitext\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy Subsystem for qtype_aitext implementing user_preference_provider.
 *
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        // This component has data.
        // We need to return default options that have been set a user preferences.
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\user_preference_provider {
    /**
     * Returns meta data about this system.
     *
     * @param   collection     $collection The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_user_preference('qtype_aitext_defaultmark', 'privacy:preference:defaultmark');
        $collection->add_user_preference('qtype_aitext_responseformat', 'privacy:preference:responseformat');
        $collection->add_user_preference('qtype_aitext_responsefieldlines', 'privacy:preference:responsefieldlines');
        $collection->add_user_preference('qtype_aitext_maxbytes', 'privacy:preference:maxbytes');
        $collection->add_database_table('qtype_aitext_queue', [
            'userid' => 'privacy:metadata:queue:userid',
            'response' => 'privacy:metadata:queue:response',
            'prompt' => 'privacy:metadata:queue:prompt',
            'feedback' => 'privacy:metadata:queue:feedback',
            'marks' => 'privacy:metadata:queue:marks',
            'timecreated' => 'privacy:metadata:queue:timecreated',
        ], 'privacy:metadata:queue:tableexplanation');
        return $collection;
    }

    /**
     * Return the contexts where the user has queued AI grading data.
     *
     * @param int $userid User id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $contextlist->add_from_sql(
            "SELECT qu.contextid
               FROM {qtype_aitext_queue} q
               JOIN {question_usages} qu ON qu.id = q.usageid
              WHERE q.userid = :userid",
            ['userid' => $userid]
        );
        return $contextlist;
    }

    /**
     * Add users represented in a module context to the privacy user list.
     *
     * @param userlist $userlist User list for a context.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        $userlist->add_from_sql(
            'userid',
            "SELECT q.userid
               FROM {qtype_aitext_queue} q
               JOIN {question_usages} qu ON qu.id = q.usageid
              WHERE qu.contextid = :contextid",
            ['contextid' => $context->id]
        );
    }

    /**
     * Export queue records for the requested user and contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $records = $DB->get_records_sql(
                "SELECT q.*
                   FROM {qtype_aitext_queue} q
                   JOIN {question_usages} qu ON qu.id = q.usageid
                  WHERE q.userid = :userid AND qu.contextid = :contextid
               ORDER BY q.timecreated ASC",
                ['userid' => $userid, 'contextid' => $context->id]
            );
            foreach ($records as $record) {
                writer::with_context($context)->export_data(
                    [get_string('privacy:metadata:queue', 'qtype_aitext'), (string) $record->id],
                    (object) [
                        'response' => $record->response,
                        'prompt' => $record->prompt,
                        'feedback' => $record->feedback,
                        'marks' => $record->marks,
                        'status' => $record->status,
                        'timecreated' => transform::datetime($record->timecreated),
                    ]
                );
            }
        }
    }

    /**
     * Delete queue data for all users in a context.
     *
     * @param \context $context Context.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        $usageids = $DB->get_fieldset_select('question_usages', 'id', 'contextid = ?', [$context->id]);
        if ($usageids) {
            [$insql, $params] = $DB->get_in_or_equal($usageids, SQL_PARAMS_NAMED, 'usage');
            $DB->delete_records_select('qtype_aitext_queue', "usageid {$insql}", $params);
        }
    }

    /**
     * Delete queue data for selected users in one context.
     *
     * @param approved_userlist $userlist Approved users.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();
        $usageids = $DB->get_fieldset_select('question_usages', 'id', 'contextid = ?', [$context->id]);
        $userids = $userlist->get_userids();
        if ($usageids && $userids) {
            [$usagein, $usageparams] = $DB->get_in_or_equal($usageids, SQL_PARAMS_NAMED, 'usage');
            [$userin, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'user');
            $DB->delete_records_select('qtype_aitext_queue', "usageid {$usagein} AND userid {$userin}",
                $usageparams + $userparams);
        }
    }

    /**
     * Delete queue data for one user.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $usageids = $DB->get_fieldset_select('question_usages', 'id', 'contextid = ?', [$context->id]);
            if ($usageids) {
                [$insql, $params] = $DB->get_in_or_equal($usageids, SQL_PARAMS_NAMED, 'usage');
                $params['userid'] = $userid;
                $DB->delete_records_select('qtype_aitext_queue', "usageid {$insql} AND userid = :userid", $params);
            }
        }
    }

    /**
     * Export all user preferences for the plugin.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     */
    public static function export_user_preferences(int $userid) {
        $preference = get_user_preferences('qtype_aitext_defaultmark', null, $userid);
        if (null !== $preference) {
            $desc = get_string('privacy:preference:defaultmark', 'qtype_aitext');
            writer::export_user_preference('qtype_aitext', 'defaultmark', $preference, $desc);
        }

        $preference = get_user_preferences('qtype_aitext_responseformat', null, $userid);
        if (null !== $preference) {
            switch ($preference) {
                case 'editor':
                    $stringvalue = get_string('formateditor', 'qtype_aitext');
                    break;
                case 'editorfilepicker':
                    $stringvalue = get_string('formateditorfilepicker', 'qtype_aitext');
                    break;
                case 'plain':
                    $stringvalue = get_string('formatplain', 'qtype_aitext');
                    break;
                case 'monospaced':
                    $stringvalue = get_string('formatmonospaced', 'qtype_aitext');
                    break;
                case 'noinline':
                    $stringvalue = get_string('formatnoinline', 'qtype_aitext');
                    break;
                default:
                    $stringvalue = get_string('formateditor', 'qtype_aitext');
                    break;
            }
            $desc = get_string('privacy:preference:responseformat', 'qtype_aitext');
            writer::export_user_preference('qtype_aitext', 'responseformat', $stringvalue, $desc);
        }

        $preference = get_user_preferences('qtype_aitext_responserequired', null, $userid);
        if (null !== $preference) {
            if ($preference) {
                $stringvalue = get_string('responseisrequired', 'qtype_aitext');
            } else {
                $stringvalue = get_string('responsenotrequired', 'qtype_aitext');
            }
            $desc = get_string('privacy:preference:responserequired', 'qtype_aitext');
            writer::export_user_preference('qtype_aitext', 'responserequired', $stringvalue, $desc);
        }

        $preference = get_user_preferences('qtype_aitext_responsefieldlines', null, $userid);
        if (null !== $preference) {
            $desc = get_string('privacy:preference:responsefieldlines', 'qtype_aitext');
            writer::export_user_preference(
                'qtype_aitext',
                'responsefieldlines',
                get_string('nlines', 'qtype_aitext', $preference),
                $desc
            );
        }
        $preference = get_user_preferences('qtype_aitext_attachments', null, $userid);
        if (null !== $preference) {
            if ($preference == 0) {
                $stringvalue = get_string('no');
            } else if ($preference == -1) {
                    $stringvalue = get_string('unlimited');
            } else {
                $stringvalue = $preference;
            }
            $desc = get_string('privacy:preference:attachments', 'qtype_aitext');
            writer::export_user_preference('qtype_aitext', 'attachments', $stringvalue, $desc);
        }

        $preference = get_user_preferences('qtype_aitext_attachmentsrequired', null, $userid);
        if (null !== $preference) {
            if ($preference == 0) {
                $stringvalue = get_string('attachmentsoptional', 'qtype_aitext');
            } else {
                $stringvalue = $preference;
            }
            $desc = get_string('privacy:preference:attachmentsrequired', 'qtype_aitext');
            writer::export_user_preference('qtype_aitext', 'attachmentsrequired', $stringvalue, $desc);
        }

        $preference = get_user_preferences('qtype_aitext_maxbytes', null, $userid);
        if (null !== $preference) {
            switch ($preference) {
                case 52428800:
                    $stringvalue = '50MB';
                    break;
                case 20971520:
                    $stringvalue = '20MB';
                    break;
                case 10485760:
                    $stringvalue = '10MB';
                    break;
                case 5242880:
                    $stringvalue = '5MB';
                    break;
                case 2097152:
                    $stringvalue = '2MB';
                    break;
                case 1048576:
                    $stringvalue = '1MB';
                    break;
                case 512000:
                    $stringvalue = '500KB';
                    break;
                case 102400:
                    $stringvalue = '100KB';
                    break;
                case 51200:
                    $stringvalue = '50KB';
                    break;
                case 10240:
                    $stringvalue = '10KB';
                    break;
                default:
                    $stringvalue = '50MB';
                    break;
            }
            $desc = get_string('privacy:preference:maxbytes', 'qtype_aitext');
            writer::export_user_preference('qtype_aitext', 'maxbytes', $stringvalue, $desc);
        }
    }
}
