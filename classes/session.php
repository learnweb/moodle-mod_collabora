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

namespace mod_collabora;

use mod_collabora\api\collabora_fs;

/**
 * Helper class for sessions.
 *
 * Currently it is only used to find out whether or not a user has a valid session.
 *
 * @package    mod_collabora
 *
 * @author     Andreas Grabs <moodle@grabs-edv.de>
 * @copyright  2025 Humboldt-Universität zu Berlin <moodle-support@cms.hu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class session {

    /**
     * Does the PHP session with given id exist?
     *
     * The session must exist in actual session backend and the session must not be timed out.
     * With this check, we ensure that the callback calls belong to a user who is actually logged in.
     *
     * @param string $sid
     * @return bool
     */
    public static function session_exists($sid) {
        $handlerclass = \core\session\manager::get_handler_class();
        /** @var \core\session\handler $handler */
        $handler = new $handlerclass();
        try {
            $handler->init();
        } catch (\Exception $e) {
            if (session_id() == $sid) {
                return true;
            }
            debugging($e->getMessage());
            return false;
        }
        return $handler->session_exists($sid);
    }
}
