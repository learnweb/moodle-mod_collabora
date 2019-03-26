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
 * API functions
 *
 * @package   mod_collabora
 * @copyright 2019 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function collabora_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}

/**
 * @param $collabora
 * @param mod_collabora_mod_form $mform
 * @return int
 */
function collabora_add_instance($collabora, $mform) {
    global $DB;

    $collabora->timecreated = time();
    $collabora->timemodified = time();

    $collabora->id = $DB->insert_record('collabora', $collabora);

    $completiontimeexpected = !empty($collabora->completionexpected) ? $collabora->completionexpected : null;
    \core_completion\api::update_completion_date_event($collabora->coursemodule, 'collabora',
                                                       $collabora->id, $completiontimeexpected);

    // Save the 'initial file'.
    $context = context_module::instance($collabora->coursemodule);
    file_postupdate_standard_filemanager($collabora, 'initialfile', $mform->get_filemanager_opts(),
                                         $context, 'mod_collabora', \mod_collabora\collabora::FILEAREA_INITIAL, 0);

    return $collabora->id;

}

function collabora_update_instance($collabora) {
    global $DB;

    $collabora->id = $collabora->instance;
    $collabora->timemodified = time();

    $DB->update_record('collabora', $collabora);

    $completiontimeexpected = !empty($collabora->completionexpected) ? $collabora->completionexpected : null;
    \core_completion\api::update_completion_date_event($collabora->coursemodule, 'collabora',
                                                       $collabora->id, $completiontimeexpected);

    // Do not save the 'initial file' here, as you cannot change this after the activity has been created.

    return true;
}

function collabora_delete_instance($id) {
    global $DB;

    if (!$collabora = $DB->get_record('collabora', ['id' => $id])) {
        return false;
    }

    $cm = get_coursemodule_from_instance('collabora', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'collabora', $id, null);

    $DB->delete_records('collabora_document', ['collaboraid' => $collabora->id]);
    $DB->delete_records('collabora', ['id' => $collabora->id]);

    return true;
}

function collabora_get_coursemodule_info($coursemodule) {
    global $DB, $USER;
    if (!$collabora = $DB->get_record('collabora', ['id' => $coursemodule->instance])) {
        return null;
    }

    $info = new cached_cm_info();
    if ($collabora->display === \mod_collabora\collabora::DISPLAY_NEW) {
        // Use javascript to open the link in a new tab.
        $url = new moodle_url('/mod/collabora/view.php', ['id' => $coursemodule->id]);
        $info->onclick = 'event.preventDefault();window.open("'.$url->out(false).'", "_blank").focus();';
    }
    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('collabora', $collabora, $coursemodule->id, false);
    }

    $instance = new \mod_collabora\collabora($collabora, context_module::instance($coursemodule->id), 0, $USER->id);
    if ($specificicon = $instance->get_module_icon()) {
        $info->icon = $specificicon;
    }
    return $info;
}

function collabora_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_MODULE) {
        return;
    }
    require_login($course, false, $cm);
    // File link only occurs on the edit settings page, so restrict access to teachers.
    if (!has_capability('moodle/course:manageactivities', $context)) {
        return;
    }

    if ($filearea !== \mod_collabora\collabora::FILEAREA_INITIAL) {
        return;
    }

    $itemid = (int)array_shift($args);
    if ($itemid !== 0) {
        return;
    }

    $filename = array_pop($args);
    $filepath = '/'.implode('/', $args);
    if ($filepath !== '/') {
        $filepath .= '/';
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_collabora', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return;
    }

    send_stored_file($file, null, 0, $forcedownload, $options);
}