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

use \mod_collabora\api\collabora_fs;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Create instance form
 *
 * @package   mod_collabora
 * @copyright 2019 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_collabora_mod_form extends moodleform_mod {
    /**
     * Get options for the filemanger
     *
     * @return array
     */
    public static function get_filemanager_opts() {
        return [
            'subdirs' => 0,
            'maxbytes' => 0,
            'maxfiles' => 1,
            'accepted_types' => collabora_fs::get_accepted_types(),
        ];
    }

    /**
     * Get the file link to the document as an html fragment
     *
     * @return string
     */
    private function get_file_link() {
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->context->id, 'mod_collabora', collabora_fs::FILEAREA_INITIAL,
                                     false, '', false, 0, 0, 1);
        $file = reset($files);
        if (!$file) {
            return get_string('missingfile', 'mod_collabora');
        }
        $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(),
                                               $file->get_itemid(), $file->get_filepath(), $file->get_filename(), true);
        return html_writer::link($url, $file->get_filename());
    }

    /**
     * Defines the mform items
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;
        $config = get_config('mod_collabora');

        // General section.
        $mform->addElement('text', 'name', get_string('name', 'mod_collabora'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required');

        $this->standard_intro_elements();

        // Format section.
        $mform->addElement('select', 'format', get_string('format', 'mod_collabora'), collabora_fs::format_menu());
        $mform->setDefault('format', $config->defaultformat);
        if ($this->_instance) {
            $mform->freeze('format');
        }

        if (!$this->_instance) {
            $mform->addElement('filemanager', 'initialfile_filemanager', get_string('initialfile', 'mod_collabora'),
                               null, $this->get_filemanager_opts());
            $mform->hideIf('initialfile_filemanager', 'format', 'neq', collabora_fs::FORMAT_UPLOAD);
        } else if ($this->current->format === collabora_fs::FORMAT_UPLOAD) {
            $mform->addElement('static', 'initialfile', get_string('initialfile', 'mod_collabora'), $this->get_file_link());
        }

        if (!$this->_instance || $this->current->format === collabora_fs::FORMAT_TEXT) {
            $mform->addElement('textarea', 'initialtext', get_string('initialtext', 'mod_collabora'));
            $mform->hideIf('initialtext', 'format', 'neq', collabora_fs::FORMAT_TEXT);
            if ($this->_instance) {
                $mform->freeze('initialtext');
            }
        }

        // Display section.
        $mform->addElement('select', 'display', get_string('display', 'mod_collabora'), collabora_fs::display_menu());
        $mform->setDefault('display', $config->defaultdisplay);
        $mform->addHelpButton('display', 'display', 'mod_collabora');

        // Width.
        $mform->addElement('text', 'width', get_string('width', 'mod_collabora'));
        $mform->setDefault('width', 0);
        $mform->setType('width', PARAM_INT);
        $mform->setAdvanced('width');
        $mform->disabledIf('width', 'display', 'eq', collabora_fs::DISPLAY_NEW);

        // Height.
        $mform->addElement('text', 'height', get_string('height', 'mod_collabora'));
        $mform->setDefault('height', 0);
        $mform->setType('height', PARAM_INT);
        $mform->setAdvanced('height');
        $mform->disabledIf('height', 'display', 'eq', collabora_fs::DISPLAY_NEW);

        // Display activity name.
        $mform->addElement('selectyesno', 'displayname', get_string('displayname', 'mod_collabora'));
        $mform->setDefault('displayname', $config->defaultdisplayname);
        $mform->addHelpButton('displayname', 'displayname', 'mod_collabora');
        $mform->disabledIf('displayname', 'display', 'eq', collabora_fs::DISPLAY_NEW);

        // Display description.
        $mform->addElement('selectyesno', 'displaydescription', get_string('displaydescription', 'mod_collabora'));
        $mform->setDefault('displaydescription', $config->defaultdisplaydescription);
        $mform->disabledIf('displaydescription', 'display', 'eq', collabora_fs::DISPLAY_NEW);

        // Standard sections.
        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    /**
     * Validates the send mform params
     *
     * @param array $data
     * @param array $files
     * @return array The elements with the related error message.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (!$this->_instance) {
            if ($data['format'] === collabora_fs::FORMAT_UPLOAD) {
                if (empty($data['initialfile_filemanager'])) {
                    $errors['initialfile_filemanager'] = get_string('requiredforupload', 'mod_collabora');
                } else {
                    $info = file_get_draft_area_info($data['initialfile_filemanager']);
                    if (!$info['filecount']) {
                        $errors['initialfile_filemanager'] = get_string('requiredforupload', 'mod_collabora');
                    }
                }
            } else if ($data['format'] === collabora_fs::FORMAT_TEXT) {
                if (!isset($data['initialtext']) || !trim($data['initialtext'])) {
                    $errors['initialtext'] = get_string('requiredfortext', 'mod_collabora');
                }
            }
        }
        return $errors;
    }
}
