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
 * Main plugin entry point.
 *
 * @package   mod_collabora
 * @copyright 2019 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
global $PAGE, $DB, $USER;

$cmid            = required_param('id', PARAM_INT);
$loadcurrentfile = optional_param('loadcurrentfile', false, PARAM_BOOL);
$loadversion     = optional_param('loadversion', false, PARAM_INT); // The version is the timemodified timestamp.

list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'collabora');

$PAGE->set_url('/mod/collabora/view.php', ['id' => $cm->id]);
require_login($course, false, $cm);
require_capability('mod/collabora:view', $PAGE->context);

// Completion.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Handle groups selection.
$groupid = \mod_collabora\util::get_current_groupid_from_cm($cm);

$collabora = $DB->get_record('collabora', ['id' => $cm->instance], '*', MUST_EXIST);
// Trigger course_module_viewed event.
\mod_collabora\event\course_module_viewed::trigger_from_course_cm($course, $cm, $collabora);

// Load the collabora details for this page.
$collaborafs = new \mod_collabora\api\collabora_fs($collabora, $PAGE->context, $groupid, $USER->id);

if ($loadcurrentfile) {
    require_capability('mod/collabora:directdownload', $PAGE->context);
    $collaborafs->send_groupfile();
    die;
}

// Load the version file only if activated.
if ($collaborafs->use_versions()) {
    if (!empty($loadversion)) {
        require_capability('mod/collabora:manageversions', $PAGE->context);
        $collaborafs->send_version_file($loadversion);
        die;
    }
}

if ($collaborafs->process_lock_unlock()) {
    redirect($PAGE->url); // If the locking is changed we have to reload the page.
}

// Set up the page.
$PAGE->set_title($collabora->name);
$PAGE->set_heading($course->fullname);
$aspopup = false;
if ($collabora->display === \mod_collabora\util::DISPLAY_NEW) {
    $PAGE->set_pagelayout('embedded');
    $aspopup = true;
}

$opts = [
    'id'              => $collabora->id,
    'contextid'       => $PAGE->context->id,
    'collaboraurl'    => $collaborafs->get_collabora_url()->out(),
    'origincollabora' => $collaborafs->get_collabora_origin(),
    'originmoodle'    => $collaborafs->get_moodle_origin(),
    'courseurl'       => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(),
    'aspopup'         => $aspopup,
    'iframeid'        => 'collaboraiframe_' . $collabora->id,
    'useversions'     => $collaborafs->use_versions(),
    'versionviewerid' => 'version_viewer_' . $collabora->id,
    'versionmanager'  => has_capability('mod/collabora:manageversions', $cm->context),
    'strback'         => get_string('back'),
    'imgbackurl'      => $CFG->wwwroot . '/mod/collabora/pix/go_back.svg',
    'uimode'          => $collaborafs->get_ui_mode(),
];
$PAGE->requires->js_call_amd('mod_collabora/postmessage', 'init', [$opts]);

// Decide whether or not we show the description.
if ($PAGE->pagelayout == 'embedded' || !$collaborafs->display_description() || !trim(strip_tags($collabora->intro))) {
    $PAGE->activityheader->set_attrs(['description' => '']);
}

/** @var \mod_collabora\output\renderer $renderer */
$renderer = $PAGE->get_renderer('mod_collabora');

// Start the output.
echo $renderer->header();

// Main iframe (or warning message, if no groups available).
$widget = new \mod_collabora\output\content(
    $cm,
    $collabora,
    $collaborafs,
    $groupid
);

echo $renderer->render($widget);
echo $renderer->footer();
