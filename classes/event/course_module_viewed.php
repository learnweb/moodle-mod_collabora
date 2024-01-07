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
 * The mod_collabora course module viewed event.
 *
 * @package    mod_collabora
 * @copyright  2019 Davo Smith, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_collabora\event;

/**
 * The mod_collabora course module viewed event class.
 *
 * @package    mod_collabora
 * @copyright  2019 Davo Smith, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_module_viewed extends \core\event\course_module_viewed {
    /**
     * Trigger the event.
     *
     * @param  \stdClass $course
     * @param  \stdClass $cm
     * @param  \stdClass $collabora
     * @return void
     */
    public static function trigger_from_course_cm($course, $cm, $collabora) {
        $params = [
            'context'  => \context_module::instance($cm->id),
            'objectid' => $collabora->id,
        ];
        $event = self::create($params);
        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('course', $course);
        $event->add_record_snapshot('collabora', $collabora);
        $event->trigger();
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = 'collabora';
        $this->data['crud']        = 'r';
        $this->data['edulevel']    = self::LEVEL_PARTICIPATING;
    }

    /**
     * Get the object mapping.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'collabora', 'restore' => 'collabora'];
    }
}
