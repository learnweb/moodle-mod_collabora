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

namespace mod_collabora\task;

/**
 * Clean up task
 *
 * @package   mod_collabora
 * @copyright 2022 Andreas Grabs <moodle@grabs-edv.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup extends \core\task\scheduled_task {

    /**
     * Returns localized name of this task.
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_name() {

        return get_string('task_cleanup', 'mod_collabora');
    }

    public function execute() {
        global $DB;

        \mod_collabora\api\collabora_fs::remove_unused_tokens();
    }
}