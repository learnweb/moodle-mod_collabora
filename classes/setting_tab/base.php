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

/**
 * Settings tab base class.
 */
class base {
    /**
     * Get the tab id built from the class name.
     * @return string
     */
    public static function get_tab_id() {
        return str_replace('\\', '_', get_called_class());
    }
}
