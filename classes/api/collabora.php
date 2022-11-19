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
class collabora implements i_filesystem {
    /** Define the collabora file format for individual files */
    const FORMAT_UPLOAD = 'upload';
    /** Define the collabora file format as simple text */
    const FORMAT_TEXT = 'text';
    /** Define the collabora file format as spreadsheet */
    const FORMAT_SPREADSHEET = 'spreadsheet';
    /** Define the collabora file format as wordprocessor */
    const FORMAT_WORDPROCESSOR = 'wordprocessor';
    /** Define the collabora file format as presentation */
    const FORMAT_PRESENTATION = 'presentation';

    /** Define the display in the current tab/window */
    const DISPLAY_CURRENT = 'current';
    /** Define the display in a new tab/Window */
    const DISPLAY_NEW = 'new';

    /** Define the filearea for initial stored files */
    const FILEAREA_INITIAL = 'initial';
    /** Define the filearea for files a group of users is working at */
    const FILEAREA_GROUP = 'group';

    /** Define accepted languages for WOPI server. This languages come from loolwsd.xml and are the default accepted languages. */
    const ACCEPTED_LANGS = 'de_DE,en_GB,en_US,es_ES,fr_FR,it,nl,pt_BR,pt_PT,ru';
    /** The default language if nothing is defined */
    const FALLBACK_LANG = 'en';

    /** @var object */
    private $collaborarec;
    /** @var \context */
    private $context;
    /** @var int */
    private $groupid;
    /** @var int */
    private $userid;
    /** @var object */
    private $document;
    /** @var \stored_file */
    private $file;
    /** @var \stdClass */
    private $myconfig;
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
        /** @var \moodle_database $DB */
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
     * Get an array for the activity format settings menu
     *
     * @return array
     */
    public static function format_menu() {
        return [
            self::FORMAT_UPLOAD => get_string(self::FORMAT_UPLOAD, 'mod_collabora'),
            self::FORMAT_TEXT => get_string(self::FORMAT_TEXT, 'mod_collabora'),
            self::FORMAT_SPREADSHEET => get_string(self::FORMAT_SPREADSHEET, 'mod_collabora'),
            self::FORMAT_WORDPROCESSOR => get_string(self::FORMAT_WORDPROCESSOR, 'mod_collabora'),
            self::FORMAT_PRESENTATION => get_string(self::FORMAT_PRESENTATION, 'mod_collabora'),
        ];
    }

    /**
     * Get an array for the activity display settings menu
     *
     * @return array
     */
    public static function display_menu() {
        return [
            self::DISPLAY_CURRENT => get_string(self::DISPLAY_CURRENT, 'mod_collabora'),
            self::DISPLAY_NEW => get_string(self::DISPLAY_NEW, 'mod_collabora'),
        ];
    }

    /**
     * Get an array which defines all accepted file types, this activity can handle
     *
     * @return array
     */
    public static function get_accepted_types() {
        return [
            // Plain text.
            '.txt',
            // Textprocesser files.
            '.rtf',
            '.doc',
            '.docx',
            '.odt',
            // Spreadsheet files.
            '.xls',
            '.xlsx',
            '.ods',
            // Presentation files.
            '.ppt',
            '.pptx',
            '.odp',
            '.odg',
        ];
    }

