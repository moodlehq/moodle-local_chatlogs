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
 * Provides utility functions used by plugin.
 *
 * @package     local_chatlogs
 * @copyright   2016 Andrew Nicols <andrew@nicols.co.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_chatlogs;

/**
 * Provides utility functions used by plugin.
 *
 * @copyright   2016 Andrew Nicols <andrew@nicols.co.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class util {
    /**
     * Group orphaned conversations.
     *
     * @return  array   The list of orphaned conversations which were re-grouped with another conversation.
     *                  The array contains the conversation moved => where it was moved to.
     */
    public static function group_orphans() {
        global $DB;
        // Clean up orphan conversations.
        $sql = <<<EOF
                SELECT c.id, c.messagecount, COUNT(m.id) AS actualcount
                FROM {local_chatlogs_conversations} c
            INNER JOIN {local_chatlogs_messages} m ON m.conversationid = c.id
            GROUP BY c.id, c.messagecount
            ORDER BY c.id DESC
EOF;

        $moved = [];
        if ($conversations = $DB->get_records_sql($sql)) {
            // Skip the first conversation - it is still in progress.
            array_shift($conversations);

            $sourceid = 0;
            foreach ($conversations as $conversation) {
                if ($sourceid) {
                    $moved[$sourceid] = $conversation->id;

                    // Move messages to the target conversation.
                    $DB->set_field('local_chatlogs_messages', 'conversationid', $conversation->conversationid, ['conversationid'  => $sourceid]);
                    $DB->set_field('local_chatlogs_conversations', 'messagecount', 0, ['id'  => $sourceid]);

                    // Update the count, and timeend on the target conversation.
                    $conversation->messagecount = $DB->count_records('local_chatlogs_messages', ['conversationid' => $conversation->id]);
                    $conversation->timeend = $DB->get_field_sql('SELECT timesent FROM {local_chatlogs_messages} WHERE conversationid = ? ORDER BY timesent DESC', [$conversation->id]);
                    $DB->update_record('local_chatlogs_conversations', $conversation);

                    // Clear the source value.
                    $sourceid = 0;
                }

                if ($conversation->messagecount == 1 || $conversation->actualcount == 1) {
                    // There is only a single message in the conversation.
                    // Push this to the next conversation.
                    $sourceid = $conversation->conversationid;
                }
            }
        }

        return $moved;
    }
}
