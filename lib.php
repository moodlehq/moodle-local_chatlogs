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
 * The plugin's external API is defined here
 *
 * @package     local_chatlogs
 * @copyright   2012 Dan Poltawski <dan@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Determines whether the current user can access chatlogs
 *
 * @return bool true if user can access logs
 */
function local_chatlogs_can_access() {
    global $USER, $DB;

    $context = context_system::instance();

    if (has_capability('local/chatlogs:view', $context)) {
        // If user has the view permission they are allowed to view then chatlogs.
        return true;
    }

    if (has_capability('local/chatlogs:viewifdeveloper', $context)) {
        if ($cohortid = get_config('local_chatlogs', 'cohortid')) {
            if ($cohortid > 0) {
                // Only allowed to view if in developer cohort.
                if ($DB->get_record('cohort_members', array('cohortid' => $cohortid, 'userid' => $USER->id))) {
                    return true;
                }
            }
        }
    }

    return false;
}

/**
 * Puts chatlogs into the global navigation tree.
 *
 * @since Moodle 2.9
 * @param global_navigation $navigation the navigation tree instance
 * @category navigation
 */
function local_chatlogs_extend_navigation(global_navigation $navigation) {
    global $SESSION;

    // Horrible hack to prevent additional db queries on every page.
    if (isloggedin() && !isguestuser() && !property_exists($SESSION, 'show_devchat_nav')) {
        $SESSION->show_devchat_nav = (bool) local_chatlogs_can_access();
    }

    if (property_exists($SESSION, 'show_devchat_nav') && $SESSION->show_devchat_nav) {
        $node = $navigation->add(get_string('pluginname', 'local_chatlogs'), null, navigation_node::TYPE_CUSTOM,
            null, 'local_chatlogs-root');
        $node->add(get_string('developerconversations', 'local_chatlogs'), new moodle_url('/local/chatlogs/index.php'),
            navigation_node::TYPE_CUSTOM, null, 'local_chatlogs-conversations');
        $node->add(get_string('info', 'local_chatlogs'), new moodle_url('/local/chatlogs/info.php'),
            navigation_node::TYPE_CUSTOM, null, 'local_chatlogs-info');
        if (has_capability('moodle/site:config', context_system::instance())) {
            $admin = $node->add(get_string('administration'));
            $admin->add(get_string('jabberaliases', 'local_chatlogs'),
                new moodle_url('/local/chatlogs/admin/jabber-aliases.php'));
        }
    }
}

/**
 * Puts chatlogs into the global navigation tree.
 *
 * Keeping this function here for Moodle 2.8 and lower versions.
 *
 * @todo Remove this function when this plugin requires Moodle 2.9
 * @param global_navigation $navigation the navigation tree instance
 * @category navigation
 */
function local_chatlogs_extends_navigation(global_navigation $navigation) {
    return local_chatlogs_extend_navigation($navigation);
}
