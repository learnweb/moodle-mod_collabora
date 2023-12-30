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
    var collaboraUrl;
    var courseURL;
    var asPopup;
    var iframe;

    /**
     * Listener function for the event 'message'
     * @param {Event} event
     */
    function receiveMessage(event) {
        var msg, msgId;

        console.log('ReceiveMessage: ' + event.data);

        msg = JSON.parse(event.data);
        if (!msg) {
            return;
        }

        msgId = msg.MessageId;

        switch (msgId) {
            case 'UI_Close':
                closeDoc();
                break;
            case 'App_LoadingStatus':
                tellPostmessageReady(msg);
                break;
        }
    }

    function closeDoc() {
        if (asPopup) {
            window.close();
        } else {
            window.location.href = courseURL;
        }
    }

    function tellPostmessageReady(msg) {
        if (msg.Values) {
            if (msg.Values.Status == 'Document_Loaded') {
                // Send the Host_PostMessageReady  before other posts (Mandatory)
                var postObject = {
                    'MessageId': 'Host_PostmessageReady',
                    'SendTime': Date.now()
                };
                postMessage(postObject);
            }
        }
    }

    function postMessage(postObject) {
        var message = JSON.stringify(postObject);
        console.log('Post message to collabora: ' + message);
        iframe.postMessage(message, collaboraUrl);
    }

    return {
        init: function(opts) {
            collaboraUrl = opts.collaboraurl;
            courseURL = opts.courseurl;
            asPopup = opts.aspopup;
            iframe = document.getElementById(opts.iframeid);
            iframe = iframe.contentWindow || (iframe.contentDocument.document || iframe.contentDocument);
            window.addEventListener('message', receiveMessage);
        }
    };
});