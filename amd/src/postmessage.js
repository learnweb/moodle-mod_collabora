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

class PostMessageHandler {
    asPopup;
    collaboraUrl;
    component;
    contextid;
    courseURL;
    id;
    iframeid;
    imgBackUrl;
    isSaving;
    newVersion;
    originCollabora;
    originMoodle;
    strBack;
    uiMode;
    version;
    useVersions;
    versionManager;
    versionViewer;
    spinner;

    constructor(opts) {
        this.courseURL = opts.courseurl;
        this.collaboraUrl = opts.collaboraurl;
        if ('component' in opts) {
            this.component = opts.component;
        } else {
            this.component = 'mod_collabora';
        }
        this.originCollabora = opts.origincollabora;
        this.originMoodle = opts.originmoodle;
        this.asPopup = opts.aspopup;
        this.iframeid = opts.iframeid;
        this.id = opts.id;
        this.contextid = opts.contextid;
        this.strBack = opts.strback;
        this.imgBackUrl = opts.imgbackurl;
        this.uiMode = opts.uimode;
        if (opts.useversions == undefined) {
            this.useVersions = false;
        } else {
            this.useVersions = opts.useversions;
        }
        this.versionManager = opts.versionmanager;
        this.isSaving = false;

        // The version is "0" by default.
        this.version = 0;
        this.newVersion = 0;

        this.versionViewer = document.querySelector('#' + opts.versionviewerid);
        this.spinner = document.querySelector('#collabora-spinner-' + this.id);
    }

    init() {
        const _this = this;

        _this.setFrameData();
        _this.initModal();
        window.addEventListener('message', function(e) {
            _this.receiveMessage(e);
        });
    }

