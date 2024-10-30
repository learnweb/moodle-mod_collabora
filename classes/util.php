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

namespace mod_collabora;

use mod_collabora\api\collabora_fs;

/**
 * Util class for mod_collabora.
 *
 * @package    mod_collabora
 *
 * @author     Andreas Grabs <moodle@grabs-edv.de>
 * @copyright  2019 Humboldt-Universität zu Berlin <moodle-support@cms.hu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class util {
    /** The classic view with menues. */
    public const UI_COMPACT = 'classic';
    /** The modern view with tabs. */
    public const UI_TABBED = 'notebookbar';
    /** Use the server defined ui. */
    public const UI_SERVER = 0;

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

    /**
     * Get the current groupid from $cm
     *
     * @param  \stdClass|\cm_info       $cm
     * @param  \stdClass|null $user
     * @return int
     */
    public static function get_current_groupid_from_cm($cm, ?\stdClass $user = null) {
        global $USER;

        if (empty($user)) {
            $user = $USER;
        }

        $context = \context_module::instance($cm->id);

        // Handle groups selection.
        $groupid = groups_get_activity_group($cm, true);
        if ($groupid === false) {
            $groupid = 0; // No groups, so use id 0 for everyone.
        } else if ($groupid === 0) {
            if (groups_get_activity_groupmode($cm) === SEPARATEGROUPS) {
                if (!has_capability('moodle/site:accessallgroups', $context)) {
                    return -1;
                }
            }
        }

        return $groupid;
    }

    /**
     * Get an array of options for the format menu in the Collabora module.
     *
     * The format menu allows users to select a template or file format for the Collabora document.
     * This method returns an array of options, organized into groups, that can be used to populate the format menu.
     *
     * @param bool $chooseoption Whether to include a "Choose" option in the format menu.
     * @return array An array of format menu options, organized into groups.
     */
    public static function grouped_format_menu($chooseoption = true) {
        $mycfg = get_config('mod_collabora');

        $options = [];
        if (!empty($chooseoption)) {
            $options[''] = ['' => get_string('choosedots')];
        }

        if ($templates = static::get_templates()) {
            $templateoptions = [];
            foreach ($templates as $pathnamehash => $filename) {
                $templateoptions[$pathnamehash] = $filename;
            }
            $options[get_string('templates', 'mod_collabora')] = $templateoptions;
        }

        $fixedtemplates = static::get_format_menu_dynamic_items();
        $fixedoptions = [];
        foreach ($fixedtemplates as $template) {
            $fixedoptions[$template] = get_string($template, 'mod_collabora');
        }
        $options[get_string('templates_dynamic', 'mod_collabora')] = $fixedoptions;

        if (!empty($mycfg->showlegacytemplates)) {
            $legacyitems = static::get_legacy_templates();
            $legacyoptions = [];
            foreach ($legacyitems as $item => $unused) {
                $legacyoptions[$item] = get_string($item, 'mod_collabora');
            }
            $options[get_string('templates_legacy', 'mod_collabora')] = $legacyoptions;
        }

        return $options;
    }

    /**
     * The legace format menu to choose one of the legacy formats.
     *
     * @return array
     */
    public static function format_menu() {
        return [
            static::FORMAT_UPLOAD        => get_string(static::FORMAT_UPLOAD, 'mod_collabora'),
            static::FORMAT_TEXT          => get_string(static::FORMAT_TEXT, 'mod_collabora'),
            static::FORMAT_SPREADSHEET   => get_string(static::FORMAT_SPREADSHEET, 'mod_collabora'),
            static::FORMAT_WORDPROCESSOR => get_string(static::FORMAT_WORDPROCESSOR, 'mod_collabora'),
            static::FORMAT_PRESENTATION  => get_string(static::FORMAT_PRESENTATION, 'mod_collabora'),
        ];
    }

    /**
     * Get an array for the activity display settings menu.
     *
     * @return array
     */
    public static function display_menu() {
        return [
            static::DISPLAY_CURRENT => get_string(static::DISPLAY_CURRENT, 'mod_collabora'),
            static::DISPLAY_NEW     => get_string(static::DISPLAY_NEW, 'mod_collabora'),
        ];
    }

    /**
     * Get options for the filemanger.
     *
     * @return array
     */
    public static function get_filemanager_opts() {
        return [
            'subdirs'        => 0,
            'maxbytes'       => 0,
            'maxfiles'       => 1,
            'accepted_types' => collabora_fs::get_accepted_types(),
        ];
    }

    /**
     * Stores the initial file for a Collabora Online activity.
     *
     * This method is responsible for handling the storage of the initial file for a Collabora Online activity,
     * based on the activity's format setting. It supports four different formats:
     * - upload,
     * - text,
     * - legacy templates and
     * - system templates defined by the administrator.
     *
     * @param \stdClass $collabora The Collabora Online activity object.
     * @param int $moduleid The ID of the module instance.
     * @return bool True if the initial file was successfully stored, false otherwise.
     */
    public static function store_initial_file($collabora, $moduleid): bool {
        $context = \context_module::instance($moduleid);
        $fs      = get_file_storage();

        // Store the initial file from the uploaded file.
        if ($collabora->format == static::FORMAT_UPLOAD) {
            try {
                file_postupdate_standard_filemanager(
                    $collabora,
                    'initialfile',
                    static::get_filemanager_opts(),
                    $context,
                    'mod_collabora',
                    collabora_fs::FILEAREA_INITIAL,
                    0
                );
                return true;
            } catch (\moodle_exception $e) {
                return false;
            }
        }

        // The common filerecord for the other initial files.
        $filerec = (object) [
            'contextid' => $context->id,
            'component' => 'mod_collabora',
            'filearea'  => collabora_fs::FILEAREA_INITIAL,
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => clean_filename(format_string($collabora->name)),
        ];

        // Store a text file as initial file if the format is text.
        // The initial content comes from the initialtext field.
        if ($collabora->format == static::FORMAT_TEXT) {
            $inittext = $collabora->initialtext;
            $ext      = 'txt';
            $filerec->filename .= '.' . $ext;
            if ($fs->create_file_from_string($filerec, $inittext)) {
                return true;
            }
            return false;
        }

        // If the format is a value from the legacy templates we store the initial file from one of them.
        $templates = static::get_legacy_templates();
        if ($filepath = ($templates[$collabora->format] ?? '')) {
            $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
            $filerec->filename = $collabora->format . '.' . $ext;
            if ($fs->create_file_from_pathname($filerec, $filepath)) {
                return true;
            }
            return false;
        }

        // The last check is for the system templates.
        // It uses the pathnamehash from the template file.
        $file = $fs->get_file_by_hash($collabora->format);
        if (empty($file)) {
            throw new \moodle_exception('filenotfound', 'error');
        }

        $filerec->filename = $file->get_filename();
        if ($fs->create_file_from_storedfile($filerec, $file)) {
            return true;
        }
        return false;
    }

    /**
     * Returns a list of dynamic format menu items.
     *
     * This method returns an array of format IDs that should be displayed as dynamic menu items in the format selection UI.
     * Currently the items are:
     * - upload
     * - text
     *
     * @return array List of format IDs to display in the dynamic format menu.
     */
    public static function get_format_menu_dynamic_items() {
        return [
            static::FORMAT_UPLOAD,
            static::FORMAT_TEXT,
        ];
    }

    /**
     * Returns a list of legacy templates.
     * This method returns an array of legacy templates that can be used as initial files for Collabora Online activities.
     * The keys of the array are the format IDs and the values are the file paths of the templates.
     *
     * @return array List of legacy templates.
     */
    public static function get_legacy_templates() {
        global $CFG;
        return [
            static::FORMAT_SPREADSHEET   => $CFG->dirroot . '/mod/collabora/blankfiles/blankspreadsheet.xlsx',
            static::FORMAT_WORDPROCESSOR => $CFG->dirroot . '/mod/collabora/blankfiles/blankdocument.docx',
            static::FORMAT_PRESENTATION  => $CFG->dirroot . '/mod/collabora/blankfiles/blankpresentation.pptx',
        ];
    }

    /**
     * Returns a list of available Collabora Online templates.
     *
     * This method retrieves all the files stored in the 'mod_collabora' component's 'FILEAREA_TEMPLATE' file area,
     * and returns an associative array where the keys are the file IDs and the values are the file names.
     *
     * @return array An associative array of available Collabora Online templates,
     *               where the keys are the file IDs and the values are the file names.
     */
    public static function get_templates() {
        $context = \context_system::instance();
        $fs = get_file_storage();

        $templates = $fs->get_area_files(
            $context->id,                    // Param contextid.
            'mod_collabora',                 // Param component.
            collabora_fs::FILEAREA_TEMPLATE, // Param filearea.
            false,                           // Param itemid.
            'filename',                      // Param sort.
            false                            // Param includedirs.
        );

        $templatelist = [];
        foreach ($templates as $file) {
            $templatelist[$file->get_pathnamehash()] = $file->get_filename();
        }
        return $templatelist;
    }

    /**
     * Fixes any legacy initial files for the Collabora Online activity.
     *
     * This method ensures that the initial file for the Collabora Online activity is set up correctly.
     * If no initial file is found, it will store a default initial file.
     * Note: This method is only called after a restore of a legacy backup.
     *
     * @param \mod_collabora\api\collabora $collabora The Collabora Online API instance.
     * @param \stdClass $cm The course module object.
     */
    public static function fix_legacy_initfiles($collabora, $cm) {
        $context = \context_module::instance($cm->id);
        $fs    = get_file_storage();
        $files = $fs->get_area_files(
            $context->id,                   // Param contextid.
            'mod_collabora',                // Param component.
            collabora_fs::FILEAREA_INITIAL, // Param filearea.
            false,                          // Param itemid.
            'filename',                     // Param sort.
            false,                          // Param includedirs.
            0,                              // Param updatedsince.
            0,                              // Param limitfrom.
            1                               // Param limitnum.
        );
        if ($files) {
            $file  = reset($files);
        }
        if (empty($file)) {
            static::store_initial_file($collabora, $cm->id);
        }
    }

    /**
     * Returns an associative array of yes/no options.
     *
     * @return array An array where the keys are 1 and 0, and the values are the localized strings for 'yes' and 'no'.
     */
    public static function yesno_options() {
        return [
            1 => get_string('yes'),
            0 => get_string('no'),
        ];
    }
}
