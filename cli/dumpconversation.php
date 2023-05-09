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
 * CLI script to dump a given conversation to standard output.
 *
 * @package     local_chatlogs
 * @subpackage  cli
 * @copyright   2014 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->dirroot.'/local/chatlogs/locallib.php');
require_once($CFG->libdir.'/clilib.php');

$usage = "
Dumps the given conversation to the standard output.

Usage:

    $ php dumpconversation.php [--format=<format>] [-h|--help] <conversationid>

Options:

    --format    The output format. Supports 'mediawiki' for now.
    -h, --help      Prints this usage information.

";

list($options, $unrecognized) = cli_get_params(
    array(
        'help' => false,
        'format' => 'mediawiki',
    ), array(
        'h' => 'help',
    )
);

if (!empty($options['help']) or count($unrecognized) <> 1) {
    cli_error($usage);
}

$format = $options['format'];

if ($format !== 'mediawiki') {
    cli_error('Unsupported output format '.$format);
}

$conversationid = reset($unrecognized);

if (!preg_match('/^[1-9][0-9]*$/', $conversationid)) {
    cli_error('Invalid conversation id '.$conversationid);
}

$sql = "SELECT m.id AS messageid, m.fromemail, m.fromplace, m.timesent,
               m.message, p.nickname, p.userid, u.lastname, u.firstname
          FROM {local_chatlogs_messages} m
     LEFT JOIN {local_chatlogs_participants} p ON m.fromemail = p.fromemail
     LEFT JOIN {user} u ON p.userid = u.id
         WHERE m.conversationid = :conversationid
         ORDER BY m.timesent";

$rs = $DB->get_recordset_sql($sql, array('conversationid' => $conversationid));

if (!$rs->valid()) {
    cli_error('No data found');
}

echo local_chatlogs_dumpconversation_header($format);

foreach ($rs as $message) {
    $message->displaytimesent = userdate($message->timesent, "%H:%M:%S UTC", 0);

    if (empty($message->userid)) {
        $message->displayname = $message->nickname;
    } else {
        $message->displayname = $message->firstname.' '.$message->lastname;
    }

    if (trim(substr($message->message, 0, 4)) === '/me') {
        $message->displayname = '*'.$message->displayname;
        $message->message = substr(trim($message->message), 4);
    }

    echo local_chatlogs_dumpconversation_message($message, $format);
}

echo local_chatlogs_dumpconversation_footer($format);

exit(0);

/**
 * Local functions follow
 */

/**
 * Return the formatted header.
 *
 * @param string $format
 * @return string
 */
function local_chatlogs_dumpconversation_header($format) {

    if ($format === 'mediawiki') {
        return '{| class="nicetable"'.PHP_EOL;
    }
}

/**
 * Return the formatted footer.
 *
 * @param string $format
 * @return string
 */
function local_chatlogs_dumpconversation_footer($format) {

    if ($format === 'mediawiki') {
        return '|}'.PHP_EOL;
    }
}

/**
 * Return a formatted message.
 *
 * @param stdClass $message
 * @param string $format
 * @return string
 */
function local_chatlogs_dumpconversation_message(stdClass $message, $format) {

    $out = '';

    if ($format === 'mediawiki') {
        $out .= '|-'.PHP_EOL;
        $out .= '| <span style="white-space:pre">'.$message->displayname.'</span>'.PHP_EOL;
        $out .= '| '.str_replace('|', '<nowiki>|</nowiki>', $message->message).PHP_EOL;
        $out .= '| <span style="white-space:pre">'.$message->displaytimesent.'</span>'.PHP_EOL;
    }

    return $out;
}
