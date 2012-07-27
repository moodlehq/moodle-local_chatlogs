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
 * Provides library functions used by plugin
 *
 * @package     local_chatlogs
 * @copyright   2012 Dan Poltawski <dan@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->dirroot.'/local/chatlogs/lib.php');

/**
 * Table listing jabber conversations
 *
 * @copyright   2012 Dan Poltawski <dan@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_chatlogs_converations_table extends table_sql {

    /**
     * Sets up the table_sql parameters
     *
     * @param string $uniqueid unique id of form
     */
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);
        $this->set_attribute('class', 'devchatslist generaltable generalbox');
        if (!isset($this->sql)) {
            $this->sql = new stdClass();
        }
        $this->sql->fields = 'conversationid, messagecount, timestart, timeend, (timestart - timeend) AS duration';
        $this->sql->from = '{local_chatlogs_conversations}';
        $this->sql->where = 'messagecount > 0';
        $this->sql->params = array();

        $this->define_columns(array('conversationid', 'participants', 'messagecount', 'timestart', 'timeend', 'duration'));
        $this->define_headers(array('ID', 'Participants', 'Messages', 'Start', 'End', 'Duration'));
        $this->no_sorting('participants');
        $this->collapsible(false);
        $this->sortable(true, 'timeend', SORT_DESC);
    }

    /**
     * Generate the participants column by querying the chatlogs and dicsocvering
     * participants for this column
     *
     * @param object $row row data
     * @return string HTML for the particupants column
     */
    public function col_participants($row) {
        global $DB;

        $sql = 'SELECT p.fromemail, p.nickname
                FROM {local_chatlogs_messages} m
                JOIN {local_chatlogs_participants} p
                    ON m.fromemail = p.fromemail
                WHERE m.conversationid = ? GROUP BY p.fromemail, p.nickname';
        $participants = $DB->get_records_sql_menu($sql, array($row->conversationid));
        $participants = implode(', ', $participants);

        return $this->conversation_link($row->conversationid, $participants);
    }

    /**
     * Generate conversationid cell
     *
     * @param stdClass $row row data
     * @return string HTML for the column
     */
    public function col_conversationid($row) {
        return $this->conversation_link($row->conversationid, $row->conversationid);
    }

    /**
     * Generate messagecount cell
     *
     * @param stdClass $row row data
     * @return string HTML for the column
     */
    public function col_messagecount($row) {
        return $this->conversation_link($row->conversationid, $row->messagecount);
    }

    /**
     * Generate duration cell
     *
     * @param stdClass $row row data
     * @return string HTML for the column
     */
    public function col_duration($row) {
        return format_time($row->duration);
    }

    /**
     * Generate timestart cell
     *
     * @param stdClass $row row data
     * @return string HTML for the column
     */
    public function col_timestart($row) {
        return userdate($row->timestart);
    }

    /**
     * Generate timeend cell
     *
     * @param stdClass $row row data
     * @return string HTML for the column
     */
    public function col_timeend($row) {
        return userdate($row->timeend);
    }

    /**
     * Generate a link to a specific conversation
     *
     * @param int $id converationid of converation
     * @param string $text text contained within link
     * @return string HTML of link
     */
    private function conversation_link($id, $text) {
        $url = new moodle_url('/local/chatlogs/index.php', array('conversationid' => $id));

        return html_writer::link($url, $text);
    }
}


