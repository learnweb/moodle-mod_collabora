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
 *
 * @author     Andreas Grabs <moodle@grabs-edv.de>
 * @copyright  2019 Humboldt-Universität zu Berlin <moodle-support@cms.hu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_collabora\output;

defined('MOODLE_INTERNAL') || die();

class content implements \renderable, \templatable {
    /** @var \stdClass $data */
    private $data;

    public function __construct(\cm_info $cm, \stdClass $instance, \mod_collabora\collabora $collabora, int $groupid) {
        global $PAGE;

        $this->data = new \stdClass();
        $this->data->id = $instance->id;
        $this->data->name = $collabora->display_name() ? format_string($instance->name) : '';

        if ($PAGE->pagelayout == 'embedded') {
            $this->data->embedded = true;
        } else {
            // Description should only be shown in non embedded pages.
            if ($collabora->display_description() && trim(strip_tags($instance->intro))) {
                $this->data->description = format_module_intro('collabora', $instance, $cm->id);
            }
        }

        if ($groupid >= 0) {
            $this->data->activitymenu = groups_print_activity_menu($cm, $PAGE->url, true, true);
            $this->data->lockicon = $collabora->get_lock_icon();

            $viewurl = $collabora->get_view_url();
            $this->data->viewurl = $viewurl->out(false);

            $this->data->framewidth = $instance->width ? $instance->width . 'px' : '100%';
            $this->data->frameheight = $instance->height ? $instance->height . 'px' : '60vh';

            /** @var \mod_collabora\output\renderer $renderer */
            $renderer = $PAGE->get_renderer('mod_collabora');
            $this->data->legacy = !$renderer->is_boost_based();

        } else {
            $this->data->warning = get_string('nogroupaccess', 'mod_collabora');
        }
    }

    public function export_for_template(\renderer_base $output) {
        return $this->data;
    }
}
