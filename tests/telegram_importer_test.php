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
 * Telegram importer tests.
 *
 * @package local_chatlogs
 * @copyright 2017 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Telegram importer tests.
 *
 * @package local_chatlogs
 * @copyright 2017 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_chatlogs_telegram_importer_test extends advanced_testcase {

    public function test_import_when_empty() {
        $this->resetAfterTest();

        $importer = new local_chatlogs_testable_telegram_importer();
        $importer->set_mock_response([]);
        $importedcount  = $importer->import();
        $this->assertEmpty($importedcount);
    }

    public function test_import_fails_on_bad_json() {
        $this->resetAfterTest();
        $importer = new local_chatlogs_testable_telegram_importer();
        $importer->set_mock_response('{sdfs:sdf');

        $this->expectException(moodle_exception::class);
        $importer->import();
    }

    public function test_import() {
        global $DB;
        $this->resetAfterTest();

        $this->assertEmpty($DB->count_records('local_chatlogs_messages'));
        $this->assertEmpty($DB->count_records('local_chatlogs_conversations'));
        $this->assertEmpty($DB->count_records('local_chatlogs_participants'));

        // Run initial import of 2 messages.
        $importer = new local_chatlogs_testable_telegram_importer();
        $importer->set_mock_response([
            ['username' => 'danpoltawski', 'fullname' => 'Dan P',
                'message' => 'First message from Dan', 'timestamp' => '2017-01-29T12:30:37.601Z'],
            ['username' => 'danpoltawski', 'fullname' => 'Dan P',
                'message' => 'Second message from Dan', 'timestamp' => '2017-01-29T12:31:37.601Z'],
        ]);

        // To messages imported.
        $importedcount = $importer->import();
        $this->assertSame(2, $importedcount);

        // Run the import again. We should not get duplicate messages imported.
        $importedcount = $importer->import();
        $this->assertEmpty($importedcount);

        // Simulate a message added by Joe Bloggs.
        $importer->set_mock_response([
            ['username' => 'danpoltawski', 'fullname' => 'Dan P',
                'message' => 'First message from Dan', 'timestamp' => '2017-01-29T12:30:37.601Z'],
            ['username' => 'danpoltawski', 'fullname' => 'Dan P',
                'message' => 'Second message from Dan', 'timestamp' => '2017-01-29T12:31:37.601Z'],
            ['username' => 'joeblogs', 'fullname' => 'Dan P',
                'message' => 'Response from Joe', 'timestamp' => '2017-01-29T12:32:37.601Z'],
        ]);

        // Now we've got an extra message to import.
        $importedcount = $importer->import();
        $this->assertSame(1, $importedcount);

        // Verify db tables have been up updated.
        $this->assertSame(3, $DB->count_records('local_chatlogs_messages'));
        $this->assertSame(1, $DB->count_records('local_chatlogs_conversations'));
        $this->assertSame(2, $DB->count_records('local_chatlogs_participants'));

        // Simulate a chat message sent the next day, this should create a new conversation record.
        $importer->set_mock_response([
            ['username' => 'danpoltawski', 'fullname' => 'Dan P',
                'message' => 'First message from Dan', 'timestamp' => '2017-01-29T12:30:37.601Z'],
            ['username' => 'danpoltawski', 'fullname' => 'Dan P',
                'message' => 'Second message from Dan', 'timestamp' => '2017-01-29T12:31:37.601Z'],
            ['username' => 'joeblogs', 'fullname' => 'Joe B',
                'message' => 'Response from Joe', 'timestamp' => '2017-01-29T12:32:37.601Z'],
            ['username' => 'danpoltawski', 'fullname' => 'Dan P',
                'message' => 'Sorry Joe!', 'timestamp' => '2017-01-30T12:32:37.601Z'],
        ]);

        $importedcount = $importer->import();
        // Should only be the one new message to import.
        $this->assertSame(1, $importedcount);

        // And now two conversation records.
        $this->assertSame(2, $DB->count_records('local_chatlogs_conversations'));
    }

    public function test_import_with_existing_data() {
        global $DB;
        $this->resetAfterTest();
        // Insert some chatlog data.
        $conversations = [
            ['conversationid' => '1', 'timestart' => '1358138860', 'timeend' => '1358140681', 'messagecount' => 2],
            ['conversationid' => '2', 'timestart' => '1358142505', 'timeend' => '1358142507', 'messagecount' => 1],
        ];
        $DB->insert_records('local_chatlogs_conversations', $conversations);
        $messages = [
            ['conversationid' => '1', 'fromemail' => 'danpoltawski@telegram.me',
                'timesent' => '1358138860', 'timejava' => '1358138860000', 'message' => 'Testing'],
            ['conversationid' => '1', 'fromemail' => 'danpoltawski@telegram.me',
                'timesent' => '1358138958', 'timejava' => '1358138958000', 'message' => 'Testing'],
            ['conversationid' => '2', 'fromemail' => 'danpoltawski@telegram.me',
                'timesent' => '1358142505', 'timejava' => '1358142505000', 'message' => '123'],
        ];
        $DB->insert_records('local_chatlogs_messages', $messages);

        $participants = [
            ['fromemail' => 'danpoltawski@telegram.me', 'nickname' => 'danpoltawski'],
        ];
        $DB->insert_records('local_chatlogs_participants', $participants);

        $this->assertSame(3, $DB->count_records('local_chatlogs_messages'));
        $this->assertSame(2, $DB->count_records('local_chatlogs_conversations'));
        $this->assertSame(1, $DB->count_records('local_chatlogs_participants'));

        // Run import of new 2 messages.
        $importer = new local_chatlogs_testable_telegram_importer();
        $importer->set_mock_response([
            ['username' => 'danpoltawski', 'fullname' => 'Dan P',
                'message' => 'First message from Dan', 'timestamp' => '2017-01-29T12:30:37.601Z'],
            ['username' => 'bob', 'fullname' => 'Bob',
                'message' => 'Message from Bob', 'timestamp' => '2017-01-29T12:31:37.601Z'],
        ]);

        $importedcount = $importer->import();
        $this->assertSame(2, $importedcount);

        // 2 extra messages.
        $this->assertSame(5, $DB->count_records('local_chatlogs_messages'));
        // 1 extra conversation.
        $this->assertSame(3, $DB->count_records('local_chatlogs_conversations'));
        // 1 extra participant.
        $this->assertSame(2, $DB->count_records('local_chatlogs_participants'));
    }

    public function test_problematic_mysql_emojis() {
        global $DB;
        $this->resetAfterTest();

        // Run initial import of 2 messages.
        $importer = new local_chatlogs_testable_telegram_importer();
        $importer->set_mock_response([
            ['username' => 'davidm', 'fullname' => 'DavidMonllao',
                'message' => '👍', 'timestamp' => '2017-01-29T12:31:37.601Z'],
        ]);

        // To messages imported.
        $importedcount = $importer->import();
        $this->assertSame(1, $importedcount);
        $this->assertSame(1, $DB->count_records('local_chatlogs_messages'));
    }

}

