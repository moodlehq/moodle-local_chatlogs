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
 * Displays list of conversations or a specific covnersation depending on params
 *
 * @package     local_chatlogs
 * @copyright   2012 Dan Poltawski <dan@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/local/chatlogs/locallib.php');

$conversationid = optional_param('conversationid', 0, PARAM_INT);
$searchterm = optional_param('q', '', PARAM_TEXT);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');

$pageurl = new moodle_url('/local/chatlogs/index.php');
if (!empty($conversationid)) {
    $pageurl->param('conversationid', $conversationid);
}
$PAGE->set_url($pageurl);

$PAGE->set_title(get_string('pluginname', 'local_chatlogs'));
$PAGE->set_heading(get_string('pluginname', 'local_chatlogs'));

require_login(null, false);
local_chatlogs_require_capability();

echo $OUTPUT->header();


if (!empty($searchterm)) {
    echo $OUTPUT->heading(get_string('searchchat', 'local_chatlogs'));
    echo local_chatlogs_search_table::form($searchterm);

    $table = new local_chatlogs_search_table('dev-search', $searchterm);
    $url = $PAGE->url;
    $url->param('q', $searchterm);
    $table->define_baseurl($url);
    $table->out(50, true);
} else if ($conversationid) {
    $conversation = new local_chatlogs_conversation($conversationid);
    $conversation->render();
} else {
    echo $OUTPUT->heading(get_string('developerconversations', 'local_chatlogs'));
    echo local_chatlogs_search_table::form();
    $table = new local_chatlogs_converations_table('chatlogs-table');
    $table->define_baseurl($PAGE->url);
    $table->out(20, true);
}

echo $OUTPUT->footer();
