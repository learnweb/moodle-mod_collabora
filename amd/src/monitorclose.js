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
 * Closes the collabora window.
 *
 * @module     mod_collabora/monitorclose
 * @author     Andreas Grabs <moodle@grabs-edv.de>
 * @copyright  2019 Humboldt-Universität zu Berlin <moodle-support@cms.hu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    var courseURL;
    var closeWindow;

    /**
     * Listener function for the event 'message'
     * @param {Event} e
     */
    function checkMessage(e) {
        var msg, msgId;
        try {
            msg = JSON.parse(e.data);
            msgId = msg.MessageId;
        } catch (exc) {
            msgId = e.data;
        }
        if (msgId === 'UI_Close') {
            if (closeWindow) {
                window.close();
            } else {
                window.location.href = courseURL;
            }
        }
    }

    return {
        init: function(opts) {
            courseURL = opts.courseurl;
            closeWindow = opts.closewindow;
            window.addEventListener('message', checkMessage);
        }
    };
});
