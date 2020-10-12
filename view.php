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

list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'collabora');
$rec = $DB->get_record('collabora', ['id' => $cm->instance], '*', MUST_EXIST);

$PAGE->set_url('/mod/collabora/view.php', ['id' => $cm->id]);
require_login($course, false, $cm);
require_capability('mod/collabora:view', $PAGE->context);

// Trigger course_module_viewed event.
\mod_collabora\event\course_module_viewed::trigger_from_course_cm($course, $cm, $rec);

// Completion.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

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

// Load the collabora details for this page.
$collabora = new \mod_collabora\collabora($rec, $PAGE->context, $groupid, $USER->id);
$collabora->process_lock_unlock();

// Set up the page.
$PAGE->set_title($rec->name);
$PAGE->set_heading($course->fullname);
$closewindow = false;
if ($rec->display === \mod_collabora\collabora::DISPLAY_NEW) {
    $PAGE->set_pagelayout('embedded');
    $closewindow = true;
}

$opts = [
    'courseurl' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(),
    'closewindow' => $closewindow,
];
$PAGE->requires->js_call_amd('mod_collabora/monitorclose', 'init', [$opts]);

$renderer = $PAGE->get_renderer('mod_collabora');

// Start the output.
echo $renderer->header();

// Main iframe (or warning message, if no groups available).
$widget = new \mod_collabora\output\content(
    $cm,
    $rec,
    $collabora,
    $groupid
);

echo $renderer->render($widget);
echo $renderer->footer();
