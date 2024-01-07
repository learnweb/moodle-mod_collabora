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
 * Change the iframe size relative to the window size.
 *
 * @module     mod_collabora/resizeiframe
 * @author     Andreas Grabs <moodle@grabs-edv.de>
 * @copyright  2019 Humboldt-Universität zu Berlin <moodle-support@cms.hu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import log from 'core/log';

export const init = () => {
    $(window).on('resize', resizeIframe);
    resizeIframe();

    /**
     * Callback for 'resize' and changes the size of the iframe.
     */
    function resizeIframe() {
        var inlineContainer = document.querySelector('.collabora-inline-container');
        if (inlineContainer === null || inlineContainer == undefined) {
            return;
        }

        var currentTop = inlineContainer.getBoundingClientRect().top;
        var myHeight = parseInt(window.innerHeight - currentTop);
        myHeight = myHeight - 5; // Decrease the height a little.
        if (myHeight < 300) { // Don't shrink below 300!
            myHeight = 300;
        }
        log.debug('change iframe height to: ' + myHeight);
        inlineContainer.style.height = myHeight + 'px';
    }
};
