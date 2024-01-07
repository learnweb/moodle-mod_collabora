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
class version_viewer_content implements \renderable, \templatable {
    /** @var \stdClass */
    private $data;

    /**
     * Constructor.
     *
     * @param \cm_info $cm
     * @param int      $displayedversion // The version actually displayed but not necessarily the current version
     */
    public function __construct(\cm_info $cm, int $displayedversion) {
        global $USER, $DB;

        $this->data = new \stdClass();

        // Handle groups selection.
        $groupid   = \mod_collabora\util::get_current_groupid_from_cm($cm);
        $collabora = $DB->get_record('collabora', ['id' => $cm->instance], '*', MUST_EXIST);

        $collaborafs     = new \mod_collabora\api\collabora_fs($collabora, $cm->context, $groupid, $USER->id);
        $currentfile     = $collaborafs->get_file();
        $versions        = $collaborafs->get_version_files();
        $versions        = array_reverse($versions);
        $currentfileinfo = $this->get_file_infos($currentfile, $cm, $displayedversion);

        $versioninfos = [];
        foreach ($versions as $version) {
            $versioninfos[] = $this->get_file_infos($version, $cm, $displayedversion);
        }

        $this->data->id              = $collabora->id;
        $this->data->currentfileinfo = $currentfileinfo;
        $this->data->versioninfos    = $versioninfos;
        $this->data->hasversions     = count($versioninfos) > 0;
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

    /**
     * Get an info object to a given stored_file.
     *
     * @param  \stored_file $file
     * @param  \cm_info     $cm
     * @param  int          $displayedversion
     * @return \stdClass
     */
    protected function get_file_infos(\stored_file $file, \cm_info $cm, int $displayedversion) {
        $version = $file->get_filepath();
        $version = trim($version, '/');

        if (empty($version)) {
            $version = 0;
        }

        $fileinfo = new \stdClass();
        if ($version == $displayedversion) {
            $fileinfo->iscurrent = true;
        }
        $fileinfo->fileid       = $file->get_id();
        $fileinfo->filename     = $file->get_filename();
        $fileinfo->timecreated  = userdate($file->get_timecreated());
        $fileinfo->timemodified = userdate($file->get_timemodified());
        if (!empty($version)) {
            $fileinfo->version     = $version;
            $fileinfo->downloadurl = new \moodle_url('/mod/collabora/view.php', ['id' => $cm->id, 'loadversion' => $version]);
        }

        return $fileinfo;
    }
}
