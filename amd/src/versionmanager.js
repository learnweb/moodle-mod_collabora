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

import log from 'core/log';

export const init = (id) => {
    const table = document.querySelector("#version_viewer_table-" + id);
    table.addEventListener("click", (event) => {
        const target = event.target;
        const version = target.dataset.version;
        if (target.classList.contains("collabora-preview-button")) {
            event.preventDefault();
            event.stopPropagation();
            postMessage({
                'MessageId': 'SET_VERSION',
                'Values': {
                    'version': version
                }
            });
        }
        if (target.classList.contains("collabora-restore-button")) {
            event.preventDefault();
            event.stopPropagation();
            postMessage({
                'MessageId': 'RESTORE_VERSION',
                'Values': {
                    'version': version
                }
            });
        }
        if (target.classList.contains("collabora-deleteversion-button")) {
            event.preventDefault();
            event.stopPropagation();
            postMessage({
                'MessageId': 'DELETE_VERSION',
                'Values': {
                    'version': version
                }
            });
        }
    });

    /**
     * Send a message to the collabora editor
     * @param {object} postObject The object we want to post.
     */
    function postMessage(postObject) {
        postObject.SendTime = Date.now();
        var message = JSON.stringify(postObject);
        log.debug('Post message to myself: ' + message);

        window.postMessage(message, '*');
    }

};
