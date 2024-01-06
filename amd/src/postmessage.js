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
import ajax from 'core/ajax';
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
    var strBack;
    var imgBackUrl;
    var uiMode;
    var newVersion;
    var versionManager;

    courseURL = opts.courseurl;
    collaboraUrl = opts.collaboraurl;
    destinationOrigin = opts.destinationorigin;
    asPopup = opts.aspopup;
    iframeid = opts.iframeid;
    id = opts.id;
    contextid = opts.contextid;
    strBack = opts.strback;
    imgBackUrl = opts.imgbackurl;
    uiMode = opts.uimode;
    versionManager = opts.versionmanager;

    // The version is "0" by default.
    version = 0;
    newVersion = 0;
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
            // Messages sent by collabora editor.
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
            case 'Clicked_Button':
                handleCustomButton(msg);
                break;
            case 'App_VersionRestore':
                restoreVersion(msg);
                break;

            // Messages sent by version viewer.
            case 'SET_VERSION':
                version = msg.Values.version;
                setFrameData(collaboraUrl);
                showVersionView();
                break;
            case 'RESTORE_VERSION':
                prepareRestoreVersion(msg.Values.version);
                break;
        }
    }

    /**
     * Close the document or go back to the course page, depending on the current view.
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
            'id': id,
            'version': version
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

                // If newVersion is set, we have to tell the editor we want to restore to it.
                if (newVersion != 0) {
                    var postObject = {
                        'MessageId': 'Host_VersionRestore',
                        'Values': {
                        'Status': 'Pre_Restore'
                        }
                    };
                    postMessage(postObject);
                    return;
                }

                // Disable the default save command to activate our own.
                var postObject = {
                    'MessageId': 'Disable_Default_UIAction',
                    'Values': {
                        'action': 'UI_Save',
                        'disable': true
                    }
                };
                postMessage(postObject);

                // Disable the default save command to activate our own.
                var postObject = {
                    'MessageId': 'Action_ChangeUIMode',
                    'Values': {
                        'Mode': uiMode
                    }
                };
                postMessage(postObject);

                if (version > 0) {
                    addBackButton();
                }
            }
        }
    }

    function handleCustomButton(msg) {
        if (msg.Values) {
            if (msg.Values.Id == 'moodle_go_back') {
                version = 0;
                setFrameData(collaboraUrl);
            }
        }

    }

    function addBackButton() {
        var postObject = {
            'MessageId': 'Insert_Button',
            'Values': {
                'id': 'moodle_go_back',
                'imgurl': imgBackUrl,
                'label': strBack,
                'hint': strBack
            }
        };
        postMessage(postObject);
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
                document.body.appendChild(form);

                form.submit();
                // form.remove();
            }
        ).fail(notification.exception);

        return;
    }

    function prepareRestoreVersion(theNewVersion) {
        version = 0; // Set version to the current document, so we can tell other users using this document.
        newVersion = theNewVersion;
        setFrameData(collaboraUrl);
    }

    function restoreVersion(msg) {
        if (!versionManager) {
            return;
        }
        if (msg.Values.Status == 'Pre_Restore_Ack') {
            // Now we call the webservice with "ajax.call" and get an array of promises.
            // Because we call only one service we only get one promise inside this array.
            var myPromises = ajax.call([{ // Note: there is a Square bracket!
                // The parameter methodname is the webservice we want to call.
                methodname: 'mod_collabora_restore_version',
                // The second one is a json object with all in the webservice defined parameters.
                // The submitaction is needed to set the right action url in the mform.
                args:{ id: id, version: newVersion }
            }]);

            // We only have one promise because we call only one webservice. More would be possible.
            // So we just use promises[0].
            myPromises[0].done(function(data) {
                if (data.success == 1) {
                    version = 0;
                    newVersion = 0;
                    setFrameData(collaboraUrl);

                    showVersionView();
                } else {
                    notification.exception({message: data.failuremsg});
                }
            }).fail(notification.exception); // If any went wrong we let the user know.
        }

    }
};
