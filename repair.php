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

$cmid    = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', false, PARAM_BOOL);

list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'collabora');

$myurl = new \moodle_url($FULLME);
$myurl->remove_all_params();
$myurl->param('id', $cm->id);

$context = \context_course::instance($course->id);
$PAGE->set_url($myurl);
$PAGE->set_pagelayout('admin');

require_login($course, false, $cm);
require_capability('mod/collabora:repair', $PAGE->context);

$rec        = $DB->get_record('collabora', ['id' => $cm->instance], '*', MUST_EXIST);
$confirmurl = new \moodle_url($myurl, ['confirm' => true]);
$returnurl  = new \moodle_url('/mod/collabora/view.php', ['id' => $cm->id]);

// Handle groups selection.
$groupid = \mod_collabora\util::get_current_groupid_from_cm($cm);

/** @var \plugin_renderer_base $renderer */
$renderer = $PAGE->get_renderer('mod_collabora');

if ($confirm) {
    require_sesskey();

    // Load the collabora object related to the context, group and user.
    $collaborafs = new \mod_collabora\api\collabora_fs($rec, $PAGE->context, $groupid, $USER->id);
    // Try to repair the document.
    if ($collaborafs->process_repair()) {
        $msg     = get_string('repair_succeeded', 'mod_collabora');
        $msgtype = \core\notification::SUCCESS;
    } else {
        $msg     = get_string('repair_failed', 'mod_collabora');
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
