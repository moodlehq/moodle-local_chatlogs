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
 * A scheduled task for syning chatlogs from the remote api.
 *
 * @package    local_chatlogs
 * @copyright  2017 Dan Poltawski <dan@moodle.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_chatlogs\task;

/**
 * A scheduled task for syning chatlogs from the remote api.
 *
 * @package    local_chatlogs
 * @copyright  2017 Dan Poltawski <dan@moodle.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_chatlogs extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('syncchatlogs', 'local_chatlogs');
    }

    /**
     * Sync chatlogs
     */
    public function execute() {
        $url = get_config('local_chatlogs', 'apiurl');
        $secret = get_config('local_chatlogs', 'apisecret');
        if (empty($url)) {
            mtrace('local_chatlogs/apiurl is not configured, nothing to do.');
            return;
        } else if (empty($secret)) {
            throw new \moodle_exception('API url is configured, but no secret is set.');
        }

        mtrace("Syncing chatlogs from {$url}");
        $importer = new \local_chatlogs\telegram_importer($url, $secret);
        $count = $importer->import();
        mtrace("Chatlogs sync complete. {$count} messages synced.");
    }
}
