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
 * @package    mod_collabora
 * @copyright 2019 Davo Smith, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define all the restore steps that will be used by the restore_collabora_activity_task
 */

/**
 * Structure step to restore one collabora activity
 */
class restore_collabora_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('collabora', '/activity/collabora');
        if ($userinfo) {
            $paths[] = new restore_path_element('collabora_document', '/activity/collabora/documents/document');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_collabora($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('collabora', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_collabora_document($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $oldgroupid = $data->groupid;

        $data->collaboraid = $this->get_new_parentid('collabora');
        $data->groupid = $data->groupid ? $this->get_mappingid('group', $data->groupid) : 0;

        $newitemid = $DB->insert_record('collabora_document', $data);
        $this->set_mapping('collabora_document', $oldid, $newitemid);
        $this->set_mapping('collabora_group', $oldgroupid, $data->groupid, true);
    }

    protected function after_execute() {
        // Add collabora related files.
        $this->add_related_files('mod_collabora', 'intro', null);
        $this->add_related_files('mod_collabora', \mod_collabora\collabora::FILEAREA_INITIAL, null);
        $this->add_related_files('mod_collabora', \mod_collabora\collabora::FILEAREA_GROUP, 'collabora_group');
    }
}
