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

namespace mod_collabora\fragment;

/**
 * Util class for fragment api.
 *
 * @package    mod_collabora
 *
 * @author     Andreas Grabs <moodle@grabs-edv.de>
 * @copyright  2019 Humboldt-Universität zu Berlin <moodle-support@cms.hu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class util {
    /**
     * Returns the html fragment.
     *
     * @param  array             $args
     * @throws \moodle_exception
     * @return string
     */
    public static function get_html($args) {
        if (empty($args['function'])) {
            throw new \moodle_exception('missing argument "function"');
        }
        $function = 'get_' . $args['function'];
        if (!method_exists(static::class, $function)) {
            throw new \moodle_exception('Wrong function "' . $function . '"');
        }

        return static::{$function}($args);
    }

    /**
     * Get the html content for the version_viewer.
     *
     * @param [] $args
     * @return string The rendered html
     */
    protected static function get_version_viewer_content($args) {
        global $OUTPUT;

        $id = $args['id'] ?? false;
        if (empty($id)) {
            throw new \moodle_exception('missing or wrong id');
        }

        $version = $args['version'] ?? 0;

        list($course, $cm) = get_course_and_cm_from_instance($id, 'collabora');
        $versionwidget     = new \mod_collabora\output\version_viewer_content($cm, $version);

        return $OUTPUT->render($versionwidget);
    }

    /**
     * Get the wopi src for a javascript as json string.
     *
     * @param [] $args
     * @return string
     */
    protected static function get_wopi_src($args) {
        global $DB, $USER;

        $version = $args['version'] ?? 0;
        $id      = $args['id'] ?? false;
        if (empty($id)) {
            throw new \moodle_exception('missing or wrong id');
        }

        list($course, $cm) = get_course_and_cm_from_instance($id, 'collabora');
        $collabora         = $DB->get_record('collabora', ['id' => $cm->instance], '*', MUST_EXIST);
        $groupid           = \mod_collabora\util::get_current_groupid_from_cm($cm);

        $collaborafs = new \mod_collabora\api\collabora_fs($collabora, $cm->context, $groupid, $USER->id, $version);
        $params      = $collaborafs->get_view_params();

        return json_encode($params);
    }
}
