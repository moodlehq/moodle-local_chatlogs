<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Defines {@see \local_chatlogs\privacy\provider} class.
 *
 * @package     local_chatlogs
 * @category    privacy
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_chatlogs\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/**
 * Privacy API implementation for the Developer chat plugin.
 *
 * @copyright  2018 David Mudrák <david@moodle.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider {

    /**
     * Describe all the places where the Developer chat plugin stores some personal data.
     *
     * @param collection $collection Collection of items to add metadata to.
     * @return collection Collection with our added items.
     */
    public static function get_metadata(collection $collection) : collection {

        $collection->add_database_table('local_chatlogs_messages', [
           'conversationid' => 'privacy:metadata:db:messages:conversationid',
           'fromemail' => 'privacy:metadata:db:messages:fromemail',
           'fromplace' => 'privacy:metadata:db:messages:fromplace',
           'fromnick' => 'privacy:metadata:db:messages:fromnick',
           'timejava' => 'privacy:metadata:db:messages:timejava',
           'timesent' => 'privacy:metadata:db:messages:timesent',
           'message' => 'privacy:metadata:db:messages:message',
        ], 'privacy:metadata:db:messages');

        $collection->add_database_table('local_chatlogs_participants', [
           'fromemail' => 'privacy:metadata:db:participants:fromemail',
           'nickname' => 'privacy:metadata:db:participants:nickname',
        ], 'privacy:metadata:db:participants');

        return $collection;
    }

    /**
     * Get the list of contexts that contain personal data for the specified user.
     *
     * @param int $userid ID of the user.
     * @return contextlist List of contexts containing the user's personal data.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {

        $contextlist = new contextlist();
        $contextlist->add_system_context();

        return $contextlist;
    }

    /**
     * Export personal data stored in the given contexts.
     *
     * @param approved_contextlist $contextlist List of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (!count($contextlist)) {
            return;
        }

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->id == SYSCONTEXTID) {
                $syscontextapproved = true;
                break;
            }
        }

        if (!$syscontextapproved) {
            return;
        }

        $user = $contextlist->get_user();
        $writer = writer::with_context(\context_system::instance());
        $subcontext = [get_string('pluginname', 'local_chatlogs')];

        $sql = "SELECT m.conversationid, m.fromemail, m.fromplace, m.fromnick, m.timejava, m.timesent, m.message
                  FROM local_chatlogs_messages m
                  JOIN local_chatlogs_participants p ON m.fromemail = p.fromemail
				 WHERE p.userid = :userid
              ORDER BY m.timesent";

        $params = [
            'userid' => $user->id,
        ];

        $rs = $DB->get_recordset_sql($sql, $params);
        $currentdate = '';
        $messages = [];

        foreach ($rs as $record) {
            $folderdate = date('Y-m-d', $record->timesent);
            if ($currentdate !== $folderdate) {
                if ($messages) {
                    $writer->export_data(array_merge($subcontext, [$folderdate]), (object)['messages' => $messages]);
                }
                $currentdate = $folderdate;
                $messages = [];
            }
            $record->timesent = transform::datetime($record->timesent);
            $messages[] = $record;
        }

        $rs->close();

        $aliases = $DB->get_records('local_chatlogs_participants', ['userid' => $user->id], '', 'id, fromemail, nickname');
        if ($aliases) {
            $writer->export_related_data($subcontext, 'aliases', array_values(array_map(function($record) {
                unset($record->id);
                return $record;
            }, $aliases)));
        }
    }

    /**
     * Delete personal data for all users in the context.
     *
     * @param context $context Context to delete personal data from.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        // You will probably use some variant of `$DB->delete_records()` here to remove user data from your tables.
        // If you have plugin files, do not forget to clean the relevant files areas too.
    }

    /**
     * Delete personal data for the user in a list of contexts.
     *
     * @param approved_contextlist $contextlist List of contexts to delete data from.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (!count($contextlist)) {
            return;
        }

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $user = $contextlist->get_user();
        $fs = get_file_storage();

        // You will probably use some variant of `$DB->delete_records()` here to remove user data from your tables.
        // If you have plugin files, do not forget to clean the relevant files areas too.
    }
}
