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
 * Define all the backup steps that will be used by the backup_collabora_activity_task.
 *
 * @package    mod_collabora
 * @copyright 2019 Davo Smith, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_collabora_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the structure for the collabora activity.
     * @return backup_nested_element
     */
    protected function define_structure() {
        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $collabora = new backup_nested_element('collabora', ['id'], [
            'name', 'intro', 'introformat', 'timecreated', 'timemodified',
            'format', 'initialtext', 'display', 'height', 'displayname',
            'displaydescription',
        ]);

        $documents = new backup_nested_element('documents');

        $document = new backup_nested_element('document', ['id'], [
            'groupid', 'locked',
        ]);

        // Build the tree.
        $collabora->add_child($documents);
        $documents->add_child($document);

        // Define sources.
        $collabora->set_source_table('collabora', ['id' => backup::VAR_ACTIVITYID]);

        // All these source definitions only happen if we are including user info.
        if ($userinfo) {
            $document->set_source_table('collabora_document', ['collaboraid' => backup::VAR_PARENTID]);
        }

        // Define id annotations.
        $document->annotate_ids('group', 'groupid');

        // Define file annotations.
        $collabora->annotate_files('mod_collabora', 'intro', null); // This file area hasn't itemid.
        $collabora->annotate_files('mod_collabora', \mod_collabora\api\collabora_fs::FILEAREA_INITIAL, null);
        $document->annotate_files('mod_collabora', \mod_collabora\api\collabora_fs::FILEAREA_GROUP, 'groupid');

        // Return the root element (collabora), wrapped into standard activity structure.
        return $this->prepare_activity_structure($collabora);
    }
}
