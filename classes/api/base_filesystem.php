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
 * @copyright 2022 Andreas Grabs <moodle@grabs-edv.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base_filesystem {
    /** Define the collabora file format for individual files */
    public const FORMAT_UPLOAD = 'upload';
    /** Define the collabora file format as simple text */
    public const FORMAT_TEXT = 'text';
    /** Define the collabora file format as spreadsheet */
    public const FORMAT_SPREADSHEET = 'spreadsheet';
    /** Define the collabora file format as wordprocessor */
    public const FORMAT_WORDPROCESSOR = 'wordprocessor';
    /** Define the collabora file format as presentation */
    public const FORMAT_PRESENTATION = 'presentation';

    /** Define the display in the current tab/window */
    public const DISPLAY_CURRENT = 'current';
    /** Define the display in a new tab/Window */
    public const DISPLAY_NEW = 'new';

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

    /**
     * Get the URL for editing built from the given mimetype.
     *
     * @param string $discoveryxml
     * @param string $mimetype
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
        $url = isset($action['urlsrc']) ? $action['urlsrc'] : '';
        if (!$url) {
            throw new \moodle_exception('unsupportedtype', 'mod_collabora', '', $mimetype);
        }
        return (string)$url;
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
     * Constructor
     *
     * @param \stdClass $user
     * @param \stored_file $file
     * @param \moodle_url $callbackurl
     * @param int $userpermission
     */
    public function __construct($user, $file, $callbackurl) {
        $this->myconfig = get_config('mod_collabora');
        $this->user = $user;
        $this->file = $file;
        $this->callbackurl = $callbackurl;
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
     * Get the URL of the handler, based on the mimetype of the existing file.
     *
     * @return \moodle_url
     */
    protected function get_collabora_url() {
        $mimetype = $this->get_file_mimetype();
        $discoveryxml = $this->load_discovery_xml();
        return new \moodle_url(
            static::get_url_from_mimetype(
                $discoveryxml,
                $mimetype
            )
        );
    }

    /**
     * Get the URL for the iframe in which to display the collabora document.
     *
     * @return \moodle_url
     */
    public function get_view_url($showclosebutton = false) {
        // Preparing the parameters.

        $fileid = $this->get_file_id();
        $wopisrc = $this->callbackurl->out().'/wopi/files/'.$fileid;
        $token = $this->get_user_token();
        // The loleaflet.html from $collaboraurl accepts a lang parameter but only with hyphen and not the underscore from moodle.
        // This is prepared by get_collabora_lang().
        $lang = static::get_collabora_lang();

        $collaboraurl = $this->get_collabora_url();
        $params = array(
            'WOPISrc' => $wopisrc,
            'access_token' => $token,
            'lang' => $lang,
            'closebutton' => $showclosebutton,
        );
        $collaboraurl->params($params);
        return $collaboraurl;
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
     * @param bool $forcedownload
     * @return void
     */
    public function send_groupfile($forcedownload = true) {
        send_stored_file($this->file, null, 0, $forcedownload); // Force download.
    }

    /**
     * Update the stored file
     *
     * @param string $content
     * @return void
     */
    public function update_file($postdata) {
        if ($this->is_readonly()) {
            throw new \moodle_exception('docreadonly', 'assignsubmission_collabora');
        }

        $fs = get_file_storage();

        // Now we get to save the file - STOLEN CODE.
        $filerecord = (object)[
            'contextid' => $this->file->get_contextid(),
            'component' => $this->file->get_component(),
            'filearea' => $this->file->get_filearea(),
            'itemid' => $this->file->get_itemid(),
            'filepath' => $this->file->get_filepath(),
            'filename' => $this->file->get_filename(),
            'timecreated' => $this->file->get_timecreated(),
            // Time modified will be changed - and so will Version number.
        ];
        $this->file->delete(); // Remove the old file.
        // Store the new file - This will change the ID and automtically unlock it.
        $this->file = $fs->create_file_from_string($filerecord, $postdata);
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

}