/**
 * Testable importer which generates mock server response.
 * @package local_chatlogs
 * @copyright 2017 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_chatlogs_testable_telegram_importer extends local_chatlogs\telegram_importer {
    /** @var string|array mock response */
    private $mockresponse = null;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('https://example.org/api/', 'secret');
    }

    /**
     * Mocking response from simple json api from https://github.com/danpoltawski/hubot-log-to-pgsql
     * @param \moodle_url $url to call
     * @return string
     */
    protected function call_api(\moodle_url $url) {
        if (is_string($this->mockresponse)) {
            // Allow simulation of bad json etc.
            return $this->mockresponse;
        }

        // Simulate server-side filtering of timestamp.
        if ($timestamp = $url->param('aftertimestamp')) {
            $afterdate = new DateTime($timestamp);
            $resp = array_filter($this->mockresponse, function ($row) use($afterdate) {
                return new DateTime($row->timestamp) > $afterdate;
            });
            return json_encode(array_values($resp));
        }

        return json_encode($this->mockresponse);
    }

    /**
     * Set mock response for importer api call
     * @param string|array $data
     */
    public function set_mock_response($data) {
        if (is_string($data)) {
            $this->mockresponse = $data;
            return;
        }

        $this->mockresponse = [];
        foreach ($data as $row) {
            $this->mockresponse[] = (object) $row;
        }
    }
}
