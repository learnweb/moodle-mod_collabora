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
 * Main plugin entry point
 *
 * @package   mod_collabora
 * @copyright 2019 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
global $PAGE, $DB, $USER;

$cmid = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', false, true);

list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'collabora');

$myurl = new \moodle_url($FULLME);
$myurl->remove_all_params();
$myurl->param('id', $cm->id);

$context = \context_course::instance($course->id);
$PAGE->set_url($myurl);
$PAGE->set_pagelayout('admin');

require_login($course, false, $cm);
require_capability('mod/collabora:repair', $PAGE->context);

$rec = $DB->get_record('collabora', ['id' => $cm->instance], '*', MUST_EXIST);
$confirmurl = new \moodle_url($myurl, array('confirm' => true));
$returnurl = new \moodle_url('/mod/collabora/view.php', array('id' => $cm->id));

// Handle groups selection.
$groupid = groups_get_activity_group($cm, true);
if ($groupid === false) {
    $groupid = 0; // No groups, so use id 0 for everyone.
} else if ($groupid === 0) {
    // Groups in use, but none currently selected, so we need to find the first available group.
    $allgroups = has_capability('moodle/site:accessallgroups', $PAGE->context);
    // Start with groups we are a member of.
    $allowedgroups = groups_get_all_groups($cm->course, $USER->id, $cm->groupingid);
    if (!$allowedgroups && ($allgroups || groups_get_activity_groupmode($cm) === VISIBLEGROUPS)) {
        // Not a member of any groups, but can see some groups, so get the full list.
        $allowedgroups = groups_get_all_groups($cm->course, 0, $cm->groupingid);
    }
    if (!$allowedgroups) {
        // No access to any group - will just display a warning message.
        $groupid = -1;
    } else {
        // Phew ... we found some group(s) we can access, so display the first one.
        $firstgroup = reset($allowedgroups);
        $groupid = $firstgroup->id;
    }
}

$renderer = $PAGE->get_renderer('mod_collabora');

if ($confirm) {
    require_sesskey();

    // Load the collabora object related to the context, group and user.
    $collabora = new \mod_collabora\api\collabora($rec, $PAGE->context, $groupid, $USER->id);
    // Try to repair the document.
    if ($collabora->process_repair()) {
        $msg = get_string('repair_succeeded', 'mod_collabora');
        $msgtype = \core\notification::SUCCESS;
    } else {
        $msg = get_string('repair_failed', 'mod_collabora');
        $msgtype = \core\notification::ERROR;
    }
    redirect($returnurl, $msg, null, $msgtype);
}

$confirm = new \mod_collabora\output\confirmation(
    $confirmurl,
    $returnurl,
    get_string('repairdocument', 'mod_collabora', $rec->name),  // The title string.
    get_string('repairdocumentconfirm', 'mod_collabora'),       // The confirmation question.
    get_string('repair', 'mod_collabora'),                      // The label of the confirm button.
    null,
    get_string('repairdocumentconfirm_help', 'mod_collabora')   // The moreinfo text to show additional infos.
);

// Start the output.
echo $renderer->header();
echo $renderer->render($confirm);
echo $renderer->footer();
