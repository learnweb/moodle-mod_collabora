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
 * Settings tab for the connection settings
 */
class connection extends base {

    /**
     * Get a tab based settings page
     *
     * @return \admin_settingpage
     */
    public static function get_settings_tab() {

        $title = get_string('setting_connection', 'mod_collabora');

        $tabid = self::get_tab_id();
        $tabname = $title;

        $page = new \admin_settingpage($tabid, $tabname);
        $page->add(
            new \admin_setting_configtext(
                'mod_collabora/url',
                get_string('collaboraurl', 'mod_collabora'),
                '',
                '',
                PARAM_URL
            )
        );

        $page->add(
            new \admin_setting_configselect(
                'mod_collabora/defaultdisplay',
                get_string('defaultdisplay', 'mod_collabora'),
                '',
                util::DISPLAY_CURRENT,
                util::display_menu()
            )
        );

        $page->add(
            new \admin_setting_configselect(
                'mod_collabora/defaultdisplayname',
                get_string('defaultdisplayname', 'mod_collabora'),
                '',
                1,
                util::yesno_options()
            )
        );

        $page->add(
            new \admin_setting_configselect(
                'mod_collabora/defaultdisplaydescription',
                get_string('defaultdisplaydescription', 'mod_collabora'),
                '',
                1,
                util::yesno_options()
            )
        );

        $modlist = [
            util::UI_SERVER  => get_string('uiserver', 'mod_collabora'),
            util::UI_COMPACT => get_string('uicompact', 'mod_collabora'),
            util::UI_TABBED  => get_string('uitabbed', 'mod_collabora'),
        ];
        $page->add(
            new \admin_setting_configselect(
                'mod_collabora/uimode',
                get_string('uimode', 'mod_collabora'),
                '',
                util::UI_SERVER,
                $modlist
            )
        );

        $page->add(
            new \admin_setting_configselect(
                'mod_collabora/enableversions',
                get_string('enableversions', 'mod_collabora'),
                get_string('enableversions_help', 'mod_collabora'),
                1,
                util::yesno_options()
            )
        );

        $page->add(
            new \admin_setting_configselect(
                'mod_collabora/shareuserimages',
                get_string('setting_share_userimages', 'mod_collabora'),
                get_string('setting_share_userimages_help', 'mod_collabora'),
                1,
                util::yesno_options()
            )
        );

        $page->add(
            new \admin_setting_configselect(
                'mod_collabora/showserveraudit',
                get_string('setting_showserveraudit', 'mod_collabora'),
                get_string('setting_showserveraudit_help', 'mod_collabora'),
                0,
                util::yesno_options()
            )
        );

        $page->add(
            new \admin_setting_heading(
                'mod_collabora_security_hdr',
                get_string('setting_header_security', 'mod_collabora'),
                ''
            )
        );

        $page->add(
            new \admin_setting_configselect(
                'mod_collabora/allowcollaboraserverexplicit',
                get_string('setting_allowcollaboraserverexplicit', 'mod_collabora'),
                get_string('setting_allowcollaboraserverexplicit_help', 'mod_collabora'),
                0,
                util::yesno_options()
            )
        );

        return $page;
    }
}