    /**
     * Get a lang string which is supported by collabora.
     * The loleaflet.html accepts a lang parameter but only with hyphen and not the underscore from moodle.
     * This method first check whether the current lang is supported and replaces the underscore (_) by a hyphen (-).
     *
     * @return string The lang accepted parameter
     */
    public static function get_collabora_lang() {
        global $CFG;

        // Prepare the control array.
        $controllist = explode(',', self::ACCEPTED_LANGS);
        array_walk($controllist, function(&$value, $key) {
            $value = substr($value, 0, 2);
        });
        array_unique($controllist);

        // First check whether or not the current lang is accepted.
        // For the check we only need the first two characters. That means e.g. "de_xyzabc" is accepted.
        $currentlang = current_language();
        $controlstring = substr($currentlang, 0, 2);
        if (in_array($controlstring, $controllist)) {
            // Return the full langstring but with hyphen and not with underscore.
            return str_replace('_', '-', $currentlang);
        }

        // If we got here we check the system language as first fallback.
        $systemlang = isset($CFG->lang) ?: self::FALLBACK_LANG;
        $controlstring = substr($systemlang, 0, 2);
        if (in_array($controlstring, $controllist)) {
            // Return the full langstring but with hyphen and not with underscore.
            return str_replace('_', '-', $systemlang);
        }

        // At this point we return the last fallback.
        return self::FALLBACK_LANG;
    }

    /**
     * Get the discovery XML file from the collabora server.
     * @param \stdClass $cfg The collabora configuration.
     * @return string The xml string
     */
    public static function get_discovery_xml($cfg) {
        $baseurl = trim($cfg->url);
        if (!$baseurl) {
            throw new \moodle_exception('collaboraurlnotset', 'mod_collabora');
        }
        $cache = \cache::make('mod_collabora', 'discovery');
        if (!$xml = $cache->get($baseurl)) {
            if (static::is_testing()) {
                $xml = static::get_fixture_discovery_xml();
            } else {
                $url = rtrim($baseurl, '/').'/hosting/discovery';

                // Do we explicitely allow the Collabora host?
                $curlsettings = array();
                if (!empty($cfg->allowcollaboraserverexplicit)) {
                    $curlsettings = array(
                        'securityhelper' => new curl_security_helper($url),
                    );
                }
                $curl = new \curl($curlsettings);
                $xml = $curl->get($url);
            }
            // Check whether or not the xml is valid.
            try {
                new \SimpleXMLElement($xml);
            } catch (\Exception $e) {
                $xmlerror = true;
            }
            if (!empty($xmlerror)) {
                throw new \moodle_exception('XML-Error: '.$xml);
            }
            $cache->set($baseurl, $xml);
        }
        return $xml;
    }

    /**
     * Checks whether or not the current site is running a test (behat or unit test).
     *
     * @return boolean
     */
    public static function is_testing() {
        if (defined('BEHAT_SITE_RUNNING')) {
            return true;
        }
        if ((defined('PHPUNIT_TEST') && PHPUNIT_TEST)) {
            return true;
        }
        return false;
    }

    public static function get_fixture_discovery_xml() {
        global $CFG;

        $search = 'https://example.com/browser/randomid/cool.html';
        $replace = new \moodle_url('/mod/collabora/tests/fixtures/dummyoutput.html');
        $xmlpath = $CFG->dirroot.'/mod/collabora/tests/fixtures/discovery.xml';
        $xml = file_get_contents($xmlpath);
        $xml = str_replace($search, $replace->out(), $xml);
        return $xml;
    }

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
        $this->user = $DB->get_record('user', array('id' => $userid));
        $this->myconfig = get_config('mod_collabora');

        $this->isgroupmember = true;
        if ($this->groupid > 0) {
            $this->isgroupmember = groups_is_member($groupid, $userid);
        }

