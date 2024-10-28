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
 * This plugin extends the auth_oidc plugin with additional features for schools.
 *
 * @package    mod_collabora
 * @copyright  2021 (http://www.grabs-edv.de)
 * @author     Andreas Grabs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_collabora\setting_tab;
use mod_collabora\util;

/**
 * Settings tab for template settings.
 */
class template extends base {

    /**
     * Get a tab based settings page
     *
     * @return \admin_settingpage
     */
    public static function get_settings_tab() {

        $title = get_string('setting_templates', 'mod_collabora');

        $tabid = self::get_tab_id();
        $tabname = $title;

        $page = new \admin_settingpage($tabid, $tabname);

        $page->add(
            new \admin_setting_heading(
                'mod_collabora_hdr',
                $title,
                ''
            )
        );

        // Add a filemanager to manage template files.
        $filemanageroptions = [];
        $filemanageroptions['accepted_types'] = \mod_collabora\api\collabora_fs::get_accepted_types();
        $filemanageroptions['maxbytes'] = 0;
        $filemanageroptions['subdirs'] = 0;
        $filemanageroptions['maxfiles'] = -1; // No limit.
        $filemanageroptions['mainfile'] = false;

        $page->add(
            new \admin_setting_configstoredfile(
                'mod_collabora/templates',
                get_string('templates', 'mod_collabora'),
                '',
                \mod_collabora\api\collabora_fs::FILEAREA_TEMPLATE,
                0,
                $filemanageroptions
            )
        );

        $page->add(
            new \admin_setting_configselect(
                'mod_collabora/defaultformat',
                get_string('defaultformat', 'mod_collabora'),
                '',
                '',
                util::grouped_format_menu(false)
            )
        );

        $page->add(
            new \admin_setting_configselect(
                'mod_collabora/showlegacytemplates',
                get_string('setting_showlegacytemplates', 'mod_collabora'),
                get_string('setting_showlegacytemplates_help', 'mod_collabora'),
                1,
                util::yesno_options()
            )
        );

        return $page;
    }
}
