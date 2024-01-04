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

import $ from 'jquery';
import fragment from 'core/fragment';
import templates from 'core/templates';
import notification from 'core/notification';
import log from 'core/log';

export const init = (opts) => {
    var courseURL;
    var collaboraUrl;
    var destinationOrigin;
    var asPopup;
    var iframeid;
    var versionviewer;
    var id;
    var contextid;
    var version;

    courseURL = opts.courseurl;
    collaboraUrl = opts.collaboraurl;
    destinationOrigin = opts.destinationorigin;
    asPopup = opts.aspopup;
    iframeid = opts.iframeid;
    id = opts.id;
    contextid = opts.contextid;
    // version = 1704188035;
    version = 0;
    setFrameData(collaboraUrl);

    initModal();
    versionviewer = document.getElementById(opts.versionviewerid);
    window.addEventListener('message', receiveMessage);

    /**
     * Listener function for the event 'message'
     * @param {Event} event
     */
    function receiveMessage(event) {
        var msg, msgId;

        log.debug('ReceiveMessage from ' + event.origin + ': ' + event.data);
        if (typeof event.data !== 'string' && !(event.data instanceof String)) {
            return;
        }

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
            case 'UI_FileVersions':
                showVersionView();
                break;
            case 'UI_Save':
                invokeSave();
                break;
            case 'SET_VERSION':
                version = msg.Values.version;
                setFrameData(collaboraUrl);
        }
    }

    /**
     * Clode the document or go back to the course page, depending on the current view.
     */
    function closeDoc() {
        if (asPopup) {
            window.close();
        } else {
            window.location.href = courseURL;
        }
    }

    /**
     * Show the version_view page with all current versions.
     */
    function showVersionView() {
        var serviceparams = {
            'function' : 'version_viewer_content',
            'id': id
        };
        fragment.loadFragment('mod_collabora', 'get_html', contextid, serviceparams).then(
            function(html, js) {
                $('#' + versionviewer.id).collapse('show');
                let contentcontainer = document.querySelector('#' + versionviewer.id + ' .card-body');
                contentcontainer.innerHTML = html;
                if (js) {
                    templates.runTemplateJS(js);
                }
            }
        ).fail(notification.exception);

    }

    /**
     * Invoke the save command.
     * This is different to the default save action because we can prevent saving of unmodified documents
     * which prevents creating new versions of the same document.
     */
    function invokeSave() {
        var postObject = {
            'MessageId': 'Action_Save',
            'Values': {
                'DontTerminateEdit': true,
                'DontSaveIfUnmodified': true
            }
        };
        postMessage(postObject);
    }

    /**
     * Post the first message to collabora when the document is ready.
     * @param {object} msg The received message object from the collabora editor.
     */
    function tellPostmessageReady(msg) {
        if (msg.Values) {
            if (msg.Values.Status == 'Document_Loaded') {

                // Send the Host_PostMessageReady  before other posts (Mandatory)
                var postObject = {
                    'MessageId': 'Host_PostmessageReady'
                };
                postMessage(postObject);

                // Disable the default save command to activate our own.
                var postObject = {
                    'MessageId': 'Disable_Default_UIAction',
                    'Values': {
                        'action': 'UI_Save',
                        'disable': true
                    }
                };
                postMessage(postObject);
            }
        }
    }

    /**
     * Send a message to the collabora editor
     * @param {object} postObject The object we want to post.
     */
    function postMessage(postObject) {
        postObject.SendTime = Date.now();
        var message = JSON.stringify(postObject);
        log.debug('Post message to collabora: ' + message);

        var iframe = document.getElementById(iframeid);
        iframe = iframe.contentWindow || (iframe.contentDocument.document || iframe.contentDocument);

        iframe.postMessage(message, destinationOrigin);
    }

    function initModal() {
        // Get the elements between which the iframe is moved back and forth.
        const inlineelement = document.querySelector('#collabora-inline_' + id);
        const modalelement = document.querySelector('#collaboramodal-body_' + id);
        // Get the iframe container we move to one of the above defined elements.
        const iframecontainer = document.querySelector('#iframe-container_' + id);

        // Move the iframe to the modal element.
        $("#collaboramodal_" + id).on("show.bs.modal", function() {
            modalelement.append(iframecontainer);
            $("body").addClass("modal-open");

            setFrameData(collaboraUrl);
        });

        // Move the iframe to the inline element.
        $("#collaboramodal_" + id).on("hide.bs.modal", function() {
            inlineelement.append(iframecontainer);
            $("body").removeClass("modal-open");
            setFrameData(collaboraUrl);
        });

    }

    function setFrameData() {
        log.debug('Set iframe source: ' + collaboraUrl);

        // Load the wopi_src params.
        var serviceparams = {
            'function' : 'wopi_src',
            'id': id,
            'version': version
        };
        fragment.loadFragment('mod_collabora', 'get_html', contextid, serviceparams).then(
            function(strparams) {
                var params = JSON.parse(strparams);

                var form = document.createElement("form");
                form.method = "get";
                form.action = collaboraUrl;
                form.target = iframeid;
                for (const [key, value] of Object.entries(params)) {
                    var element = document.createElement("input");
                    element.type = "hidden";
                    element.name = key;
                    element.value = value;
                    form.appendChild(element);
                    log.debug('Add element ' + key + ': ' + value);
                }
                if (version > 0) {
                    var element = document.createElement("input");
                    element.type = "hidden";
                    element.name = 'permission';
                    element.value = 'readonly';
                    form.appendChild(element);
                    log.debug('Add readonly element');
                }
                document.body.appendChild(form);

                form.submit();
                // form.remove();
            }
        ).fail(notification.exception);

        return;
    }
};
