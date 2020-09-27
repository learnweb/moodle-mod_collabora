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
 * Main support functions
 *
 * @package   mod_collabora
 * @copyright 2019 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_collabora;

use mod_collabora\event\document_locked;
use mod_collabora\event\document_unlocked;

defined('MOODLE_INTERNAL') || die();

class collabora {
    const FORMAT_UPLOAD = 'upload';
    const FORMAT_TEXT = 'text';
    const FORMAT_SPREADSHEET = 'spreadsheet';
    const FORMAT_WORDPROCESSOR = 'wordprocessor';
    const FORMAT_PRESENTATION = 'presentation';

    const DISPLAY_CURRENT = 'current';
    const DISPLAY_NEW = 'new';

    const FILEAREA_INITIAL = 'initial';
    const FILEAREA_GROUP = 'group';

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

    public static function format_menu() {
        return [
            self::FORMAT_UPLOAD => get_string(self::FORMAT_UPLOAD, 'mod_collabora'),
            self::FORMAT_TEXT => get_string(self::FORMAT_TEXT, 'mod_collabora'),
            self::FORMAT_SPREADSHEET => get_string(self::FORMAT_SPREADSHEET, 'mod_collabora'),
            self::FORMAT_WORDPROCESSOR => get_string(self::FORMAT_WORDPROCESSOR, 'mod_collabora'),
            self::FORMAT_PRESENTATION => get_string(self::FORMAT_PRESENTATION, 'mod_collabora'),
        ];
    }

    public static function display_menu() {
        return [
            self::DISPLAY_CURRENT => get_string(self::DISPLAY_CURRENT, 'mod_collabora'),
            self::DISPLAY_NEW => get_string(self::DISPLAY_NEW, 'mod_collabora'),
        ];
    }

    public static function get_accepted_types() {
        return [
            '.txt', '.rtf',
            '.doc', '.docx', '.odt',
            '.xls', '.xlsx', '.ods',
            '.ppt', '.pptx', '.odp',
            '.odg',
        ];
    }

    public function __construct($collaborarec, $context, $groupid, $userid) {
        $this->collaborarec = $collaborarec;
        $this->context = $context;
        $this->groupid = (int)$groupid;
        $this->userid = (int)$userid;

        if ($this->groupid >= 0) {
            $this->create_retrieve_document_record();
            $this->create_retrieve_file();
        }
    }

    public function display_name() {
        return (bool)$this->collaborarec->displayname;
    }

    public function display_description() {
        return (bool)$this->collaborarec->displaydescription;
    }

    public function is_locked() {
        return (bool)$this->document->locked;
    }

    public function can_lock_unlock() {
        if (!$this->document) {
            return false;
        }
        return has_capability('mod/collabora:lock', $this->context);
    }

