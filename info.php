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
 * Displays info of how to get to the chat logs
 *
 * @package     local_chatlogs
 * @copyright   2012 Dan Poltawski <dan@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(dirname(dirname(__FILE__))).'/config.php');
require($CFG->dirroot.'/local/chatlogs/locallib.php');

require_login(SITEID, false);
local_chatlogs_require_capability();

$PAGE->set_pagelayout('standard');
$PAGE->set_url(new moodle_url('/local/chatlogs/info.php'));
$PAGE->set_title(get_string('info', 'local_chatlogs'));
$PAGE->set_heading(get_string('info', 'local_chatlogs'));

echo $OUTPUT->header();
echo $OUTPUT->heading('Moodle developers chat');
echo $OUTPUT->box_start('text-center lead');
?>

<p>Historically, Moodle developers used a Jabber chat room for synchronous
discussions. Since February 1st 2017, the chat has been moved to <a
href="https://telegram.org/">Telegram</a>. Developers are encouraged to join
the chat at <a href="https://telegram.me/moodledev">telegram.me/moodledev</a>.</p>

<hr />

<?php
echo $OUTPUT->pix_icon('telegram', 'Moodle dev and Telegram logos', 'local_chatlogs',
    ['width' => 118, 'class' => 'img-responsive']);
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
