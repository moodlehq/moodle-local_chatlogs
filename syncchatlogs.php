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
 * CLI script to perform the fetch all the new chat logs to the plugin
 *
 * @package     local_chatlogs
 * @copyright   2012 Dan Poltawski <dan@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__.'/../../config.php');

// Only php cli allowed.
if (isset($_SERVER['REMOTE_ADDR'])) {
    if ($_SERVER['REMOTE_ADDR'] != "174.123.154.58") {
        print_error('cronerrorclionly', 'admin');
        exit;
    } else {
        $ismoodlebot = true;
    }
}

$apiurl = get_config('local_chatlogs', 'apiurl');
if (!empty($apiurl)) {
    die("local_chatlogs/apiurl is set, please use the scheduled task instead.\n");
}

$conversationgap = 30 * 60;   // 30 minutes gap.

// Include config for DB details.
require('sync-config.php');

$chatdb = moodle_database::get_driver_instance('mysqli', 'native');
// @codingStandardsIgnoreStart
// phpcs:disable
if (!$chatdb->connect($SYNC_DBHOST, $SYNC_DBUSER, $SYNC_DBPASSWORD, $SYNC_DBNAME, $SYNC_DBPREFIX, array('dbport'=> $SYNC_DBPORT))) {
    // @codingStandardsIgnoreEnd
    // phpcs:enable
    throw new dbtransfer_exception('notargetconectexception', null, "$CFG->wwwroot/mod/cvsadmin/syncchatlogs.php");
}

$lastmessage = $DB->get_record_sql('SELECT * FROM {local_chatlogs_messages} ORDER BY timejava DESC LIMIT 1');
if (empty($lastmessage)) {
    $lastmessage = new stdClass;
    $lastmessage->conversationid = 0;
    $lastmessage->timejava = 0;
    $lastmessage->timesent = 0;
}

if (!isset($ismoodlebot)) {
    mtrace("Fetching messages since ".userdate($lastmessage->timesent));
}

$count = 0;
$convid = 0;

// @codingStandardsIgnoreStart
// phpcs:disable
if ($rs = $chatdb->get_recordset_sql("SELECT * FROM $SYNC_TABLE WHERE (logTime > ?) ORDER BY logTime ASC", array($lastmessage->timesent))) {
    // @codingStandardsIgnoreEnd
    // phpcs:enable

    foreach ($rs as $data) {
        if (empty($data->body)) {    // Bogus message.
            continue;
        }

        $nameparts = explode('/', $data->sender);

        $message = new stdClass;
        $message->fromemail = $nameparts[0];
        $message->fromplace = $nameparts[1];
        $message->fromnick = $data->nickname;
        $message->timesent = $data->logtime;
        $message->timejava = $data->logtime * 1000;
        $message->message = $data->body;

        // Work out which conversation this is part of and update that.
        if ($message->timesent - $lastmessage->timesent < $conversationgap) {
            $message->conversationid = $lastmessage->conversationid;   // Same.

            $conversation = $DB->get_record('local_chatlogs_conversations',
                array('conversationid' => $message->conversationid), '*', MUST_EXIST);
            $conversation->timeend = $message->timesent;
            $conversation->messagecount = $conversation->messagecount + 1;
            if (!$DB->update_record('local_chatlogs_conversations', $conversation)) {
                exit;
            }


        } else {
            $message->conversationid = $lastmessage->conversationid + 1;   // New.

            // Add this new conversation to the conversation table.
            $conversation = new object;
            $conversation->conversationid = $message->conversationid;
            $conversation->timestart = $message->timesent;
            $conversation->timeend = $message->timesent + 1;
            $conversation->messagecount = 1;
            if (!$DB->insert_record('local_chatlogs_conversations', $conversation)) {
                exit;
            }
        }

        if (!$message->id = $DB->insert_record('local_chatlogs_messages', $message)) {
            exit;
        }

        $lastmessage = $message;

        $count++;

        // Now check that they have a registered name and if not, register them.

        if (empty($currentparticipant[$message->fromemail])) {
            if (!$participant = $DB->get_record('local_chatlogs_participants', array('fromemail' => $message->fromemail))) {
                $participant = new object;
                $participant->fromemail = $message->fromemail;
                $participant->nickname = $message->fromnick;
                $DB->insert_record('local_chatlogs_participants', $participant);   // Add them to the list.
            }
            $currentparticipant[$message->fromemail] = true;  // No need to check again today.
        }


    }
    $rs->close();

}

if (!isset($ismoodlebot)) {
    mtrace("Inserted $count new messages");
} else {
    $conversationid = $DB->get_field_sql(
        "SELECT conversationid FROM {local_chatlogs_conversations} ORDER BY conversationid desc LIMIT 1");
    echo "Synchronised chat logs ($count new messages) - URL: http://moodle.org/local/chatlogs/index.php?conversationid=" .
        $conversationid."\n";
}

// Clean up orphan conversations.

$firstone = true;
$pushtonext = 0;

if ($conversations = $DB->get_records('local_chatlogs_conversations', null, 'conversationid desc')) {

    foreach ($conversations as $conversation) {

        if ($pushtonext) {  // Copy those to this.
            $DB->execute(
                "UPDATE {local_chatlogs_messages} SET conversationid = $conversation->conversationid WHERE conversationid = ?",
                array($pushtonext));
            $DB->execute(
                "UPDATE {local_chatlogs_conversations} SET messagecount = 0 WHERE conversationid = ?",
                array($pushtonext));
            $pushtonext = 0;
        }

        if (!$firstone && $conversation->messagecount == 1) {
            $pushtonext = $conversation->conversationid;
        }

        $firstone = false;
    }
}
