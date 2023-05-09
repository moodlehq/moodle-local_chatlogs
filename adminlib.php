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
 * Admin tree class for lazyloading config box
 *
 * @package     local_chatlogs
 * @copyright   2012 Dan Poltawski <dan@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * admin_setting_configselect for the seleted cohort, so we can lazy-load the choices.
 *
 * @package   local_chatlogs
 * @copyright Dan Poltawski <dan@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_chatlogs_cohort_selector extends admin_setting_configselect {

    /** Lazy-load the available choices for the select box */
    public function load_choices() {
        global $DB;

        $this->choices = array(0 => get_string('none'));
        if ($cohorts = $DB->get_records_menu('cohort', array('contextid' => context_system::instance()->id), 'name', 'id, name')) {
            foreach ($cohorts as $key => $value) {
                $this->choices[$key] = $value;
            }
        }

        return true;
    }
}
