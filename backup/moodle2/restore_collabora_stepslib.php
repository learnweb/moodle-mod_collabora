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

use mod_collabora\api\collabora_fs;

/**
 * Define all the restore steps that will be used by the restore_collabora_activity_task.
 *
 * @package    mod_collabora
 * @copyright 2019 Davo Smith, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_collabora_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define the structure of the restore workflow.
     *
     * @return restore_path_element[]
     */
    protected function define_structure() {
        $paths    = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('collabora', '/activity/collabora');
        if ($userinfo) {
            $paths[] = new restore_path_element('collabora_document', '/activity/collabora/documents/document');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process a collabora restore.
     *
     * @param  object $data The data in object form
     * @return void
     */
    protected function process_collabora($data) {
        global $DB;

        $data         = (object) $data;
        $data->course = $this->get_courseid();

        $data->timecreated  = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('collabora', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process a collabora document restore.
     *
     * @param  object $data The data in object form
     * @return void
     */
    protected function process_collabora_document($data) {
        global $DB;

        $data       = (object) $data;
        $oldid      = $data->id;
        $oldgroupid = $data->groupid;

        $data->collaboraid = $this->get_new_parentid('collabora');
        $data->groupid     = $data->groupid ? $this->get_mappingid('group', $data->groupid) : 0;
        $data->repaircount = 0;
        $data->doctoken = collabora_fs::get_unique_table_token('collabora_document', 'doctoken');

        $newitemid = $DB->insert_record('collabora_document', $data);
        $this->set_mapping('collabora_document', $oldid, $newitemid);
        $this->set_mapping('collabora_group', $oldgroupid, $data->groupid, true);
    }

    /**
     * Restore the related files after the database structure is ready.
     * @return void
     */
    protected function after_execute() {
        // Add collabora related files.
        $this->add_related_files('mod_collabora', 'intro', null);
        $this->add_related_files('mod_collabora', collabora_fs::FILEAREA_INITIAL, null);
        $this->add_related_files('mod_collabora', collabora_fs::FILEAREA_GROUP, 'collabora_group');
    }

    /**
     * Fix the version files after the restore is done.
     * @return void
     */
    protected function after_restore() {
        global $DB;

        $collabora = $DB->get_record('collabora', ['id' => $this->task->get_activityid()]);
        $cm = get_coursemodule_from_instance('collabora', $collabora->id);

        // Fix the file stamps.
        $this->fix_file_timestamps($cm);

        // Add missing initial files if needed.
        // Old backups might come without them.
        \mod_collabora\util::fix_legacy_initfiles($collabora, $cm);
    }

    /**
     * Fix the timemodified stamp from all newly created version files.
     *
     * @param \stdClass $cm
     * @return void
     */
    protected function fix_file_timestamps($cm) {
        $context = \context_module::instance($cm->id);

        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $context->id,
            'mod_collabora',
            collabora_fs::FILEAREA_GROUP,
            false,
            // The sorting is important because of the way we store document versions.
            'filepath',
            false
        );
        // Set the timemodified field to the value from the filepath which represents the version.
        foreach ($files as $file) {
            $version = $file->get_filepath();
            if ($version == '/') {
                continue;
            }
            $version = trim($version, '/');
            if (intval($version) && $version > 0) {
                $timestamp = $version;
            }
            $file->set_timemodified($timestamp);
        }

    }
}
