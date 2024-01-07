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
 * Endpoint for callback from Collabora.
 *
 * @package   mod_collabora
 * @copyright 2019 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_collabora\api\api;
use mod_collabora\api\collabora_fs;

// This script is called by the Collabora server and does not need cookies!
define('NO_MOODLE_COOKIES', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

$relativepath = get_file_argument();
$accesstoken  = required_param('access_token', PARAM_ALPHANUMEXT);
$postdata     = file_get_contents('php://input');

list($requesttyp, $fileid) = api::get_request_and_fileid_from_path($relativepath, $postdata);
$collaborafs               = collabora_fs::get_instance_by_fileid($fileid, $accesstoken);
$api                       = new api($requesttyp, $collaborafs, $postdata);
$api->handle_request();
