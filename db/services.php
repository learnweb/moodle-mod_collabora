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
 * Service definition.
 *
 * @package   mod_collabora
 * @copyright 2024 Andreas Grabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
$functions = [
    // Restore version.
    'mod_collabora_restore_version' => [
        'classname'    => 'mod_collabora\external\services',
        'methodname'   => 'restore_version',
        'classpath'    => '',
        'description'  => 'Restore a document version.',
        'type'         => 'write',
        'capabilities' => 'mod/collabora:manageversions',
        'ajax'         => true,
    ],
    // Delete version.
    'mod_collabora_delete_version' => [
        'classname'    => 'mod_collabora\external\services',
        'methodname'   => 'delete_version',
        'classpath'    => '',
        'description'  => 'Delete a document version.',
        'type'         => 'write',
        'capabilities' => 'mod/collabora:manageversions',
        'ajax'         => true,
    ],
];