/**
 * A jabber conversation
 *
 * @copyright   2012 Dan Poltawski <dan@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_chatlogs_conversation {
    /** @var object From from the chatlogs_conversations table */
    private $conversation = null;
    /**
     * Html link to previous conversation or empty string if none
     * @var string
     * */
    private $previouslink = null;
    /**
     * Html link to next conversation or empty string if none
     * @var string
     * */
    private $nextlink = null;

    /**
     * Creates a conversation object based on converationid
     * @param int $conversationid id of conversation to creaet
     */
    public function __construct($conversationid) {
        global $DB;

        $this->conversation = $DB->get_record('local_chatlogs_conversations',
            array('conversationid' => $conversationid), '*', MUST_EXIST);
    }

    /**
     * Get a link to the previous conversation 
     *
     * @return string the html of previous converation
     */
    private function get_previous_link() {
        global $DB;

        if ($this->previouslink !== null) {
            return $this->previouslink;
        }
        $this->previouslink = '';

        $sql = 'SELECT * FROM {local_chatlogs_conversations}
                WHERE  id != ? AND timeend < ? AND messagecount > 0
                ORDER BY timeend DESC LIMIT 1';
        $previous = $DB->get_record_sql($sql, array($this->conversation->id, $this->conversation->timeend));

        if ($previous) {
            $url = new moodle_url('/local/chatlogs/index.php',
                array('conversationid' => $previous->conversationid));
            $this->previouslink = html_writer::link($url, '&#x25C4; '.$previous->messagecount.' messages',
                array('class' => 'previouslink'));
        }

        return $this->previouslink;
    }

    /**
     * Get a link to the next conversation 
     *
     * @return string the html of next converation
     */
    private function get_next_link() {
        global $DB;

        if ($this->nextlink !== null) {
            return $this->nextlink;
        }
        $this->nextlink = '';

        $sql = 'SELECT * FROM {local_chatlogs_conversations}
                WHERE  id != ? AND timeend > ? AND messagecount > 0
                ORDER BY timeend ASC LIMIT 1';
        $next = $DB->get_record_sql($sql, array($this->conversation->id, $this->conversation->timeend));

        if ($next) {
            $url = new moodle_url('/local/chatlogs/index.php', array('conversationid' => $next->conversationid));
            $this->nextlink = html_writer::link($url, $next->messagecount.' messages &#x25BA;',
                array('class' => 'nextlink'));
        }

        return $this->nextlink;
    }

    /**
     * Generates header html of conversation
     *
     * @return the header html of a conversation
     */
    public function conversation_header() {
        global $OUTPUT;

        $starttime = userdate($this->conversation->timestart);
        $duration = format_time($this->conversation->timeend - $this->conversation->timestart);

        $header = new html_table();
        $header->width = '100%';
        $header->wrap  = array('nowrap', '', 'nowrap');
        $header->size = array('', '100%', '');
        $header->data[] = array(
            $OUTPUT->heading($this->get_previous_link()),
            $OUTPUT->heading("$starttime, for $duration"),
            $OUTPUT->heading($this->get_next_link()),
        );

        return html_writer::table($header);
    }

    /**
     * Generates footer html of conversation
     *
     * @return the footer html of a conversation
     */
    public function conversation_footer() {
        global $OUTPUT;

        $footer = new html_table();
        $footer->wrap  = array('nowrap', '', 'nowrap');
        $footer->width = '100%';
        $footer->size = array('', '100%', '');
        $footer->data[] = array(
            $OUTPUT->heading($this->get_previous_link()),
            $OUTPUT->heading(html_writer::link(new moodle_url('/local/chatlogs/index.php'),
                get_string('allconversations', 'local_chatlogs'))),
            $OUTPUT->heading($this->get_next_link()),
        );

        return html_writer::table($footer);
    }

    /**
     * Generates html of conversation
     *
     * @return the html representation of a conversation
     */
    public function render() {
        global $DB, $OUTPUT, $PAGE;

        // adds a handy left/right arrow shortcut
        $PAGE->requires->yui_module('moodle-local_chatlogs-keyboard', 'M.local_chatlogs.init_keyboard');

        $sql = 'SELECT m.id AS messageid, m.fromemail, m.fromplace, m.timesent,
                m.message, p.nickname, p.userid, '.user_picture::fields('u').
               ' FROM {local_chatlogs_messages} m
                LEFT JOIN {local_chatlogs_participants} p
                    ON m.fromemail = p.fromemail
                LEFT JOIN {user} u
                    ON p.userid = u.id
                WHERE m.conversationid = :conversationid
                ORDER BY m.timesent';

        $rs = $DB->get_recordset_sql($sql, array('conversationid' => $this->conversation->conversationid));
        echo $this->conversation_header();

        $table = new html_table();
        $table->attributes['class'] = 'devchat';
        $footer->wrap  = array('nowrap', 'nowrap', '');

        foreach ($rs as $message) {
            $time = userdate($message->timesent, "%I:%M:%S %P");

            $namecell = new html_table_cell();
            $namecell->attributes['title'] = $message->fromemail.'/'.$message->fromplace;
            $namecell->attributes['class'] = 'userinfo usersays';
            if (!empty($message->userid)) {
                $namecell->text = fullname($message) . html_writer::empty_tag('br');
            }else{
                $namecell->text = $message->nickname . html_writer::empty_tag('br');
            }
            $namecell->text.= html_writer::link('#c'.$message->messageid, $time,  array('class' => 'jabbertime'));

            $messagecell = new html_table_cell();
            $messagecell->attributes['class'] = 'talkmessage';

            if (trim(substr($message->message, 0, 4)) == '/me') {
                $namecell->attributes['class'] = 'userinfo useractions';
                $messagecell->attributes['class'] = 'chataction';
                $messagecell->text.= $message->nickname.' ';
                $message->message = substr(trim($message->message), 4);
            }

            $messagecell->text.= format_text($message->message, FORMAT_MOODLE, array('para'=>false));

            $imagecell = new html_table_cell();
            if (!empty($message->userid)) {
                $imagecell->attributes['class'] = 'userimage';
                $imagecell->text = $OUTPUT->user_picture($message);
            }

            $row = new html_table_row();
            $row->id = 'c'.$message->messageid;
            $row->cells = array($imagecell, $namecell, $messagecell);
            $table->data[] = $row;
        }
        $rs->close();

        echo html_writer::table($table);

        echo $this->conversation_footer();
    }
}

