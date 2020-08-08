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
 * Global settings
 *
 * @package   mod_collabora
 * @copyright 2019 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $productname = '';
    try {
        // If we have a URL, fetch the product name from the server.
        // And if we had it, re-fetch it, in case it's changed.
        if (get_config('mod_collabora', 'url')) {
            $collabora = new \mod_collabora\collabora(0, 0, -1, 0);
            // Remove from the cache to force fetching.
            $collabora->reset_caches();
            $productname = $collabora->get_product_name();
        }
    } catch (Exception $e) {
        // For some reason we couldn't discover the server name.
        // Possibly the server doesn't have the capabilities file.
        $productname = '';
    }
    finally {
        // Fallback.
        if (empty($productname)) {
            $cache = \cache::make('mod_collabora', 'capabilities');
            if (!$productname = $cache->get('productname')) {
                // If it's not already cached, default, as we are aren't configured yet.
                $productname = get_string('default_server_name', 'mod_collabora');
            }
        }
    }

    $settings->add(new admin_setting_configtext('mod_collabora/url',
                                                new lang_string('collaboraurl', 'mod_collabora', $productname), '', '', PARAM_URL));

    $settings->add(new admin_setting_configselect('mod_collabora/defaultformat',
                                                  new lang_string('defaultformat', 'mod_collabora'), '',
                                                  \mod_collabora\collabora::FORMAT_WORDPROCESSOR,
                                                  \mod_collabora\collabora::format_menu()));

    $settings->add(new admin_setting_configselect('mod_collabora/defaultdisplay',
                                                  new lang_string('defaultdisplay', 'mod_collabora'), '',
                                                  \mod_collabora\collabora::DISPLAY_CURRENT,
                                                  \mod_collabora\collabora::display_menu()));

    $yesno = [1 => new lang_string('yes'), 0 => new lang_string('no')];
    $settings->add(new admin_setting_configselect('mod_collabora/defaultdisplayname',
                                                  new lang_string('defaultdisplayname', 'mod_collabora'), '',
                                                  1, $yesno));

    $settings->add(new admin_setting_configselect('mod_collabora/defaultdisplaydescription',
                                                  new lang_string('defaultdisplaydescription', 'mod_collabora'), '',
                                                  1, $yesno));
}
