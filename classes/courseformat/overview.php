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

namespace mod_collabora\courseformat;

use core\output\action_link;
use core\output\local\properties\button;
use core\output\local\properties\text_align;
use core\url;
use core_courseformat\local\overview\overviewitem;

/**
 * Etherpad Lite overview integration (for Moodle 5.1+)
 *
 * @package   mod_collabora
 * @author    Andreas Grabs <info@grabs-edv.de>
 * @copyright 2021 onwards Grabs EDV {@link https://www.grabs-edv.de}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overview extends \core_courseformat\activityoverviewbase {
    /**
     * Constructor.
     *
     * @param \cm_info $cm the course module instance.
     * @param \core\output\renderer_helper $rendererhelper the renderer helper.
     */
    public function __construct(
        \cm_info $cm,
        /** @var \core\output\renderer_helper $rendererhelper the renderer helper */
        protected readonly \core\output\renderer_helper $rendererhelper,
    ) {
        parent::__construct($cm);
    }

    #[\Override]
    public function get_actions_overview(): ?overviewitem {
        $url = new url(
            '/mod/collabora/view.php',
            ['id' => $this->cm->id],
        );

        $text = get_string('view');
        // The button::BODY_OUTLINE constant is available since Moodle 5.1.
        // So we have to check the existing here.
        if (defined(button::class . '::BODY_OUTLINE')) {
            $bodyclass = button::BODY_OUTLINE->classes();
        } else {
            $bodyclass = button::SECONDARY_OUTLINE->classes();
        }

        $content = new action_link(
            url: $url,
            text: $text,
            attributes: ['class' => $bodyclass],
        );

        return new overviewitem(
            name: get_string('actions'),
            value: $text,
            content: $content,
            textalign: text_align::CENTER,
        );
    }
}
