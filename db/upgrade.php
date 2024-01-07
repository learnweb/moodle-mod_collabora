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
 * Plugin upgrade script.
 *
 * @package   mod_collabora
 * @copyright 2019 Jan Dageförde, WWU Münster
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @param mixed $oldversion
 */

/**
 * Upgrade function for mod_collabora.
 *
 * @param string $oldversion the version we are upgrading from
 */
function xmldb_collabora_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2021102400) {
        // Define field repaircount to be added to collabora_document.
        $table = new xmldb_table('collabora_document');
        $field = new xmldb_field('repaircount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'locked');

        // Conditionally launch add field repaircount.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Collabora savepoint reached.
        upgrade_mod_savepoint(true, 2021102400, 'collabora');
    }

    if ($oldversion < 2022042002) {
        // Define field sid to be added to collabora_token.
        $table = new xmldb_table('collabora_token');
        $field = new xmldb_field('sid', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'token');

        // Conditionally launch add field sid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Now we combine sid with userid so we remove the unique userid index.
        $table = new xmldb_table('collabora_token');
        $index = new xmldb_index('userid', XMLDB_INDEX_UNIQUE, ['userid']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        \mod_collabora\api\collabora_fs::remove_unused_tokens();

        // Collabora savepoint reached.
        upgrade_mod_savepoint(true, 2022042002, 'collabora');
    }

    if ($oldversion < 2024010700) {
        // Define field width to be dropped from collabora.
        $table = new xmldb_table('collabora');
        $field = new xmldb_field('width');

        // Conditionally launch drop field width.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Collabora savepoint reached.
        upgrade_mod_savepoint(true, 2024010700, 'collabora');
    }

    return true;
}
