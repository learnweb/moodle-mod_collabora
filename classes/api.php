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
 * Class to handle callbacks from Collabora
 *
 * @package   mod_collabora
 * @copyright 2019 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_collabora;

defined('MOODLE_INTERNAL') || die();

class api {
    /** @var int */
    private $userid;
    /** @var string */
    private $requesttype;
    /** @var string */
    private $postdata;

    /** @var \context_module */
    private $context;
    /** @var \cm_info */
    private $cm;
    /** @var object */
    private $course;
    /** @var object */
    private $docrecord;
    /** @var bool */
    private $isgroupmember;

    const REQUEST_GETFILE = 'getfile';
    const REQUEST_PUTFILE = 'putfile';
    const REQUEST_CHECKFILEINFO = 'checkfileinfo';

    public function __construct($relativepath, $accesstoken, $postdata = null) {
        $this->postdata = $postdata;
        $this->userid = $this->get_userid_from_token($accesstoken);
        $fileid = $this->parse_request($relativepath, (bool)$postdata);
        $this->parse_fileid($fileid);
    }

    public function handle_request() {
        $fnname = "handle_{$this->requesttype}";
        $this->$fnname();
    }

    private function get_userid_from_token($accesstoken) {
        global $DB;
        return (int)$DB->get_field('collabora_token', 'userid', ['token' => $accesstoken], MUST_EXIST);
    }

    /**
     * Extract the request type (to $this->requesttype) and the fileid (returned)
     * @param string $relativepath
     * @param bool $haspostdata
     * @return string
     */
    private function parse_request($relativepath, $haspostdata) {
        if (!preg_match('|/wopi/files/([^/]*)(/contents)?|', $relativepath, $matches)) {
            throw new \moodle_exception('invalidrequest', 'mod_collabora');
        }
        $fileid = $matches[1];
        $hascontents = isset($matches[2]);
        if ($hascontents && $haspostdata) {
            $this->requesttype = self::REQUEST_PUTFILE;
        } else if ($hascontents && !$haspostdata) {
            $this->requesttype = self::REQUEST_GETFILE;
        } else if (!$hascontents && !$haspostdata) {
            $this->requesttype = self::REQUEST_CHECKFILEINFO;
        } else {
            // Unsupported request type (putrelativepath).
            throw new \moodle_exception('invalidrequest', 'mod_collabora');
        }
        return $fileid;
    }

    /**
     * Parse the fileid to extract the contextid and groupid.
     * Sets $this->context, $this->isgroupmember, $this->cm, $this->course, $this->docrecord.
     *
     * @param string $fileid
     */
    private function parse_fileid($fileid) {
        global $DB;
        $parts = explode('_', $fileid);
        if (count($parts) < 2) {
            throw new \moodle_exception('invalidfileid', 'mod_collabora');
        }
        list($contextid, $groupid) = $parts;

        // Check the context.
        $this->context = \context::instance_by_id($contextid);
        if ($this->context->contextlevel !== CONTEXT_MODULE) {
            throw new \moodle_exception('invalidcontextlevel', 'mod_collabora');
        }
        require_capability('mod/collabora:view', $this->context, $this->userid);

        // Check the group access.
        $this->isgroupmember = true;
        list($this->course, $this->cm) = get_course_and_cm_from_cmid($this->context->instanceid, 'collabora');
        $groupmode = groups_get_activity_groupmode($this->cm);
        if ($groupmode == NOGROUPS) {
            if ($groupid != 0) {
                throw new \moodle_exception('invalidgroupid', 'mod_collabora');
            }
        } else {
            if ($groupid == 0) {
                throw new \moodle_exception('invalidgroupid', 'mod_collabora');
            }
            if (!$DB->record_exists('groups', ['id' => $groupid, 'courseid' => $this->course->id])) {
                throw new \moodle_exception('invalidgroupid', 'mod_collabora');
            }
            $this->isgroupmember = groups_is_member($groupid, $this->userid);
            if ($groupmode == SEPARATEGROUPS && !$this->isgroupmember) {
                require_capability('moodle/site:accessallgroups', $this->context, $this->userid);
            }
        }

        // Load the document metadata.
        $this->docrecord = $DB->get_record('collabora_document', ['collaboraid' => $this->cm->instance, 'groupid' => $groupid],
                                           '*', MUST_EXIST);
    }

    /**
     * Is the file read-only?
     * @return bool
     */
    private function is_readonly() {
        if (!$this->isgroupmember && !has_capability('moodle/site:accessallgroups', $this->context, $this->userid)) {
            return true; // Not a member of the relevant group => definitely has no access.
        }
        if ($this->docrecord->locked) {
            // Locked - only users with the ability to edit locked documents can edit.
            return !has_capability('mod/collabora:editlocked', $this->context, $this->userid);
        }
        return false;
    }

