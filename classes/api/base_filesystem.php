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
 * @copyright 2022 Andreas Grabs <moodle@grabs-edv.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base_filesystem {
    /** The filearea for templates */
    public const FILEAREA_TEMPLATE = 'template';
    /** Define the filearea for initial stored files */
    public const FILEAREA_INITIAL = 'initial';
    /** Define the filearea for files a group of users is working at */
    public const FILEAREA_GROUP = 'group';

    /** Define accepted languages for WOPI server. This languages come from loolwsd.xml and are the default accepted languages. */
    public const ACCEPTED_LANGS = 'de_DE,en_GB,en_US,es_ES,fr_FR,it,nl,pt_BR,pt_PT,ru';
    /** The default language if nothing is defined */
    public const FALLBACK_LANG = 'en';

    /** @var \stdClass */
    protected $myconfig;
    /** @var \stdClass */
    protected $user;
    /** @var \stored_file */
    protected $file;
    /** @var \moodle_url */
    protected $callbackurl;
    /** @var bool */
    protected $useversions;
    /** @var bool */
    protected $showversionui;
    /** @var int */
    protected $version;

    /**
     * Get the URL for editing built from the given mimetype.
     *
     * @param  string            $discoveryxml
     * @param  string            $mimetype
     * @throws \moodle_exception
     * @return string
     */
    public static function get_url_from_mimetype($discoveryxml, $mimetype) {
        $xml = new \SimpleXMLElement($discoveryxml);
        $app = $xml->xpath("//app[@name='{$mimetype}']");
        if (!$app) {
            throw new \moodle_exception('unsupportedtype', 'mod_collabora', '', $mimetype);
        }
        $action = $app[0]->action;
        $url    = isset($action['urlsrc']) ? $action['urlsrc'] : '';
        if (!$url) {
            throw new \moodle_exception('unsupportedtype', 'mod_collabora', '', $mimetype);
        }

        return (string) $url;
    }

    /**
     * Get the discovery XML file from the collabora server.
     * @param  \stdClass $cfg the collabora configuration
     * @return string    The xml string
     */
    public static function get_discovery_xml($cfg) {
        $baseurl = trim($cfg->url);

        $cache = \cache::make('mod_collabora', 'discovery');
        if (!$xml = $cache->get($baseurl)) {
            if (static::is_testing()) {
                $xml = static::get_fixture_discovery_xml();
            } else {
                $url = rtrim($baseurl, '/') . '/hosting/discovery';

                // Do we explicitely allow the Collabora host?
                $curlsettings = [];
                if (!empty($cfg->allowcollaboraserverexplicit)) {
                    $curlsettings = [
                        'securityhelper' => new curl_security_helper($url),
                    ];
                }
                $curl = new \curl($curlsettings);
                $xml  = $curl->get($url);
            }
            // Check whether or not the xml is valid.
            try {
                new \SimpleXMLElement($xml);
            } catch (\Exception $e) {
                $xmlerror = true;
            }
            if (!empty($xmlerror)) {
                throw new \moodle_exception('XML-Error: ' . $xml);
            }
            $cache->set($baseurl, $xml);
        }

        return $xml;
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
        array_walk($controllist, function (&$value, $key) {
            $value = substr($value, 0, 2);
        });
        array_unique($controllist);

        // First check whether or not the current lang is accepted.
        // For the check we only need the first two characters. That means e.g. "de_xyzabc" is accepted.
        $currentlang   = current_language();
        $controlstring = substr($currentlang, 0, 2);
        if (in_array($controlstring, $controllist)) {
            // Return the full langstring but with hyphen and not with underscore.
            return str_replace('_', '-', $currentlang);
        }

        // If we got here we check the system language as first fallback.
        $systemlang    = isset($CFG->lang) ?: self::FALLBACK_LANG;
        $controlstring = substr($systemlang, 0, 2);
        if (in_array($controlstring, $controllist)) {
            // Return the full langstring but with hyphen and not with underscore.
            return str_replace('_', '-', $systemlang);
        }

        // At this point we return the last fallback.
        return self::FALLBACK_LANG;
    }

    /**
     * Checks whether or not the current site is running a test (behat or unit test).
     *
     * @return bool
     */
    public static function is_testing() {
        $mycfg = get_config('mod_collabora');
        // If no url to a collabora server is defined we can behave like we are testing.
        if (empty($mycfg->url)) {
            return true;
        }
        // Check for test environment.
        if (defined('BEHAT_SITE_RUNNING')) {
            return true;
        }
        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            return true;
        }

        return false;
    }

    /**
     * Get a dummy discovery xml from the fixtures folder. This is used if the Moodle instance is in testing mode
     * or the "url" setting is empty. {@see static::is_testing}.
     *
     * @return void
     */
    public static function get_fixture_discovery_xml() {
        global $CFG;

        $search  = 'https://example.com/browser/randomid/cool.html';
        $replace = new \moodle_url('/mod/collabora/tests/fixtures/dummyoutput.html');
        $xmlpath = $CFG->dirroot . '/mod/collabora/tests/fixtures/discovery.xml';
        $xml     = file_get_contents($xmlpath);
        $xml     = str_replace($search, $replace->out(), $xml);

        return $xml;
    }

    /**
     * Get an array which defines all accepted file types, this activity can handle.
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
     * Does the PHP session with given id exist?
     *
     * The session must exist in actual session backend and the session must not be timed out.
     * With this check, we ensure that the callback calls belong to a user who is actually logged in.
     *
     * @param string $sid
     * @return bool
     */
    public static function session_exists($sid) {
        return \mod_collabora\session::session_exists($sid);
    }

    /**
     * Constructor.
     *
     * @param \stdClass    $user
     * @param \stored_file $file
     * @param \moodle_url  $callbackurl
     * @param int          $version
     * @param bool         $useversions
     * @param bool         $showversionui
     */
    public function __construct($user, $file, $callbackurl, $version = 0, $useversions = true, $showversionui = false) {
        $this->myconfig    = get_config('mod_collabora');
        $this->user        = $user;
        $this->file        = $file;
        $this->callbackurl = $callbackurl;
        $this->version     = $version;

        $this->useversions   = $this->myconfig->enableversions ?? false;
        $this->useversions   = $this->useversions && $useversions; // Versions can be disabled through the constructor param.
        $this->showversionui = $showversionui;
    }

    /**
     * Is the file read-only?
     *
     * @return bool
     */
    abstract public function is_readonly();

    /**
     * Get the fileid that will be returned to retrieve the correct file.
     *
     * @return string
     */
    abstract public function get_file_id();

    /**
     * Unique identifier for the current user accessing the document.
     *
     * @return string
     */
    abstract public function get_user_identifier();

    /**
     * Retrieve the existing unique user token, or generate a new one.
     *
     * @return string
     */
    abstract public function get_user_token();

    /**
     * Get the mime type for the current file.
     *
     * @return string
     */
    protected function get_file_mimetype() {
        return $this->file->get_mimetype();
    }

    /**
     * What ui mode do we use?
     *
     * @return string
     */
    public function get_ui_mode() {
        return $this->myconfig->uimode ?? \mod_collabora\util::UI_TABBED;
    }

    /**
     * Get the URL of the handler, based on the mimetype of the existing file.
     *
     * @return \moodle_url
     */
    public function get_collabora_url() {
        $mimetype     = $this->get_file_mimetype();
        $discoveryxml = $this->load_discovery_xml();

        return new \moodle_url(
            static::get_url_from_mimetype(
                $discoveryxml,
                $mimetype
            )
        );
    }

    /**
     * Determines whether the server audit should be displayed.
     *
     * The server audit is displayed only if the 'showserveraudit' configuration option is enabled
     * and the user accessing the document is a site administrator.
     *
     * @return bool True if the server audit should be displayed, false otherwise.
     */
    public function show_server_audit(): bool {
        if (!empty($this->myconfig->showserveraudit)) {
            return is_siteadmin($this->user);
        }
        return false;
    }

    /**
     * Get the origin of the collabora-server, based on the collabora url.
     *
     * @return string
     */
    public function get_collabora_origin() {
        $url    = $this->get_collabora_url();
        $scheme = $url->get_scheme();
        $host   = $url->get_host();

        return $scheme . '://' . $host;
    }

    /**
     * Get the origin of the moodle server, based on wwwroot.
     *
     * @return string
     */
    public function get_moodle_origin() {
        $url    = new \moodle_url('/');
        $scheme = $url->get_scheme();
        $host   = $url->get_host();

        return $scheme . '://' . $host;
    }

    /**
     * Get the URL for the iframe in which to display the collabora document.
     *
     * @return \moodle_url
     */
    public function get_view_url() {
        // Preparing the parameters.
        $fileid  = $this->get_file_id();
        $wopisrc = $this->callbackurl->out() . '/wopi/files/' . $fileid;
        $token   = $this->get_user_token();
        // The loleaflet.html from $collaboraurl accepts a lang parameter but only with hyphen and not the underscore from moodle.
        // This is prepared by get_collabora_lang().
        $lang = static::get_collabora_lang();

        $collaboraurl = $this->get_collabora_url();
        $params       = $this->get_view_params();
        $collaboraurl->params($params);

        return $collaboraurl;
    }

    /**
     * Get the url of the userpicture to be shown inside the collabora editor.
     * This method should be overriden by the implementing class.
     *
     * @return string
     */
    public function get_userpicture_url() {
        return '';
    }

    /**
     * Get the URL for the iframe in which to display the collabora document.
     *
     * @param bool $showclosebutton
     * @return []
     */
    public function get_view_params(bool $showclosebutton = true) {
        // Preparing the parameters.
        $fileid  = $this->get_file_id();
        $wopisrc = $this->callbackurl->out() . '/wopi/files/' . $fileid;
        $token   = $this->get_user_token();
        // The loleaflet.html from $collaboraurl accepts a lang parameter but only with hyphen and not the underscore from moodle.
        // This is prepared by get_collabora_lang().
        $lang = static::get_collabora_lang();

        $params = [
            'WOPISrc'      => $wopisrc,
            'access_token' => $token,
            'lang'         => $lang,
        ];
        if ($showclosebutton) {
            $params['closebutton'] = 1;
        }
        if ($this->use_versions()) {
            if (empty($this->version)) {
                if ($this->showversionui) { // Show the version ui only if activated.
                    $params['revisionhistory'] = 1;
                }
            } else {
                $params['permission'] = 'readonly';
            }
        }

        return $params;
    }

    /**
     * Load the discovery XML file from the collabora server into the cache.
     *
     * @return string
     */
    private function load_discovery_xml() {
        return static::get_discovery_xml($this->myconfig);
    }

    /**
     * Send the file from the moodle file api.
     * This function implicitly calls a "die"!
     *
     * @param  bool $forcedownload
     * @return void
     */
    public function send_groupfile($forcedownload = true) {
        if (!empty($this->version)) {
            $this->send_version_file($this->version);
        }
        send_stored_file($this->file, null, 0, $forcedownload); // Force download.
    }

    /**
     * Send a version of our file from the moodle file api.
     * This function implicitly calls a "die"!
     *
     * @param  int  $version
     * @param  bool $forcedownload
     * @return void
     */
    public function send_version_file(int $version, bool $forcedownload = true) {
        if ($file = $this->get_version_file($version)) {
            send_stored_file($file, null, 0, $forcedownload); // Force download.
        }
        throw new \moodle_exception('missing_file');
    }

    /**
     * Update the stored file and create a new version if activated.
     *
     * @param  string $content
     * @return void
     */
    public function update_file($content) {
        if ($this->is_readonly()) {
            throw new \moodle_exception('docreadonly', 'assignsubmission_collabora');
        }

        // If the new content is the same as the old one, we don't update the file.
        if ($this->file->compare_to_string($content)) {
            return;
        }

        $fs = get_file_storage();

        if ($this->use_versions()) {
            // Create a new version from last current version.
            // Don't delete the old file but move it into a subdirectory.
            $versionrecord = (object) [
                'contextid'   => $this->file->get_contextid(),
                'component'   => $this->file->get_component(),
                'filearea'    => $this->file->get_filearea(),
                'itemid'      => $this->file->get_itemid(),
                'filepath'    => '/' . $this->file->get_timemodified() . '/',
                'filename'    => $this->file->get_filename(),
                'timecreated' => $this->file->get_timecreated(),
            ];
            try {
                $fs->create_file_from_storedfile($versionrecord, $this->file);
            } catch (\moodle_exception $e) {
                // There was an error creating the version file.
                $versionrecord = null; // We want no empty catch statement.
            }
        }

        // Now we get to save the file.
        $filerecord = (object) [
            'contextid'   => $this->file->get_contextid(),
            'component'   => $this->file->get_component(),
            'filearea'    => $this->file->get_filearea(),
            'itemid'      => $this->file->get_itemid(),
            'filepath'    => '/',
            'filename'    => $this->file->get_filename(),
            'timecreated' => $this->file->get_timecreated(),
            // Time modified will be changed - and so will Version number.
        ];
        $this->file->delete(); // Remove the old file.
        // Store the new file - This will change the ID and automtically unlock it.
        $this->file = $fs->create_file_from_string($filerecord, $content);
    }

    /**
     * Get the file from this instance.
     *
     * @return \stored_file
     */
    public function get_file() {
        if (!empty($this->version)) {
            return $this->get_version_file($this->version);
        }

        return $this->file;
    }

    /**
     * Get the file versions from this instance.
     *
     * @return \stored_file[]
     */
    public function get_version_files() {
        if (!$this->use_versions()) {
            throw new \moodle_exception('versions_are_deactivated');
        }

        $fs    = get_file_storage();
        $files = $fs->get_area_files(
            $this->file->get_contextid(), // Param contextid.
            $this->file->get_component(), // Param component.
            $this->file->get_filearea(),  // Param filearea.
            $this->file->get_itemid(),    // Param itemid.
            // The sorting is important because of the way we store document versions.
            'filepath',                   // Param sort.
            false                         // Param includedirs.
        );
        $result = [];
        foreach ($files as $file) {
            if ($file->get_filepath() == '/') {
                continue;
            }
            $result[] = $file;
        }

        return $result;
    }

    /**
     * Get a version of our file from this instance.
     *
     * @param int $version
     * @return \stored_file
     */
    public function get_version_file(int $version) {
        if (!$this->use_versions()) {
            throw new \moodle_exception('versions_are_deactivated');
        }

        $files = $this->get_version_files();

        foreach ($files as $file) {
            $fileversion = $file->get_filepath();
            $fileversion = trim($fileversion, '/');
            if ($fileversion == $version) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Get a version of our file from this instance.
     *
     * @param int $version
     * @return \stored_file
     */
    public function delete_version(int $version) {
        $versionfile = $this->get_version_file($version);
        // Get the directory of the version file.
        $fs         = get_file_storage();
        $versiondir = $fs->get_file(
            $versionfile->get_contextid(),
            $versionfile->get_component(),
            $versionfile->get_filearea(),
            $versionfile->get_itemid(),
            $versionfile->get_filepath(),
            '.' // The filename of a directory always is a dot ".".
        );
        $versionfile->delete();

        return $versiondir->delete();
    }

    /**
     * Reset the current document by the given version.
     * This creates a new version of the old current document and the version to be restored is removed.
     *
     * @param  int  $version the version to be restored
     * @return bool
     */
    public function restore_version(int $version) {
        global $DB;

        $fs = get_file_storage();

        if (!$this->use_versions()) {
            throw new \moodle_exception('versions_are_deactivated');
        }

        $transaction = $DB->start_delegated_transaction();
        try {
            // First create a new version from the current file.
            $versionrecord = (object) [
                'contextid'    => $this->file->get_contextid(),
                'component'    => $this->file->get_component(),
                'filearea'     => $this->file->get_filearea(),
                'itemid'       => $this->file->get_itemid(),
                'filepath'     => '/' . $this->file->get_timemodified() . '/',
                'filename'     => $this->file->get_filename(),
                'timecreated'  => $this->file->get_timecreated(),
                'timemodified' => $this->file->get_timemodified(),
            ];
            $fileexists = $fs->file_exists(
                $versionrecord->contextid,
                $versionrecord->component,
                $versionrecord->filearea,
                $versionrecord->itemid,
                $versionrecord->filepath,
                $versionrecord->filename
            );
            if (!$fileexists) {
                $fs->create_file_from_storedfile($versionrecord, $this->file);
            }

            if ($version > 0) {
                // Now copy the version file.
                $versionfile = $this->get_version_file($version);
                $filerecord  = (object) [
                    'contextid'    => $versionfile->get_contextid(),
                    'component'    => $versionfile->get_component(),
                    'filearea'     => $versionfile->get_filearea(),
                    'itemid'       => $versionfile->get_itemid(),
                    'filepath'     => '/',
                    'filename'     => $versionfile->get_filename(),
                    'timecreated'  => $versionfile->get_timecreated(),
                    'timemodified' => $versionfile->get_timemodified(),
                ];
                $this->file->delete(); // Remove the old file.
                $this->file = $fs->create_file_from_storedfile($filerecord, $versionfile); // Create the new one.

                // Remove the old version file.
                $this->delete_version($version);
            }
            if ($version == -1) {
                $this->file->delete(); // Remove the current file, so a new one is created by the initial file.
            }

            $transaction->allow_commit();
        } catch (\moodle_exception $e) {
            $transaction->rollback($e);

            return false;
        }

        return true;
    }

    /**
     * Get the user name who is working on this document.
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
     * Are we using versions?
     *
     * @return bool
     */
    public function use_versions() {
        return $this->useversions;
    }
}
