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
 * Post installation and migration code.
 *
 * @package    local_chatlogs
 * @copyright  2012 Dan Poltawski <dan@moodle.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Installation step for chatlogs plugin.
 *
 * If the cvsadmin tables exist then we migrate the data from these
 * tablels
 */
function xmldb_local_chatlogs_install() {
    global $DB;

    $dbman = $DB->get_manager();

    if ($dbman->table_exists('cvsadmin_talk_conversations') &&
        $dbman->table_exists('cvsadmin_talk_messages') &&
        $dbman->table_exists('cvsadmin_talk_participants')) {
        // Import old data from cvsadmin module..
        $DB->execute('INSERT INTO {local_chatlogs_conversations} (SELECT * FROM {cvsadmin_talk_conversations})');
        $DB->execute('INSERT INTO {local_chatlogs_messages} (SELECT * FROM {cvsadmin_talk_messages})');
        $DB->execute('INSERT INTO {local_chatlogs_participants} (SELECT * FROM {cvsadmin_talk_participants})');
    }
}
