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

use mod_collabora\event\document_locked;
use mod_collabora\event\document_unlocked;
use mod_collabora\event\document_repaired;

/**
 * Main support functions
 *
 * @package   mod_collabora
 * @copyright 2019 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class collabora_fs extends base_filesystem {

    /** @var object */
    private $collaborarec;
    /** @var \context */
    private $context;
    /** @var int */
    private $groupid;
    /** @var object */
    private $document;
    /** @var bool */
    private $isgroupmember;

    /**
     * Get the moodle user id from the collabora_token table
     *
     * @param string $token
     * @return int
     */
    public static function get_userid_from_token($token) {
        global $DB;
        $sql = 'SELECT ct.id, ct.userid from {collabora_token} ct
                JOIN {sessions} s ON s.userid = ct.userid AND s.sid = ct.sid
                WHERE ct.token = :token
        ';

        $tokenrec = $DB->get_record_sql($sql, array('token' => $token));

        if (empty($tokenrec->userid)) {
            return false;
        }
        return $tokenrec->userid;
    }

    /**
     * Remove unused tokens
     *
     * @return int
     */
    public static function remove_unused_tokens() {
        global $DB;

        $recordset = $DB->get_recordset('collabora_token');

        $select = 'sid = :sid AND userid > 0';
        foreach ($recordset as $tokenrec) {
            $params = array('sid' => $tokenrec->sid);
            if (!$DB->record_exists_select('sessions', $select, $params)) {
                $DB->delete_records('collabora_token', array('id' => $tokenrec->id));
            }
        }
        return true;
    }

    /**
     * Get an instance of this class by using the fileid and the accesstoken comming from the request (collabora server).
     *
     * @param string $fileid
     * @param string $accesstoken
     * @return static
     */
    public static function get_instance_by_fileid($fileid, $accesstoken) {
        global $DB;

        $userid = static::get_userid_from_token($accesstoken);

        $parts = explode('_', $fileid);
        if (count($parts) < 3) {
            throw new \moodle_exception('invalidfileid', 'mod_collabora');
        }
        list($contextid, $groupid, $repaircount) = $parts;

        // Check the context.
        $context = \context::instance_by_id($contextid);
        if ($context->contextlevel !== CONTEXT_MODULE) {
            throw new \moodle_exception('invalidcontextlevel', 'mod_collabora');
        }
        require_capability('mod/collabora:view', $context, $userid);

        // Check the group access.
        $isgroupmember = true;
        list($course, $cm) = get_course_and_cm_from_cmid($context->instanceid, 'collabora');
        $rec = $DB->get_record('collabora', ['id' => $cm->instance], '*', MUST_EXIST);

        $groupmode = groups_get_activity_groupmode($cm);
        if ($groupmode == NOGROUPS) {
            if ($groupid != 0) {
                throw new \moodle_exception('invalidgroupid', 'mod_collabora');
            }
        } else {
            if ($groupid == 0) {
                throw new \moodle_exception('invalidgroupid', 'mod_collabora');
            }
            if (!$DB->record_exists('groups', ['id' => $groupid, 'courseid' => $course->id])) {
                throw new \moodle_exception('invalidgroupid', 'mod_collabora');
            }
            $isgroupmember = groups_is_member($groupid, $userid);
            if ($groupmode == SEPARATEGROUPS && !$isgroupmember) {
                require_capability('moodle/site:accessallgroups', $context, $userid);
            }
        }

        return new static($rec, $context, $groupid, $userid);

    }

    /**
     * Constructor
     *
     * @param \stdClass $collaborarec
     * @param \context $context
     * @param int $groupid
     * @param int $userid
     */
    public function __construct($collaborarec, $context, $groupid, $userid) {
        global $DB;

        $this->collaborarec = $collaborarec;
        $this->context = $context;
        $this->groupid = (int)$groupid;

        $this->isgroupmember = true;
        if ($this->groupid > 0) {
            $this->isgroupmember = groups_is_member($groupid, $userid);
        }

        $this->create_retrieve_document_record();
        $file = $this->create_retrieve_file();
        $user = $DB->get_record('user', array('id' => $userid));
        $callbackurl = new \moodle_url('/mod/collabora/callback.php');
        parent::__construct($user, $file, $callbackurl);
    }

    /**
     * Get the display name of the current document.
     *
     * @return string
     */
    public function display_name() {
        return (bool)$this->collaborarec->displayname;
    }

    /**
     * Get the display desciption of the current document.
     *
     * @return string
     */
    public function display_description() {
        return (bool)$this->collaborarec->displaydescription;
    }

    /**
     * Info whether or not the current document is locked
     *
     * @return boolean
     */
    public function is_locked() {
        return (bool)$this->document->locked;
    }

    /**
     * Info whether or not the current document can be unlocked
     *
     * @return boolean
     */
    public function can_lock_unlock() {
        if (!$this->document) {
            return false;
        }
        return has_capability('mod/collabora:lock', $this->context);
    }

    /**
     * Lock or unlock the current document if allowed
     *
     * @return boolean
     */
    public function process_lock_unlock() {
        global $DB, $PAGE;

        if (!$this->can_lock_unlock()) {
            return false; // Nothing done.
        }

        // Check whether or not "lock" or "unlock" is given.
        $lock = optional_param('lock', null, PARAM_INT);
        if ($lock !== $this->groupid) {
            $lock = null;
        }
        $unlock = optional_param('unlock', null, PARAM_INT);
        if ($unlock !== $this->groupid) {
            $unlock = null;
        }
        if ($lock === null && $unlock === null) {
            return false; // Nothing done.
        }
        require_sesskey();

        $locked = ($lock !== null) ? 1 : 0;
        $this->document->locked = $locked;
        $DB->set_field('collabora_document', 'locked', $locked, ['id' => $this->document->id]);
        if ($locked) {
            document_locked::trigger_from_document($this->context->instanceid, $this->document);
        } else {
            document_unlocked::trigger_from_document($this->context->instanceid, $this->document);
        }
        return true; // Locking/unlocking is done.
    }

    /**
     * This method increments the repaircount value of the document record.
     * The new value extends the fileid sent to the collabora server.
     * By incrementing the value "repaircount" we are able to create a new process in collabora for broken documents.
     *
     * @return bool
     */
    public function process_repair() {
        global $DB;

        $this->document->repaircount = intval($this->document->repaircount) + 1;

        $return = $DB->set_field('collabora_document', 'repaircount', $this->document->repaircount, ['id' => $this->document->id]);
        document_repaired::trigger_from_document($this->context->instanceid, $this->document);
        return $return;
    }

    /**
     * Create the document record, if it doesn't already exist for this collabora + group.
     * Store the document record in $this->document.
     *
     * @return void
     */
    private function create_retrieve_document_record() {
        global $DB;
        $this->document = $DB->get_record('collabora_document', [
            'collaboraid' => $this->collaborarec->id,
            'groupid' => $this->groupid
        ]);
        if (!$this->document) {
            $this->document = (object)[
                'collaboraid' => $this->collaborarec->id,
                'groupid' => $this->groupid,
                'locked' => 0,
                'repaircount' => 0,
            ];
            $this->document->id = $DB->insert_record('collabora_document', $this->document);
        }
    }

    /**
     * Retrieve the current file for this collabora instance + group - create a new file, based
     * on the initial settings, if none exists.
     * Store the file in $this->>file.
     *
     * @return void
     */
    private function create_retrieve_file() {
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->context->id, 'mod_collabora', self::FILEAREA_GROUP, $this->groupid,
                                     '', false, 0, 0, 1);
        $file = reset($files);
        if (!$file) {
            $file = $this->create_file();
        }
        return $file;
    }

    /**
     * For activities with format 'upload', retrieve the file that was uploaded.
     *
     * @throws \moodle_exception
     * @return \stored_file
     */
    private function get_initial_file() {
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->context->id, 'mod_collabora', self::FILEAREA_INITIAL, false, '', false, 0, 0, 1);
        $file = reset($files);
        if (!$file) {
            throw new \moodle_exception('initialfilemissing', 'mod_collabora');
        }
        return $file;
    }

    /**
     * Create a new file for this group, based on the 'format' setting for the activity.
     *
     * @throws \coding_exception
     * @return \stored_file
     */
    private function create_file() {
        global $CFG;
        $fs = get_file_storage();
        $filerec = (object)[
            'contextid' => $this->context->id,
            'component' => 'mod_collabora',
            'filearea' => self::FILEAREA_GROUP,
            'itemid' => $this->groupid,
            'filepath' => '/',
            'filename' => clean_filename(format_string($this->collaborarec->name)),
        ];
        switch ($this->collaborarec->format) {
            case self::FORMAT_UPLOAD:
                $initfile = $this->get_initial_file();
                $ext = pathinfo($initfile->get_filename(), PATHINFO_EXTENSION);
                $filerec->filename .= '.'.$ext;
                $file = $fs->create_file_from_storedfile($filerec, $initfile);
                break;
            case self::FORMAT_TEXT:
                $inittext = $this->collaborarec->initialtext;
                $ext = 'txt';
                $filerec->filename .= '.'.$ext;
                $file = $fs->create_file_from_string($filerec, $inittext);
                break;
            case self::FORMAT_WORDPROCESSOR:
                $ext = 'docx';
                $filerec->filename .= '.'.$ext;
                $filepath = $CFG->dirroot.'/mod/collabora/blankfiles/blankdocument.docx';
                $file = $fs->create_file_from_pathname($filerec, $filepath);
                break;
            case self::FORMAT_SPREADSHEET:
                $ext = 'xlsx';
                $filerec->filename .= '.'.$ext;
                $filepath = $CFG->dirroot.'/mod/collabora/blankfiles/blankspreadsheet.xlsx';
                $file = $fs->create_file_from_pathname($filerec, $filepath);
                break;
            case self::FORMAT_PRESENTATION:
                $ext = 'pptx';
                $filerec->filename .= '.'.$ext;
                $filepath = $CFG->dirroot.'/mod/collabora/blankfiles/blankpresentation.pptx';
                $file = $fs->create_file_from_pathname($filerec, $filepath);
                break;
            default:
                throw new \coding_exception("Unknown format: {$this->collaborarec->format}");
        }
        return $file;
    }

    /**
     * Get the lock icon depending on the locking state of the current document
     *
     * @return string The html fragment of the icon presentation.
     */
    public function get_lock_icon() {
        global $PAGE, $OUTPUT;
        $canupdate = $this->can_lock_unlock();
        $islocked = $this->is_locked();
        $url = null;
        if ($canupdate) {
            $params = ['sesskey' => sesskey()];
            if ($islocked) {
                $params['unlock'] = $this->groupid;
            } else {
                $params['lock'] = $this->groupid;
            }
            $url = new \moodle_url($PAGE->url, $params);
        }
        $data = (object)[
            'canupdate' => $this->can_lock_unlock(),
            'islocked' => $islocked,
            'url' => $url,
        ];
        return $OUTPUT->render_from_template('mod_collabora/lockicon', $data);
    }

    /**
     * Choose an appropriate filetype icon based on the mimetype.
     *
     * @return string|false Icon URL to be used in `cached_cm_info` or false if there is no appropriate icon.
     */
    public function get_module_icon() {
        $mimetype = $this->get_file_mimetype();
        switch ($mimetype) {
            case 'application/vnd.oasis.opendocument.text': // ODT.
            case 'application/msword': // DOC.
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document': // DOCX.
            case 'text/rtf': // RTF.
                return 'mod/collabora/odt';
            case 'application/vnd.oasis.opendocument.spreadsheet': // ODS.
            case 'application/vnd.ms-excel': // XLS.
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': // XLSX.
                return 'mod/collabora/ods';
            case 'application/vnd.oasis.opendocument.presentation': // ODP.
            case 'application/vnd.ms-powerpoint': // PPT.
            case 'application/vnd.openxmlformats-officedocument.presentationml.presentation': // PPTX.
                return 'mod/collabora/odp';
            case 'text/plain': // Text.
                return 'mod/collabora/txt';
        }
        return false;
    }

    /* Methods from base_filesystem
     * ##################################### */

    /**
     * Is the file read-only?
     *
     * @return bool
     */
    public function is_readonly() {
        if (!$this->isgroupmember && !has_capability('moodle/site:accessallgroups', $this->context, $this->user->id)) {
            return true; // Not a member of the relevant group => definitely has no access.
        }
        if ($this->document->locked) {
            // Locked - only users with the ability to edit locked documents can edit.
            return !has_capability('mod/collabora:editlocked', $this->context, $this->user->id);
        }
        return false;
    }

    /**
     * Get the fileid that will be returned to retrieve the correct file.
     *
     * @return string
     */
    public function get_file_id() {
        // By using an additional element "repaircount" we are able to create a new process in collabora for broken documents.
        return "{$this->context->id}_{$this->groupid}_{$this->document->repaircount}";
    }

    /**
     * Unique identifier for the current user accessing the document.
     *
     * @return string
     */
    public function get_user_identifier() {
        $identifier = $this->get_ownerid().'_user_'.$this->user->id;
        return sha1($identifier);
    }

    /**
     * Retrieve the existing unique user token, or generate a new one.
     *
     * @return string
     */
    public function get_user_token() {
        global $DB;

        $params = array(
            'userid' => $this->user->id,
            'sid' => session_id(),
        );
        $sql = 'SELECT ct.id, ct.token from {collabora_token} ct
                JOIN {sessions} s ON s.userid = ct.userid AND s.sid = ct.sid
                WHERE ct.userid = :userid AND ct.sid = :sid
        ';

        $tokenrec = $DB->get_record_sql($sql, $params);

        if (!empty($tokenrec->token)) {
            return $tokenrec->token;
        }
        // Create a new token record.
        $tokenrec = new \stdClass();
        $tokenrec->userid = $this->user->id;
        $tokenrec->token = random_string(12);
        $tokenrec->sid = session_id();

        $params = array('token' => $tokenrec->token);
        while (true) {
            if (!$DB->record_exists('collabora_token', $params)) {
                $DB->insert_record('collabora_token', $tokenrec, false);
                break;
            }
        }

        return $tokenrec->token;
    }

}