        if ($this->groupid >= 0) {
            $this->create_retrieve_document_record();
            $this->create_retrieve_file();
        }
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
     * Retrieve the existing unique user token, or generate a new one.
     *
     * @return string
     */
    private function get_user_token() {
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

        // if ($token = $DB->get_field('collabora_token', 'token', ['userid' => $this->userid])) {
        //     return $token;
        // }
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
        $this->file = reset($files);
        if (!$this->file) {
            $this->file = $this->create_file();
        }
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
     * Get the mime type for the current file.
     *
     * @return string
     */
    private function get_file_mimetype() {
        return $this->file->get_mimetype();
    }

    /**
     * Load the discovery XML file from the collabora server into the cache.
     *
     * @return string
     */
    private function load_discovery_xml() {
        return self::get_discovery_xml($this->myconfig);
    }

    /**
     * Get the URL for editing built from the given mimetype.
     *
     * @param string $discoveryxml
     * @param string $mimetype
     * @throws \moodle_exception
     * @return string
     */
    private function get_url_from_mimetype($discoveryxml, $mimetype) {
        $xml = new \SimpleXMLElement($discoveryxml);
        $app = $xml->xpath("//app[@name='{$mimetype}']");
        if (!$app) {
            throw new \moodle_exception('unsupportedtype', 'mod_collabora', '', $mimetype);
        }
        $action = $app[0]->action;
        $url = isset($action['urlsrc']) ? $action['urlsrc'] : '';
        if (!$url) {
            throw new \moodle_exception('unsupportedtype', 'mod_collabora', '', $mimetype);
        }
        return (string)$url;
    }

    /**
     * Get the URL of the handler, based on the mimetype of the existing file.
     *
     * @return \moodle_url
     */
    private function get_collabora_url() {
        $mimetype = $this->get_file_mimetype();
        $discoveryxml = $this->load_discovery_xml();
        return new \moodle_url(
            $this->get_url_from_mimetype(
                $discoveryxml,
                $mimetype
            )
        );
    }

    /**
     * Get the fileid that will be returned to retrieve the correct file.
     *
     * @return string
     */
    private function get_file_id() {
        // By using an additional element "repaircount" we are able to create a new process in collabora for broken documents.
        return "{$this->context->id}_{$this->groupid}_{$this->document->repaircount}";
    }

    /**
     * Get the URL for the iframe in which to display the collabora document.
     *
     * @return \moodle_url
     */
    public function get_view_url() {
        // Preparing the parameters.
        $callbackurl = new \moodle_url('/mod/collabora/callback.php');
        $fileid = $this->get_file_id();
        $wopisrc = $callbackurl->out().'/wopi/files/'.$fileid;
        $token = $this->get_user_token();
        // The loleaflet.html from $collaboraurl accepts a lang parameter but only with hyphen and not the underscore from moodle.
        // This is prepared by get_collabora_lang().
        $lang = self::get_collabora_lang();

        $collaboraurl = $this->get_collabora_url();
        $params = array(
            'WOPISrc' => $wopisrc,
            'access_token' => $token,
            'lang' => $lang,
            'closebutton' => 1,
        );
        $collaboraurl->params($params);
        return $collaboraurl;
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

    /* Methods from interface i_filesystem
     * ##################################### */

     /**
     * Send the file from the moodle file api.
     * This function implicitly calls a "die"!
     *
     * @param bool $forcedownload
     * @return void
     */
    public function send_groupfile($forcedownload = true) {
        send_stored_file($this->file, null, 0, $forcedownload); // Force download.
    }

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
     * Update the stored file
     *
     * @param string $content
     * @return void
     */
    public function update_file($content) {
        $fs = get_file_storage();
        $filerecord = (object)[
            'contextid' => $this->file->get_contextid(),
            'component' => $this->file->get_component(),
            'filearea' => $this->file->get_filearea(),
            'itemid' => $this->file->get_itemid(),
            'filepath' => $this->file->get_filepath(),
            'filename' => $this->file->get_filename(),
            'timecreated' => $this->file->get_timecreated(),
        ];
        $this->file->delete(); // Remove the old file.
        $fs->create_file_from_string($filerecord, $content); // Store the new file.
    }

    /**
     * Get the file from this instance
     *
     * @return \stored_file
     */
    public function get_file() {
        return $this->file;
    }

    /**
     * Get the user name who is working on this document
     *
     * @return string
     */
    public function get_username() {
        return fullname($this->user);
    }

    /**
     * Unique identifier for the owner of the document.
     *
     * @return string
     */
    public function get_ownerid() {
        global $CFG;
        // I think all the files should have the same owner, so just using the Moodle site id?
        return $CFG->siteidentifier;
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

}
