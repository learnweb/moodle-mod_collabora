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

namespace mod_collabora;

/**
 * Util class for fragment api
 *
 * @package    mod_collabora
 *
 * @author     Andreas Grabs <moodle@grabs-edv.de>
 * @copyright  2019 Humboldt-Universität zu Berlin <moodle-support@cms.hu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class util {
    public static function get_current_groupid_from_cm(\cm_info $cm, \stdClass $user = null) {
        global $USER;

        if (empty($user)) {
            $user = $USER;
        }

        // Handle groups selection.
        $groupid = groups_get_activity_group($cm, true);
        if ($groupid === false) {
            $groupid = 0; // No groups, so use id 0 for everyone.
        } else if ($groupid === 0) {
            // Groups in use, but none currently selected, so we need to find the first available group.
            $allgroups = has_capability('moodle/site:accessallgroups', $cm->context);
            // Start with groups we are a member of.
            $allowedgroups = groups_get_all_groups($cm->course, $user->id, $cm->groupingid);
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
        return $groupid;
    }
}
