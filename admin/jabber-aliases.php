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
 * Displays the list of jabber users and lets the user to assign them to real user accounts
 *
 * Shamelessly stolen from local_dev by Dan
 *
 * @package     local_chatlogs
 * @copyright   2012 Dan Poltawski <dan@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require($CFG->dirroot.'/local/chatlogs/locallib.php');

require_login(SITEID, false);
require_capability('moodle/site:config', context_system::instance());

$action = optional_param('action', null, PARAM_ALPHA);

$PAGE->set_pagelayout('standard');
$PAGE->set_url(new moodle_url('/local/chatlogs/admin/jabber-aliases.php'));
$PAGE->set_title(get_string('jabberaliases', 'local_chatlogs'));
$PAGE->set_heading(get_string('jabberaliases', 'local_chatlogs'));
$PAGE->requires->yui_module('moodle-local_chatlogs-jabberaliases', 'M.local_chatlogs.init_jabberaliases');

if ($data = data_submitted()) {
    require_sesskey();
    $jabberid = required_param('jabberid', PARAM_RAW);
    $userid = required_param('userid', PARAM_INT);
    if (!empty($userid)) {
        $status = link_jabberid_to_user($jabberid, $userid);
        if ($status === false) {
            throw new moodle_exception('failed');
        }
    }
    redirect($PAGE->url);
}


echo $OUTPUT->header();

// Get the list of unknown jabber ids.

$sql = "SELECT fromemail, nickname
          FROM {local_chatlogs_participants}
         WHERE userid IS NULL
      ORDER BY fromemail ASC";
$rs = $DB->get_recordset_sql($sql);

$table = new html_table();
$table->id = 'aliaseseditor';
$table->head = array(
    get_string('jabberfullname', 'local_chatlogs'),
    get_string('jabberid', 'local_chatlogs'),
    get_string('jabberaliasesassign', 'local_chatlogs'),
);


foreach ($rs as $record) {
    $table->data[] = array(
        html_writer::tag('div', s($record->nickname), array('class' => 'aliasdata-authorname')),
        html_writer::tag('div', s($record->fromemail), array('class' => 'aliasdata-authoremail')),
        html_writer::tag('form',
            html_writer::tag('div',
                html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'jabbernick', 'value' => $record->nickname)).
                html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'jabberid', 'value' => $record->fromemail)).
                html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey())).
                html_writer::empty_tag('input', array('type' => 'text', 'name' => 'search', 'class' => 'aliasdata-search',
                    'maxlength' => 100, 'size' => 50)).
                html_writer::empty_tag('input', array('type' => 'text', 'name' => 'userid', 'class' => 'aliasdata-userid',
                    'maxlength' => 100, 'size' => 5)).
                html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('submit'))).
                html_writer::empty_tag('input', array('type' => 'reset', 'value' => get_string('reset'))).
                html_writer::tag('span', ' ', array('class' => 'aliasdata-icon'))
            ),
        array('method' => 'post', 'action' => $PAGE->url->out()))
    );
}
echo html_writer::table($table);
$rs->close();
echo $OUTPUT->footer();

/**
 * Link a jabberid to a user account
 *
 * @param string $jabberid jabberid
 * @param int $userid id of user
 * @return bool true if linked succesfully
 */
function link_jabberid_to_user($jabberid, $userid) {
    global $DB;

    if (is_null($userid) || is_null($jabberid)) {
        throw new coding_exception('NULL parameter values not allowed here');
    }

    $record = $DB->get_record('local_chatlogs_participants', array('fromemail' => $jabberid));

    if ($record) {
        $record->userid = $userid;
        $DB->update_record('local_chatlogs_participants', $record);
        return true;
    } else {
        return false;
    }
}