    /**
     * Listener for the event 'message'
     * @param {Event} event
     */
    receiveMessage(event) {
        var msg, msgId;
        const _this = this;

        log.debug('ReceiveMessage from ' + event.origin + ': ' + event.data);
        // We only handle messages from Moodle or Collabora!
        if (event.origin != _this.originCollabora && event.origin != _this.originMoodle) {
            log.debug('!!!!Received Message from wrong origin "' + event.origin + '"');
            return;
        }
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
                _this.closeDocument();
                break;
            case 'App_LoadingStatus':
                _this.tellPostmessageReady(msg);
                break;
            case 'UI_FileVersions':
                _this.showVersionView();
                break;
            case 'UI_Save':
                _this.invokeSave();
                break;
            case 'Doc_ModifiedStatus':
                _this.checkSaved(msg);
                break;
            case 'Clicked_Button':
                _this.handleCustomButton(msg);
                break;
            case 'App_VersionRestore':
                _this.restoreVersion(msg);
                break;

            // Messages sent by version viewer.
            case 'SET_VERSION':
                _this.version = msg.Values.version;
                _this.setFrameData();
                _this.showVersionView();
                break;
            case 'RESTORE_VERSION':
                _this.prepareRestoreVersion(msg.Values.version);
                break;
            case 'DELETE_VERSION':
                _this.deleteVersion(msg.Values.version);
                break;
        }
    }

    /**
     * Close the document or go back to the course page, depending on the current view.
     */
    closeDocument() {
        const _this = this;

        if (_this.asPopup) {
            window.close();
        } else {
            window.location.href = _this.courseURL;
        }
    }

    /**
     * Show or hide an overlay spinner.
     *
     * @param {boolean} show
     */
    showSpinner(show) {
        const _this = this;

        var iframe = document.querySelector('#' + _this.iframeid);
        if (show) {
            _this.spinner.classList.remove('d-none');
            iframe.style.opacity = 0.1;

        } else {
            _this.spinner.classList.add('d-none');
            iframe.style.opacity = 1;
        }
    }

    /**
     * Show the version_view page with all current versions.
     */
    showVersionView() {
        if (!this.useVersions) {
            return;
        }
        const _this = this;
        _this.loadVersionView();
        $('#' + _this.versionViewer.id).collapse('show');
    }

    /**
     * Load the content for the version viewer.
     */
    loadVersionView() {
        const _this = this;

        if (!_this.versionManager) {
            return;
        }

        const serviceparams = {
            'function': 'version_viewer_content',
            'id': _this.id,
            'version': _this.version
        };

        fragment.loadFragment(_this.component, 'get_html', _this.contextid, serviceparams).then(
            function(html, js) {
                let contentcontainer = document.querySelector('#' + _this.versionViewer.id + ' .card-body');
                contentcontainer.innerHTML = html;
                if (js) {
                    templates.runTemplateJS(js);
                }
                return;
            }
        ).fail(notification.exception);
    }

    /**
     * Invoke the save command.
     * This is different to the default save action because we can prevent saving of unmodified documents
     * which prevents creating new versions of the same document.
     */
    invokeSave() {
        const _this = this;

        _this.isSaving = true;

        const postObject = {
            'MessageId': 'Action_Save',
            'Values': {
                'DontTerminateEdit': true,
                'DontSaveIfUnmodified': true
            }
        };
        _this.postMessage(postObject);
    }

    /**
     * Check saving has finished and reload version view if open.
     * @param {object} msg The received message object from the collabora editor.
     */
    checkSaved(msg) {
        const _this = this;

        _this.isSaving = true;

        if (msg.Values.Modified == false) {
            if (_this.isSaving) {
                if (!_this.useVersions) {
                    return;
                }
                _this.loadVersionView();
            }
        }
    }

    /**
     * Post the first message to collabora when the document is ready.
     * @param {object} msg The received message object from the collabora editor.
     */
    tellPostmessageReady(msg) {
        const _this = this;

        if (msg.Values) {
            if (msg.Values.Status == 'Document_Loaded') {
                var postObject;
                // Send the Host_PostMessageReady  before other posts (Mandatory)
                postObject = {
                    'MessageId': 'Host_PostmessageReady'
                };
                _this.postMessage(postObject);

                // If newVersion is set, we have to tell the editor we want to restore to it.
                if (_this.newVersion != 0) {
                    postObject = {
                        'MessageId': 'Host_VersionRestore',
                        'Values': {
                        'Status': 'Pre_Restore'
                        }
                    };
                    _this.postMessage(postObject);
                    return;
                }

                // Disable the default save command to activate our own.
                postObject = {
                    'MessageId': 'Disable_Default_UIAction',
                    'Values': {
                        'action': 'UI_Save',
                        'disable': true
                    }
                };
                _this.postMessage(postObject);

                // Set the ui mode (notebook or classic).
                if (_this.uiMode != 0) {
                    postObject = {
                        'MessageId': 'Action_ChangeUIMode',
                        'Values': {
                            'Mode': _this.uiMode
                        }
                    };
                    _this.postMessage(postObject);
                }
                // _this.spinner.classList.add('d-none');
                _this.showSpinner(false);
                if (_this.version > 0) {
                    _this.addBackButton();
                }
            }
        }
    }

    handleCustomButton(msg) {
        const _this = this;

        if (msg.Values) {
            if (msg.Values.Id == 'moodle_go_back') {
                _this.version = 0;
                _this.setFrameData();
            }
        }

    }

    addBackButton() {
        const _this = this;

        var postObject = {
            'MessageId': 'Insert_Button',
            'Values': {
                'id': 'moodle_go_back',
                'imgurl': _this.imgBackUrl,
                'label': _this.strBack,
                'hint': _this.strBack
            }
        };
        _this.postMessage(postObject);
    }

    /**
     * Send a message to the collabora editor
     * @param {object} postObject The object we want to post.
     */
    postMessage(postObject) {
        const _this = this;

        postObject.SendTime = Date.now();
        var message = JSON.stringify(postObject);
        log.debug('Post message to collabora: ' + message);

        var iframe = document.querySelector('#' + _this.iframeid);
        if (iframe === null || iframe == undefined) {
            log.debug('The iframe "' + _this.iframeid + '" has vanished');
            return;
        }
        iframe = iframe.contentWindow || (iframe.contentDocument.document || iframe.contentDocument);

        iframe.postMessage(message, _this.originCollabora);
    }

    /**
     * Prepare the modal feature to set the frameData while showing or hiding the modal.
     */
    initModal() {
        const _this = this;

        // Get the elements between which the iframe is moved back and forth.
        const inlineelement = document.querySelector('#collabora-inline_' + _this.id);
        const modalelement = document.querySelector('#collaboramodal-body_' + _this.id);
        // Get the iframe container we move to one of the above defined elements.
        const iframecontainer = document.querySelector('#iframe-container_' + _this.id);

        // Move the iframe to the modal element.
        $("#collaboramodal_" + _this.id).on("show.bs.modal", function() {
            modalelement.append(iframecontainer);
            $("body").addClass("modal-open");

            _this.setFrameData();
        });

        // Move the iframe to the inline element.
        $("#collaboramodal_" + _this.id).on("hide.bs.modal", function() {
            inlineelement.append(iframecontainer);
            $("body").removeClass("modal-open");
            _this.setFrameData();
        });

    }

    /**
     * Set the Frame content by posting a temporary form to the iframe.
     */
    setFrameData() {
        const _this = this;

        log.debug('Set iframe source: ' + _this.collaboraUrl);

        // Check whether there is an iframe we can post the form to.
        var iframe = document.querySelector('#' + _this.iframeid);
        if (iframe === null || iframe == undefined) {
            log.debug('The iframe "' + _this.iframeid + '" has vanished');
            return;
        }

        // Load the wopi_src params.
        var serviceparams = {
            'function': 'wopi_src',
            'id': _this.id,
            'version': _this.version
        };
        fragment.loadFragment(_this.component, 'get_html', _this.contextid, serviceparams).then(
            function(strparams) {
                var params = JSON.parse(strparams);

                var form = document.createElement("form");
                form.method = "get";
                form.action = _this.collaboraUrl;
                form.target = _this.iframeid;
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
                return;
            }
        ).fail(notification.exception);

        return;
    }

    prepareRestoreVersion(version) {
        const _this = this;

        // _this.spinner.classList.remove('d-none');
        _this.showSpinner(true);

        _this.version = 0; // Set version to the current document, so we can tell other users using this document.
        _this.newVersion = version;
        _this.setFrameData();
    }

    restoreVersion(msg) {
        const _this = this;

        if (!_this.versionManager) {
            _this.showSpinner(true);
            return;
        }
        if (msg.Values.Status == 'Pre_Restore_Ack') {
            // Now we call the webservice with "ajax.call" and get an array of promises.
            // Because we call only one service we only get one promise inside this array.
            var myPromises = ajax.call([{ // Note: there is a Square bracket!
                // The parameter methodname is the webservice we want to call.
                methodname: _this.component + '_restore_version',
                // The second one is a json object with all in the webservice defined parameters.
                // The submitaction is needed to set the right action url in the mform.
                args: {id: _this.id, version: _this.newVersion}
            }]);

            // We only have one promise because we call only one webservice. More would be possible.
            // So we just use promises[0].
            myPromises[0].done(function(data) {
                if (data.success == 1) {
                    _this.version = 0;
                    _this.newVersion = 0;
                    _this.setFrameData();

                    _this.showVersionView();

                } else {
                    notification.exception({message: data.failuremsg});
                }
            }).fail(notification.exception); // If any went wrong we let the user know.
        }
    }

    deleteVersion(version) {
        const _this = this;

        if (!_this.versionManager) {
            return;
        }

        // Now we call the webservice with "ajax.call" and get an array of promises.
        // Because we call only one service we only get one promise inside this array.
        var myPromises = ajax.call([{ // Note: there is a Square bracket!
            // The parameter methodname is the webservice we want to call.
            methodname: _this.component + '_delete_version',
            // The second one is a json object with all in the webservice defined parameters.
            // The submitaction is needed to set the right action url in the mform.
            args: {id: _this.id, version: version}
        }]);

        // We only have one promise because we call only one webservice. More would be possible.
        // So we just use promises[0].
        myPromises[0].done(function(data) {
            if (data.success == 1) {
                if (version == _this.version) {
                    _this.version = 0; // If the deleted version is in the editor, we set it to the default version.
                    _this.setFrameData();
                }
                _this.showVersionView();
            } else {
                notification.exception({message: data.failuremsg});
            }
        }).fail(notification.exception); // If any went wrong we let the user know.
    }
}

export const init = (opts) => {
    const pm = new PostMessageHandler(opts);
    pm.init();
};
