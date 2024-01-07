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
 * List of all collabora instances in course.
 *
 * @package    mod_collabora
 * @copyright  2019 Davo Smith, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT); // Course id.

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_course_login($course);
$PAGE->set_pagelayout('incourse');

\mod_collabora\event\course_module_instance_list_viewed::trigger_from_course($course);

$strcollabora    = get_string('modulename', 'collabora');
$strcollaboras   = get_string('modulenameplural', 'collabora');
$strsectionname  = get_string('sectionname', 'format_' . $course->format);
$strname         = get_string('name');
$strintro        = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');

$PAGE->set_url('/mod/collabora/index.php', ['id' => $course->id]);
$PAGE->set_title($course->shortname . ': ' . $strcollaboras);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strcollaboras);
echo $OUTPUT->header();
echo $OUTPUT->heading($strcollaboras);

if (!$collaboras = get_all_instances_in_course('collabora', $course)) {
    notice(get_string('thereareno', 'moodle', $strcollaboras), "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}

$usesections = course_format_uses_sections($course->format);

$table                      = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $table->head  = [$strsectionname, $strname, $strintro];
    $table->align = ['center', 'left', 'left'];
} else {
    $table->head  = [$strlastmodified, $strname, $strintro];
    $table->align = ['left', 'left', 'left'];
}

$modinfo        = get_fast_modinfo($course);
$currentsection = '';
foreach ($collaboras as $collabora) {
    $cm = $modinfo->cms[$collabora->coursemodule];
    if ($usesections) {
        $printsection = '';
        if ($collabora->section !== $currentsection) {
            if ($collabora->section) {
                $printsection = get_section_name($course, $collabora->section);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $collabora->section;
        }
    } else {
        $printsection = '<span class="smallinfo">' . userdate($collabora->timemodified) . '</span>';
    }

    $extra = empty($cm->extra) ? '' : $cm->extra;
    $icon  = '';
    if (!empty($cm->icon)) {
        $icon = $OUTPUT->pix_icon($cm->icon, get_string('modulename', $cm->modname));
    }

    $class         = $collabora->visible ? '' : 'class="dimmed"'; // Hidden modules are dimmed.
    $table->data[] = [
        $printsection,
        "<a $class $extra href=\"view.php?id=$cm->id\">" . $icon . format_string($collabora->name) . '</a>',
        format_module_intro('collabora', $collabora, $cm->id),
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
