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

/**
 * Util class for fragment api.
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
     * Get an array for the activity format settings menu.
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
}
