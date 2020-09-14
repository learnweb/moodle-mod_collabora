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
 * Plugin upgrade script
 *
 * @package   mod_collabora
 * @copyright 2019 Jan Dageförde, WWU Münster
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
function xmldb_collabora_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();
    if ($oldversion < 2020091300) {

        $table = new xmldb_table('collabora_document');

        $field = new xmldb_field('watermark_text', XMLDB_TYPE_CHAR, '127', null, null, null, null, 'locked');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('enable_owner_termination', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'watermark_text');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('disable_print', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'enable_owner_termination');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('disable_export', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'disable_print');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('disable_copy', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'disable_export');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('enable_insert_remote_image', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'disable_copy');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('disable_change_tracking_record', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'enable_insert_remote_image');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('disable_change_tracking_show', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'disable_change_tracking_record');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('hide_change_tracking_controls', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'disable_change_tracking_show');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Collabora savepoint reached.
        upgrade_mod_savepoint(true, 2020091300, 'collabora');
    }

    return true;
}
