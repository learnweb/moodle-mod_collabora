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

namespace mod_collabora\api;

/**
 * Class to handle callbacks from Collabora
 *
 * @package   mod_collabora
 * @copyright 2019 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface i_filesystem {
     /**
     * Send the file from the moodle file api.
     * This function implicitly calls a "die"!
     *
     * @param bool $forcedownload
     * @return void
     */
    public function send_groupfile(bool $forcedownload = true);

    /**
     * Is the file read-only?
     *
     * @return bool
     */
    public function is_readonly();

    /**
     * Update the stored file
     *
     * @param string $content
     * @return void
     */
    public function update_file($postdata);

    /**
     * Get the file from this instance
     *
     * @return \stored_file
     */
    public function get_file();

    /**
     * Unique identifier for the owner of the document.
     *
     * @return string
     */
    public function get_ownerid();

    /**
     * Unique identifier for the current user accessing the document.
     *
     * @return string
     */
    public function get_user_identifier();

    /**
     * Get the user name who is working on this document
     *
     * @return string
     */
    public function get_username();

}
