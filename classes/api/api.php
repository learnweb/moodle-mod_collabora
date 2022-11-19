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
class api {

    /** @var string */
    private $requesttype;
    /** @var string */
    private $postdata;

    /** @var i_filesystem */
    private $filesystem;

    /** Request to get the document from us */
    const REQUEST_GETFILE = 'getfile';
    /** Request to put the document to us */
    const REQUEST_PUTFILE = 'putfile';
    /** Request to get infos about the document */
    const REQUEST_CHECKFILEINFO = 'checkfileinfo';

    public static function get_request_and_fileid_from_path($relativepath, $postdata) {
        if (!preg_match('|/wopi/files/([^/]*)(/contents)?|', $relativepath, $matches)) {
            throw new \moodle_exception('invalidrequest', 'mod_collabora');
        }
        $fileid = $matches[1];
        $hascontents = !empty($matches[2]);
        $haspostdata = !empty($postdata);
        if ($hascontents && $haspostdata) {
            $requesttype = self::REQUEST_PUTFILE;
        } else if ($hascontents && !$haspostdata) {
            $requesttype = self::REQUEST_GETFILE;
        } else if (!$hascontents && !$haspostdata) {
            $requesttype = self::REQUEST_CHECKFILEINFO;
        } else {
            // Unsupported request type (putrelativepath).
            throw new \moodle_exception('invalidrequest', 'mod_collabora');
        }

        return array($requesttype, $fileid);
    }

    /**
     * Constructor
     *
     * @param string $relativepath
     * @param i_filesystem $filesystem
     * @param string|null $postdata
     */
    public function __construct($requesttype, $filesystem, $postdata = null) {
        $this->postdata = $postdata;
        $this->requesttype = $requesttype;
        $this->filesystem = $filesystem;
    }

    /**
     * Handle request from WOPI server
     *
     * @return void
     */
    public function handle_request() {
        switch ($this->requesttype) {
            case self::REQUEST_GETFILE:
                $this->handle_getfile();
                break;
            case self::REQUEST_PUTFILE:
                $this->handle_putfile();
                break;
            case self::REQUEST_CHECKFILEINFO:
                $this->handle_checkfileinfo();
                break;
            default:
                send_header_404();
                die('unknown request');
        }
    }

    /**
     * Handle getfile requests.
     */
    protected function handle_getfile() {
        $this->filesystem->send_groupfile(false);
    }

    /**
     * Handle putfile requests.
     */
    protected function handle_putfile() {
        if ($this->filesystem->is_readonly()) {
            throw new \moodle_exception('readonly', 'mod_collabora');
        }
        $this->filesystem->update_file($this->postdata);
    }

    /**
     * Handle checkfileinfo requests.
     */
    protected function handle_checkfileinfo() {
        $file = $this->filesystem->get_file();

        $tz = date_default_timezone_get();
        date_default_timezone_set('UTC');

        $ret = (object)[
            'BaseFileName' => clean_filename($file->get_filename()),
            'OwnerId' => $this->filesystem->get_ownerid(),
            'Size' => $file->get_filesize(),
            'UserId' => $this->filesystem->get_user_identifier(),
            'UserFriendlyName' => $this->filesystem->get_username(),
            'UserCanWrite' => !$this->filesystem->is_readonly(),
            'UserCanNotWriteRelative' => true,
            'LastModifiedTime' => date('c', $file->get_timemodified()),
        ];

        date_default_timezone_set($tz);
        die(json_encode($ret));
    }
}
