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
use mod_collabora\event\document_repaired;
use mod_collabora\event\document_unlocked;
use mod_collabora\util;

/**
 * Main support functions.
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
     * Get the moodle user id from the collabora_token table.
     *
     * @param  string $token
     * @return int
     */
    public static function get_userid_from_token($token) {
        global $DB;
        $sql = 'SELECT * from {collabora_token} ct
                WHERE ct.token = :token
        ';

        $tokenrec = $DB->get_record_sql($sql, ['token' => $token]);

        if (empty($tokenrec->userid)) {
            return false;
        }

        // Check the user has a valid session in moodle.
        if (!static::session_exists($tokenrec->sid)) {
            return false;
        }

        return $tokenrec->userid;
    }

    /**
     * Remove unused tokens.
     *
     * @return int
     */
    public static function remove_unused_tokens() {
        global $DB;

        $recordset = $DB->get_recordset('collabora_token');

        foreach ($recordset as $tokenrec) {
            // Check the user has a valid session in moodle.
            if (!static::session_exists($tokenrec->sid)) {
                $DB->delete_records('collabora_token', ['id' => $tokenrec->id]);
            }
        }

        return true;
    }

    /**
     * Get an instance of this class by using the fileid and the accesstoken comming from the request (collabora server).
     *
     * @param  string $fileid
     * @param  string $accesstoken
     * @return static
     */
    public static function get_instance_by_fileid($fileid, $accesstoken) {
        global $DB;

        $userid = static::get_userid_from_token($accesstoken);

        $parts = explode('_', $fileid);
        if (count($parts) < 4) {
            throw new \moodle_exception('invalidfileid', 'mod_collabora');
        }
        list($contextid, $groupid, $repaircount, $version) = $parts;

        // Check the context.
        $context = \context::instance_by_id($contextid);
        if ($context->contextlevel !== CONTEXT_MODULE) {
            throw new \moodle_exception('invalidcontextlevel', 'mod_collabora');
        }
        require_capability('mod/collabora:view', $context, $userid);

        // Check the group access.
        $isgroupmember     = true;
        list($course, $cm) = get_course_and_cm_from_cmid($context->instanceid, 'collabora');
        $rec               = $DB->get_record('collabora', ['id' => $cm->instance], '*', MUST_EXIST);

        $groupmode = groups_get_activity_groupmode($cm);
        if ($groupmode == NOGROUPS) {
            if ($groupid != 0) {
                throw new \moodle_exception('invalidgroupid', 'mod_collabora');
            }
        } else {
            if ($groupid == 0) {
                if ($groupmode == SEPARATEGROUPS) {
                    require_capability('moodle/site:accessallgroups', $context, $userid);
                }
            } else {
                $isgroupmember = groups_is_member($groupid, $userid);
                if ($groupmode == SEPARATEGROUPS) {
                    if (!$isgroupmember) {
                        require_capability('moodle/site:accessallgroups', $context, $userid);
                    }
                }

                if (!$DB->record_exists('groups', ['id' => $groupid, 'courseid' => $course->id])) {
                    throw new \moodle_exception('invalidgroupid', 'mod_collabora');
                }
            }
        }

        return new static($rec, $context, $groupid, $userid, $version);
    }

    /**
     * Generates a unique token for a specified table and field.
     *
     * This function repeatedly generates random strings until it finds one
     * that doesn't already exist in the specified table and field.
     *
     * @param string $table The name of the database table to check against.
     * @param string $tokenfield The name of the field in the table to check for uniqueness.
     * @return string A unique random string of 12 characters.
     */
    public static function get_unique_table_token(string $table, string $tokenfield) {
        global $DB;
        while (true) {
            $token = random_string(12);
            if (!$DB->record_exists($table, [$tokenfield => $token])) {
                return $token;
            }
        }
    }

    /**
     * Validates a document token against a user ID.
     *
     * This function checks if a given document token is valid for a specific user
     * by querying the database for matching records in the collabora_document
     * and collabora_token tables.
     *
     * @param string $doctoken The document token to validate.
     * @param int $userid The ID of the user to check against.
     * @return bool Returns true if the document token is valid for the given user, false otherwise.
     */
    public static function validate_doctoken(string $doctoken, int $userid): bool {
        global $DB;

        $sql = 'SELECT cd.* FROM {collabora_document} cd
                INNER JOIN {collabora_token} ct ON ct.documentid = cd.id
                WHERE cd.doctoken = :doctoken AND ct.userid = :userid
        ';
        return $DB->record_exists_sql($sql, ['doctoken' => $doctoken, 'userid' => $userid]);
    }

    /**
     * Constructor.
     *
     * @param \stdClass $collaborarec
     * @param \context  $context
     * @param int       $groupid
     * @param int       $userid
     * @param int       $version
     */
    public function __construct($collaborarec, $context, $groupid, $userid, $version = 0) {
        global $DB;

        $this->collaborarec = $collaborarec;
        $this->context      = $context;
        $this->groupid      = (int) $groupid;

        $this->isgroupmember = true;
        if ($this->groupid > 0) {
            $this->isgroupmember = groups_is_member($groupid, $userid);
        }

        $this->create_retrieve_document_record();
        $file        = $this->create_retrieve_file();
        $user        = $DB->get_record('user', ['id' => $userid]);
        $callbackurl = new \moodle_url('/mod/collabora/callback.php');

        $showversionui = false;
        if (has_capability('mod/collabora:manageversions', $context, $user->id)) {
            $showversionui = true;
        }

        parent::__construct($user, $file, $callbackurl, $version, true, $showversionui);
    }

    /**
     * Get the display name of the current document.
     *
     * @return string
     */
    public function display_name() {
        return (bool) $this->collaborarec->displayname;
    }

    /**
     * Get the display desciption of the current document.
     *
     * @return string
     */
    public function display_description() {
        return (bool) $this->collaborarec->displaydescription;
    }

    /**
     * Info whether or not the current document is locked.
     *
     * @return bool
     */
    public function is_locked() {
        return (bool) $this->document->locked;
    }

    /**
     * Info whether or not the current document can be unlocked.
     *
     * @return bool
     */
    public function can_lock_unlock() {
        if (!$this->document) {
            return false;
        }

        return has_capability('mod/collabora:lock', $this->context);
    }

    /**
     * Lock or unlock the current document if allowed.
     *
     * @return bool
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

        $locked                 = ($lock !== null) ? 1 : 0;
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

        $this->document->repaircount = (int) $this->document->repaircount + 1;

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
            'groupid'     => $this->groupid,
        ]);
        if (!$this->document) {
            $this->document = (object) [
                'collaboraid' => $this->collaborarec->id,
                'groupid'     => $this->groupid,
                'doctoken'    => static::get_unique_table_token('collabora_document', 'doctoken'),
                'locked'      => 0,
                'repaircount' => 0,
            ];
            $this->document->id = $DB->insert_record('collabora_document', $this->document);
        } else {
            if (!$this->document->doctoken) {
                $this->document->doctoken = static::get_unique_table_token('collabora_document', 'doctoken');
                $DB->update_record('collabora_document', $this->document);
            }
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
        $fs    = get_file_storage();
        $files = $fs->get_area_files(
            $this->context->id,   // Param contextid.
            'mod_collabora',      // Param component.
            self::FILEAREA_GROUP, // Param filearea.
            $this->groupid,       // Param itemid.
            // The sorting is important because of the way we store document versions.
            'filepath',           // Param sort.
            false,                // Param includedirs.
            0,                    // Param updatedsince.
            0,                    // Param limitfrom.
            1                     // Param limitnum.
        );
        $file = reset($files);
        if ((!$file) || $file->get_filepath() != '/') {
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
        $fs    = get_file_storage();
        $files = $fs->get_area_files(
            $this->context->id,     // Param contextid.
            'mod_collabora',        // Param component.
            self::FILEAREA_INITIAL, // Param filearea.
            false,                  // Param itemid.
            'filename',             // Param sort.
            false,                  // Param includedirs.
            0,                      // Param updatedsince.
            0,                      // Param limitfrom.
            1                       // Param limitnum.
        );

        $file  = reset($files);
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
        $fs      = get_file_storage();
        $filerec = (object) [
            'contextid' => $this->context->id,
            'component' => 'mod_collabora',
            'filearea'  => self::FILEAREA_GROUP,
            'itemid'    => $this->groupid,
            'filepath'  => '/',
            'filename'  => clean_filename(format_string($this->collaborarec->name)),
        ];

        $initfile = $this->get_initial_file();
        $ext      = pathinfo($initfile->get_filename(), PATHINFO_EXTENSION);
        $filerec->filename .= '.' . $ext;
        $file = $fs->create_file_from_storedfile($filerec, $initfile);

        if (empty($file)) {
            throw new \moodle_exception('Could not create file from initial file');
        }
        return $file;
    }

    /**
     * Retrieves the URL for the user's picture, if sharing is enabled.
     *
     * @return string The URL for the user's picture, or an empty string if sharing is not enabled.
     */
    public function get_userpicture_url(): string {
        if (!empty($this->myconfig->shareuserimages)) {
            $urlparams = ['userid' => $this->user->id, 'doctoken' => $this->document->doctoken];
            $url = new \moodle_url('/mod/collabora/userpic.php', $urlparams);
            return $url->out(false);
        }

        return '';
    }

    /**
     * Get the lock icon depending on the locking state of the current document.
     *
     * @return string the html fragment of the icon presentation
     */
    public function get_lock_icon() {
        global $PAGE, $OUTPUT;
        $canupdate = $this->can_lock_unlock();
        $islocked  = $this->is_locked();
        $url       = null;
        if ($canupdate) {
            $params = ['sesskey' => sesskey()];
            if ($islocked) {
                $params['unlock'] = $this->groupid;
            } else {
                $params['lock'] = $this->groupid;
            }
            $url = new \moodle_url($PAGE->url, $params);
        }
        $data = (object) [
            'canupdate' => $this->can_lock_unlock(),
            'islocked'  => $islocked,
            'url'       => $url,
        ];

        return $OUTPUT->render_from_template('mod_collabora/lockicon', $data);
    }

    /**
     * Choose an appropriate filetype icon based on the mimetype.
     *
     * @return string|false icon URL to be used in `cached_cm_info` or false if there is no appropriate icon
     */
    public function get_module_icon() {
        $mimetype = $this->get_file_mimetype();
        switch ($mimetype) {
            case 'application/vnd.oasis.opendocument.text': // ODT.
            case 'application/msword': // DOC.
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document': // DOCX.
            case 'text/rtf': // RTF.
                return 'odt';
            case 'application/vnd.oasis.opendocument.spreadsheet': // ODS.
            case 'application/vnd.ms-excel': // XLS.
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': // XLSX.
                return 'ods';
            case 'application/vnd.oasis.opendocument.presentation': // ODP.
            case 'application/vnd.ms-powerpoint': // PPT.
            case 'application/vnd.openxmlformats-officedocument.presentationml.presentation': // PPTX.
                return 'odp';
            case 'text/plain': // Text.
                return 'txt';
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
        $elements = [
            $this->context->id,
            $this->groupid,
            $this->document->repaircount,
            $this->version, // On main files the version always is "0".
        ];

        return implode('_', $elements);
    }

    /**
     * Unique identifier for the current user accessing the document.
     *
     * @return string
     */
    public function get_user_identifier() {
        $identifier = $this->get_ownerid() . '_user_' . $this->user->id;

        return sha1($identifier);
    }

    /**
     * Retrieve the existing unique user token, or generate a new one.
     *
     * @return string
     */
    public function get_user_token() {
        global $DB;

        $params = [
            'userid' => $this->user->id,
            'sid'    => session_id(),
            'documentid' => $this->document->id,
        ];
        $sql = 'SELECT * from {collabora_token} ct
                WHERE ct.userid = :userid AND ct.sid = :sid AND ct.documentid = :documentid
        ';

        $tokenrecs = $DB->get_records_sql($sql, $params);

        // Check the user has a valid session in moodle.
        $tokenrec = array_pop($tokenrecs);
        if (!empty($tokenrec->token)) {
            if (static::session_exists($tokenrec->sid)) {
                return $tokenrec->token;
            }
        }
        // Create a new token record.
        $tokenrec             = new \stdClass();
        $tokenrec->userid     = $this->user->id;
        $tokenrec->documentid = $this->document->id;
        $tokenrec->token      = static::get_unique_table_token('collabora_token', 'token');
        $tokenrec->sid        = session_id();

        $DB->insert_record('collabora_token', $tokenrec);

        return $tokenrec->token;
    }
}
