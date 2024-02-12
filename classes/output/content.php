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

namespace mod_collabora\output;

/**
 * Output class to render the collabora iframe page.
 *
 * @package    mod_collabora
 *
 * @author     Andreas Grabs <moodle@grabs-edv.de>
 * @copyright  2019 Humboldt-Universität zu Berlin <moodle-support@cms.hu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content implements \renderable, \templatable {
    /** @var \stdClass */
    private $data;

    /**
     * Constructor.
     *
     * @param \cm_info                        $cm
     * @param \stdClass                       $instance
     * @param \mod_collabora\api\collabora_fs $collaborafs
     * @param int                             $groupid
     */
    public function __construct(\cm_info $cm, \stdClass $instance, \mod_collabora\api\collabora_fs $collaborafs, int $groupid) {
        global $PAGE;

        $this->data           = new \stdClass();
        $this->data->id       = $instance->id;
        $this->data->filename = $collaborafs->display_name() ? format_string($collaborafs->get_file()->get_filename()) : '';

        if ($PAGE->pagelayout == 'embedded') {
            $this->data->embedded = true;
        }

        if ($groupid >= 0) {
            if (groups_get_activity_groupmode($cm) === SEPARATEGROUPS) {
                $hideallparticipants = true;
            } else {
                $hideallparticipants = false;
            }
            $this->data->activitymenu = groups_print_activity_menu($cm, $PAGE->url, true, $hideallparticipants);
            $this->data->lockicon     = $collaborafs->get_lock_icon();

            if (empty($this->data->embedded)) {
                $this->data->frameheight = $instance->height ?? false;
            }

            // Create a url to load the last state of the document without using collabora.
            // This is very usefull if collabora is not working.
            if (has_capability('mod/collabora:directdownload', $cm->context)) {
                $loadfileurl = $PAGE->url;
                $loadfileurl->param('loadcurrentfile', '1');
                $this->data->loadfileurl = $loadfileurl->out(false);
            }
        } else {
            $this->data->warning = get_string('nogroupaccess', 'mod_collabora');
        }

        // Add a warning notice.
        if (\mod_collabora\api\collabora_fs::is_testing()) {
            $this->data->hasnotice  = true;
            $this->data->noticetype = \core\notification::WARNING;
            $this->data->notice     = get_string('collaboraurlnotset', 'mod_collabora');
        }
    }

    /**
     * Function to export the renderer data in a format that is suitable for a
     * mustache template. This means:
     * 1. No complex types - only stdClass, array, int, string, float, bool
     * 2. Any additional info that is required for the template is pre-calculated (e.g. capability checks).
     *
     * @param  \renderer_base  $output used to do a final render of any components that need to be rendered for export
     * @return \stdClass|array
     */
    public function export_for_template(\renderer_base $output) {
        return $this->data;
    }
}
