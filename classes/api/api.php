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
 * Class to handle callbacks from Collabora.
 *
 * @package   mod_collabora
 * @copyright 2019 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {
    /** @var string */
    protected $requesttype;
    /** @var string */
    protected $postdata;

    /** @var base_filesystem */
    protected $filesystem;

    /** Request to get the document from us */
    public const REQUEST_GETFILE = 'getfile';
    /** Request to put the document to us */
    public const REQUEST_PUTFILE = 'putfile';
    /** Request to get infos about the document */
    public const REQUEST_CHECKFILEINFO = 'checkfileinfo';

    /**
     * Get the request type and the fileid by using the relativepath and checking the postdata.
     *
     * @param  string $relativepath
     * @param  string $postdata
     * @return array  The request type and the fileid as array($requesttype, $fileid)
     */
    public static function get_request_and_fileid_from_path($relativepath, $postdata) {
        if (!preg_match('|/wopi/files/([^/]*)(/contents)?|', $relativepath, $matches)) {
            throw new \moodle_exception('invalidrequest', 'mod_collabora');
        }
        $fileid      = $matches[1];
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

        return [$requesttype, $fileid];
    }

    /**
     * Constructor.
     *
     * @param string          $requesttype
     * @param base_filesystem $filesystem
     * @param string|null     $postdata
     */
    public function __construct($requesttype, $filesystem, $postdata = null) {
        $this->postdata    = $postdata;
        $this->requesttype = $requesttype;
        $this->filesystem  = $filesystem;
    }

    /**
     * Handle request from WOPI server.
     *
     * @param  bool        $return if true the result is returned instead throwed through the output
     * @return void|string If $return is true a string is returned
     */
    public function handle_request($return = false) {
        switch ($this->requesttype) {
            case self::REQUEST_GETFILE:
                return $this->handle_getfile($return);
                break;
            case self::REQUEST_PUTFILE:
                return $this->handle_putfile($return);
                break;
            case self::REQUEST_CHECKFILEINFO:
                return $this->handle_checkfileinfo($return);
                break;
            default:
                send_header_404();
                die('unknown request');
        }
    }

    /**
     * Handle getfile requests.
     *
     * @param  bool        $return if true the result is returned instead throwed through the output
     * @return void|string If $return is true a string is returned
     */
    protected function handle_getfile($return = false) {
        if ($return) {
            return $this->filesystem->get_file()->get_content();
        }
        $this->filesystem->send_groupfile(false);
    }

    /**
     * Handle putfile requests.
     *
     * @param  bool      $return if true the result is returned instead throwed through the output
     * @return void|bool
     */
    protected function handle_putfile($return = false) {
        if ($this->filesystem->is_readonly()) {
            throw new \moodle_exception('readonly', 'mod_collabora');
        }
        $this->filesystem->update_file($this->postdata);
        if ($return) {
            return true;
        }
    }

    /**
     * Handle checkfileinfo requests.
     *
     * @param  bool        $return if true the result is returned instead throwed through the output
     * @return void|string If $return is true a string is returned
     */
    protected function handle_checkfileinfo($return = false) {
        $url    = new \moodle_url('/');
        $scheme = $url->get_scheme();
        $host   = $url->get_host();
        $origin = $scheme . '://' . $host;

        $file = $this->filesystem->get_file();

        $tz = date_default_timezone_get();
        date_default_timezone_set('UTC');

        $ret = (object) [
            'BaseFileName'            => clean_filename($file->get_filename()),
            'EnableShare'             => false,
            'LastModifiedTime'        => date('c', $file->get_timemodified()),
            'OwnerId'                 => $this->filesystem->get_ownerid(),
            'PostMessageOrigin'       => $origin,
            'Size'                    => $file->get_filesize(),
            'SupportsRename'          => false,
            'UserCanNotWriteRelative' => true,
            'UserCanRename'           => false,
            'UserCanWrite'            => !$this->filesystem->is_readonly(),
            'UserFriendlyName'        => $this->filesystem->get_username(),
            'UserId'                  => $this->filesystem->get_user_identifier(),
            'UserExtraInfo'           => ['avatar' => $this->filesystem->get_userpicture_url()],
            'IsAdminUser'             => $this->filesystem->show_server_audit(),
        ];

        date_default_timezone_set($tz);
        if ($return) {
            return json_encode($ret);
        }
        die(json_encode($ret));
    }
}
