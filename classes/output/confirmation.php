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

class confirmation implements \renderable, \templatable {
    /** @var \stdClass $data */
    private $data;

    /**
     * Constructor of confirmation class.
     *
     * @param \moodle_url $confirmurl
     * @param \moodle_url $cancelurl
     * @param string $title
     * @param string $confirmquestion
     * @param string|null $moreinfos
     */
    public function __construct(\moodle_url $confirmurl, \moodle_url $cancelurl,
                                    string $title, string $confirmquestion, string $confirmlabel = null,
                                    string $cancellabel = null, string $moreinfos = null) {
        global $OUTPUT;
        $confirmlabel = $confirmlabel === null ? get_string('ok') : $confirmlabel;
        $cancellabel = $cancellabel === null ? get_string('cancel') : $cancellabel;

        $confirmbutton = new \single_button($confirmurl, $confirmlabel, 'post', true);
        $cancelbutton = new \single_button($cancelurl, $cancellabel, 'get');
        $this->data = new \stdClass();
        $this->data->title = $title;
        $this->data->confirmquestion = $confirmquestion;
        $this->data->confirmbutton = $OUTPUT->render($confirmbutton);
        $this->data->cancelbutton = $OUTPUT->render($cancelbutton);
        $this->data->moreinfos = $moreinfos;
    }

    public function export_for_template(\renderer_base $output) {
        return $this->data;
    }
}