/**
 * Table listing jabber search results
 *
 * @copyright   2012 Dan Poltawski <dan@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_chatlogs_search_table extends table_sql {
    private $searchterm = null;

    /**
     * Sets up the table_sql parameters
     *
     * @param string $uniqueid unique id of form
     * @param string $searchterm of search
     */
    public function __construct($uniqueid, $searchterm) {
        global $DB;

        parent::__construct($uniqueid);

        $this->searchterm = $searchterm;
        $this->set_attribute('class', 'devchat');

        if (!isset($this->sql)) {
            $this->sql = new stdClass();
        }

        $this->sql->fields = 'm.id AS messageid, m.fromemail, m.fromplace, m.timesent,
                              m.message, m.conversationid, p.nickname,
                              p.userid, '.user_picture::fields('u');

        $this->sql->from = '{local_chatlogs_messages} m
                            LEFT JOIN {local_chatlogs_participants} p
                                ON m.fromemail = p.fromemail
                            LEFT JOIN {user} u ON p.userid = u.id';

        $this->sql->where = $DB->sql_like('m.message', ':search');
        $this->sql->params = array('search' => '%'.$searchterm.'%');


        $this->define_columns(array('timesent', 'userpic', 'userid', 'message'));
        $this->define_headers(array('Timesent', '', 'User', 'Message'));
        $this->column_class('timesent', 'userinfo');
        $this->column_class('userpic', 'userpic');
        $this->column_class('userid', 'userinfo usersays');
        $this->column_class('message', 'talkmessage');
        $this->no_sorting('message');
        $this->collapsible(false);
        $this->sortable(true, 'timesent', SORT_DESC);
    }

    /**
     * Generate timesent cell
     *
     * @param stdClass $row row data
     * @return string HTML for the column
     */
    public function col_timesent($row) {
        $link = new moodle_url('/local/chatlogs/index.php');
        $link->params( array('conversationid' => $row->conversationid));
        $link->set_anchor('c'.$row->messageid);

        return html_writer::link($link, userdate($row->timesent),  array('class' => 'jabbertime'));
    }

    /**
     * Generate userid cell
     *
     * @param stdClass $row row data
     * @return string HTML for the column
     */
    public function col_userid($row) {
        if (empty($row->userid)) {
            return $row->nickname;
        }else{
            return fullname($row);
        }
    }

    /**
     * Generate userpic cell
     *
     * @param stdClass $row row data
     * @return string HTML for the column
     */
    public function col_userpic($row) {
        global $OUTPUT;
        if (!empty($row->userid)) {
            return $OUTPUT->user_picture($row);
        }
        return '';
    }

    /**
     * Generate message cell
     *
     * @param stdClass $row row data
     * @return string HTML for the column
     */
    public function col_message($row) {
        $text = '';

        if (trim(substr($row->message, 0, 4)) == '/me') {
            // convert /me
            $text.= $row->nickname.' ';
            $row->message = substr(trim($row->message), 4);
        }

        $message = format_text($row->message, FORMAT_MOODLE, array('para' => false));
        $text .= highlight($this->searchterm, $message);

        return $text;
    }

    /**
     * Returns the search form for searching chat messages
     *
     * @param string $searchtext text searched for
     * @return string HTML for searchform
     */
    public static function form($searchtext = '') {
        $o = '';

        $url = new moodle_url('/local/chatlogs/index.php');

        $o.= html_writer::start_tag('div', array('class' => 'searchform'));
        $o.= html_writer::start_tag('form', array('method' => 'get', 'action' => $url->out()));
        $o.= html_writer::empty_tag('input', array('type' => 'text', 'name' => 'q',
             'value' => $searchtext, 'maxlength' => 100, 'size' => 20));
        $o.= html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('searchmessages', 'local_chatlogs')));
        $o.= html_writer::end_tag('form');
        $o.= html_writer::end_tag('div');

        return $o;
    }
}

/**
 * A require_capability() like function for this module.
 *
 * As we can't use capbilties alone this emulates the acceslib function to prevent having to do
 * it in ever file.
 */
function  local_chatlogs_require_capability() {
    if (!local_chatlogs_can_access()) {
        print_error('nopermissions', 'error', '',  get_string('viewchatlogs', 'local_chatlogs'));
        die;
    }
}