    public function process_lock_unlock() {
        global $DB, $PAGE;
        if (!$this->can_lock_unlock()) {
            return;
        }
        $lock = optional_param('lock', null, PARAM_INT);
        if ($lock !== $this->groupid) {
            $lock = null;
        }
        $unlock = optional_param('unlock', null, PARAM_INT);
        if ($unlock !== $this->groupid) {
            $unlock = null;
        }
        if ($lock === null && $unlock === null) {
            return;
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
        redirect($PAGE->url);
    }

    /**
     * Retrieve the existing unique user token, or generate a new one.
     * @return string
     */
    private function get_user_token() {
        global $DB;
        if ($token = $DB->get_field('collabora_token', 'token', ['userid' => $this->userid])) {
            return $token;
        }
        while (1) {
            $btyes = random_bytes(60);
            $token = substr(sha1($btyes), 0, 12);
            $ins = (object)[
                'userid' => $this->userid,
                'token' => $token,
            ];
            if (!$DB->record_exists('collabora_token', ['token' => $token])) {
                $DB->insert_record('collabora_token', $ins, false);
                break;
            }
        }
        return $token;
    }

    /**
     * Create the document record, if it doesn't already exist for this collabora + group.
     * Store the document record in $this->document.
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
            ];
            $this->document->id = $DB->insert_record('collabora_document', $this->document);
        }
    }

    /**
     * Retrieve the current file for this collabora instance + group - create a new file, based
     * on the initial settings, if none exists.
     * Store the file in $this->>file.
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
     * @return string
     */
    private function get_file_mimetype() {
        return $this->file->get_mimetype();
    }

    /**
     * Get the discovery XML file from the collabora server.
     * @return string
     */
    private function get_discovery_xml() {
        $baseurl = trim(get_config('mod_collabora', 'url'));
        if (!$baseurl) {
            // Get the product name, if it's already cached.
            $cache = \cache::make('mod_collabora', 'capabilities');
            if (!$productname = $cache->get('productname')) {
                $productname = get_string('default_server_name', 'mod_collabora');
            }
            throw new \moodle_exception('collaboraurlnotset', 'mod_collabora', '', $productname);
        }
        $cache = \cache::make('mod_collabora', 'discovery');
        if (!$xml = $cache->get($baseurl)) {
            $url = rtrim($baseurl, '/').'/hosting/discovery';
            $curl = new \curl();
            $xml = $curl->get($url);
            if ($curl->get_errno()) {
                return '';
            }
            $cache->set($baseurl, $xml);
        }
        return $xml;
    }

    /**
     * Get the URL for editing the given mimetype.
     * @param string $discoveryxml
     * @param string $mimetype
     * @return string
     */
    private function get_url_from_mimetype($discoveryxml, $mimetype) {
        $app = null;
        if ($discoveryxml) {
            $xml = new \SimpleXMLElement($discoveryxml);
            $app = $xml->xpath("//app[@name='{$mimetype}']");
        }
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
     * Get the capabilities JSON file from the collabora server.
     * @return string
     */
    private function get_capabilities_json() {
        $cache = \cache::make('mod_collabora', 'capabilities');
        if (!$json = $cache->get('json')) {
            // Pull it from the discovery.xml.
            $app = null;
            $discoveryxml = $this->get_discovery_xml();
            if ($discoveryxml) {
                $xml = new \SimpleXMLElement($discoveryxml);
                $app = $xml->xpath("//app[@name='Capabilities']");
            }
            if (!$app) {
                throw new \moodle_exception('discovery_error_no_caps_url', 'mod_collabora');
            }
            $action = $app[0]->action;
            $url = isset($action['urlsrc']) ? $action['urlsrc'] : '';
            if (!$url) {
                throw new \moodle_exception('discovery_error_no_caps_url', 'mod_collabora');
            }

            $curl = new \curl();
            $json = $curl->get($url);
            if ($curl->get_errno()) {
                return '';
            }
            $cache->set('json', $json); // Cache the Capabilities JSON file.
        }

        return $json;
    }

    /**
     * Get the product name from the collabora server.
     * @return string
     */
    public function get_product_name() {
        $cache = \cache::make('mod_collabora', 'capabilities');
        if (!$productname = $cache->get('productname')) {
            $caps = $this->get_capabilities_json();
            $json = json_decode($caps);
            if (isset($json->{'productName'})) {
                $productname = $json->{'productName'};
                $cache->set('productname', $productname);
            }
        }

        return $productname;
    }

    /**
     * Resets the caches. Useful when the server is changed.
     * @return string
     */
    public function reset_caches() {
        $cache = \cache::make('mod_collabora', 'capabilities');
        $cache->delete('json');
        $cache->delete('productname');

        // Clear the discovery.xml cache.
        $baseurl = trim(get_config('mod_collabora', 'url'));
        if ($baseurl) {
            $cache = \cache::make('mod_collabora', 'discovery');
            $cache->delete($baseurl);
        }
    }

    /**
     * Get the URL of the handler, base on the mimetype of the existing file.
     * @return string
     */
    private function get_collabora_url() {
        $mimetype = $this->get_file_mimetype();
        $discoveryxml = $this->get_discovery_xml();
        return $this->get_url_from_mimetype($discoveryxml, $mimetype);
    }

    /**
     * Get the fileid that will be returned to retrieve the correct file.
     * @return string
     */
    private function get_file_id() {
        return "{$this->context->id}_{$this->groupid}";
    }

    /**
     * Get the URL of the iframe in which to display the collabora document.
     * @return string
     */
    public function get_view_url() {
        $collaboraurl = $this->get_collabora_url();
        $callbackurl = new \moodle_url('/mod/collabora/callback.php');
        $fileid = $this->get_file_id();
        $token = $this->get_user_token();

        return $collaboraurl.'WOPISrc='.$callbackurl->out().'/wopi/files/'.$fileid.'&access_token='.$token.'&closebutton=1';
    }

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
        }
        return false;
    }
}
