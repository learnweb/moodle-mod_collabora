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
 * Security check for curl access.
 *
 * It allowes curl access to the collabora server even if the url is blocked by core moodle.
 * See $CFG->curlsecurityblockedhosts
 *
 * @package   mod_collabora
 * @copyright 2021 Andreas Grabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class curl_security_helper extends \core\files\curl_security_helper {
    /** @var string */
    private $url;

    /**
     * Constructor.
     *
     * @param string $url
     */
    public function __construct($url) {
        $this->url = $url;
    }

    /**
     * Checks whether the given URL is blocked by checking its address and port number against the allow/block lists.
     * The behaviour of this function can be classified as strict, as it returns true for URLs which are invalid or
     * could not be parsed, as well as those valid URLs which were found in the blocklist.
     *
     * @param  string $urlstring the URL to check
     * @param  int    $notused   there used to be an optional parameter $maxredirects for a short while here, not used any more
     * @return bool   true if the URL is blocked or invalid and false if the URL is not blocked
     */
    public function url_is_blocked($urlstring, $notused = null) {
        if ($this->url == $urlstring) {
            return false;
        }

        return parent::url_is_blocked($urlstring);
    }
}