    /**
     * @return \stored_file
     */
    private function get_stored_file() {
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->context->id, 'mod_collabora', collabora::FILEAREA_GROUP,
                                     $this->docrecord->groupid, '', false, 0, 0, 1);
        $file = reset($files);
        if (!$file) {
            // The collabora_document record exists, but, for some reason, no file.
            throw new \moodle_exception('missingfile', 'mod_collabora');
        }
        return $file;
    }

    /**
     * Unique identifier for the owner of the document.
     */
    private function get_ownerid() {
        global $CFG;
        // I think all the files should have the same owner, so just using the Moodle site id?
        return $CFG->siteidentifier;
    }

    /**
     * Unique identifier for the current user accessing the document.
     */
    private function get_user_identifier() {
        global $CFG;
        $identifier = $CFG->siteidentifier.'_user_'.$this->userid;
        return sha1($identifier);
    }

    private function get_user() {
        return \core_user::get_user($this->userid);
    }

    private function get_watermark_text() {
        global $DB;
        return $DB->get_field('collabora_document', 'watermark_text', ['id' => $this->docrecord->id]);
    }

    private function get_hide_print() {
        return false;
    }

    private function get_hide_save() {
        return false;
    }

    private function get_hide_export() {
        return false;
    }

    private function get_enable_owner_termination() {
        global $DB;
        return $DB->get_field('collabora_document', 'enable_owner_termination', ['id' => $this->docrecord->id]);
    }

    private function get_disable_print() {
        global $DB;
        return $DB->get_field('collabora_document', 'disable_print', ['id' => $this->docrecord->id]);
    }

    private function get_disable_export() {
        global $DB;
        return $DB->get_field('collabora_document', 'disable_export', ['id' => $this->docrecord->id]);
    }

    private function get_disable_copy() {
        global $DB;
        return $DB->get_field('collabora_document', 'disable_copy', ['id' => $this->docrecord->id]);
    }

    private function get_enable_insert_remote_image() {
        global $DB;
        return $DB->get_field('collabora_document', 'enable_insert_remote_image', ['id' => $this->docrecord->id]);
    }

    // If set to 'true', the user-list on the status bar will be hidden.
    // If set to 'mobile' | 'tablet' | 'desktop', it will be hidden on the
    // specified device(s) only (multiples values can be delimited by comma,
    // e.g. 'mobile,tablet').
    private function get_hide_user_list() {
        return 'false';
    }

    private function get_disable_change_tracking_record() {
        global $DB;
        return $DB->get_field('collabora_document', 'disable_change_tracking_record', ['id' => $this->docrecord->id]);
    }

    private function get_disable_change_tracking_show() {
        global $DB;
        return $DB->get_field('collabora_document', 'disable_change_tracking_show', ['id' => $this->docrecord->id]);
    }

    private function get_hide_change_tracking_controls() {
        global $DB;
        return $DB->get_field('collabora_document', 'hide_change_tracking_controls', ['id' => $this->docrecord->id]);
    }

    /**
     * Handle getfile requests.
     */
    private function handle_getfile() {
        $file = $this->get_stored_file();
        send_stored_file($file);
    }

    /**
     * Handle putfile requests.
     */
    private function handle_putfile() {
        if ($this->is_readonly()) {
            throw new \moodle_exception('readonly', 'mod_collabora');
        }
        $fs = get_file_storage();
        $file = $this->get_stored_file();
        $filerecord = (object)[
            'contextid' => $file->get_contextid(),
            'component' => $file->get_component(),
            'filearea' => $file->get_filearea(),
            'itemid' => $file->get_itemid(),
            'filepath' => $file->get_filepath(),
            'filename' => $file->get_filename(),
            'timecreated' => $file->get_timecreated(),
        ];
        $file->delete(); // Remove the old file.
        $fs->create_file_from_string($filerecord, $this->postdata); // Store the new file.
    }

    /**
     * Handle checkfileinfo requests.
     */
    private function handle_checkfileinfo() {
        $file = $this->get_stored_file();
        $ret = (object)[
            'BaseFileName' => clean_filename($file->get_filename()),
            'OwnerId' => $this->get_ownerid(),
            'Size' => $file->get_filesize(),
            'UserId' => $this->get_user_identifier(),
            'UserFriendlyName' => fullname($this->get_user()),
            'UserCanWrite' => !$this->is_readonly(),
            'UserCanNotWriteRelative' => true,
            'LastModifiedTime' => date('c', $file->get_timemodified()),
            'WatermarkText' => $this->get_watermark_text(),
            'HidePrintOption' => $this->get_hide_print(),
            'HideSaveOption' => $this->get_hide_save(),
            'HideExportOption' => $this->get_hide_export(),
            'EnableOwnerTermination' => $this->get_enable_owner_termination(),
            'DisablePrint' => $this->get_disable_print(),
            'DisableExport' => $this->get_disable_export(),
            'DisableCopy' => $this->get_disable_copy(),
            'EnableInsertRemoteImage' => $this->get_enable_insert_remote_image(),
            'HideUserList' => $this->get_hide_user_list(),
            'DisableChangeTrackingRecord' => $this->get_disable_change_tracking_record(),
            'DisableChangeTrackingShow' => $this->get_disable_change_tracking_show(),
            'HideChangeTrackingControls' => $this->get_hide_change_tracking_controls(),
        ];

        die(json_encode($ret));
    }
}
