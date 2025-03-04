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
 * Language strings.
 *
 * @package   mod_collabora
 * @copyright 2019 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

$string['cachedef_discovery']                                = 'Collabora discovery XML file';
$string['collabora:addinstance']                             = 'Add collaborative document to a course';
$string['collabora:directdownload']                          = 'Directly download the document';
$string['collabora:editlocked']                              = 'Edit a locked collaborative document';
$string['collabora:lock']                                    = 'Lock/unlock a collaborative document';
$string['collabora:manageversions']                          = 'Manage versions of a collaborative document';
$string['collabora:repair']                                  = 'Repair a broken document';
$string['collabora:view']                                    = 'View a collaborative document';
$string['collaboraurl']                                      = 'Collabora URL';
$string['collaboraurlnotset']                                = 'Collabora URL is not configured for this site';
$string['couldnotdeleteversion']                             = 'Could not delete version';
$string['couldnotrestoreversion']                            = 'Could not restore version';
$string['current']                                           = 'Current tab';
$string['defaultdisplay']                                    = 'Default display';
$string['defaultdisplaydescription']                         = 'Default display description';
$string['defaultdisplayname']                                = 'Default display name';
$string['defaultformat']                                     = 'Default format';
$string['display']                                           = 'Display';
$string['display_help']                                      = 'When "New tab" is selected, the document is displayed in full size in a new tab.';
$string['displaydescription']                                = 'Display description';
$string['displayname']                                       = 'Display name';
$string['displayname_help']                                  = 'If enabled, the current file name is displayed above the document.';
$string['dnduploadcollabora']                                = 'Create a Collabora document';
$string['enableversions']                                    = 'Enable versioning';
$string['enableversions_help']                               = 'If enabled, a new version is created each time changes are saved. These versions can be downloaded and restored individually.';
$string['eventdocumentlocked']                               = 'Collaborative document locked';
$string['eventdocumentrepaired']                             = 'Collaborative document repaired';
$string['eventdocumentunlocked']                             = 'Collaborative document unlocked';
$string['format']                                            = 'Format';
$string['fullscreen']                                        = 'Fullscreen';
$string['height']                                            = 'Height (0 for automatic)';
$string['initialfile']                                       = 'Initial file';
$string['initialtext']                                       = 'Initial text';
$string['locked']                                            = 'Document locked by teacher';
$string['lockedunlock']                                      = 'Document currently locked, click here to unlock it and allow editing';
$string['modulename']                                        = 'Collaborative document';
$string['modulename_help']                                   = 'With this application you can connect to a Collabora Online Server to create a text, word, presentation or spreet sheet document or upload a document and work collaborative on this document.';
$string['modulenameplural']                                  = 'Collaborative documents';
$string['name']                                              = 'Name';
$string['new']                                               = 'New tab';
$string['nogroupaccess']                                     = 'This activity is only available to users who are members of a course group';
$string['pleasewait']                                        = 'Please wait...';
$string['pluginadministration']                              = 'Collaborative document settings';
$string['pluginname']                                        = 'Collaborative document';
$string['presentation']                                      = 'Presentation';
$string['privacy:metadata:collabora_extsystem']              = 'File infos and content are shared with Collabora to allow collaborative work';
$string['privacy:metadata:collabora_extsystem:filecontent']  = 'The content of the file';
$string['privacy:metadata:collabora_extsystem:lastmodified'] = 'The time when the file was last modified';
$string['privacy:metadata:collabora_extsystem:username']     = 'The Username';
$string['privacy:metadata:core_files']                       = 'mod_collabora stores the collaborative files.';
$string['repair']                                            = 'Repair';
$string['repair_failed']                                     = 'The document could not be repaired.';
$string['repair_succeeded']                                  = 'The document has been repaired.';
$string['repairdocument']                                    = 'Repair document "{$a}"';
$string['repairdocumentconfirm']                             = 'Do you really want to try repairing the current document?';
$string['repairdocumentconfirm_help']                        = 'Sometimes the Collabora Server won\'t load the last document due to a version conflict or a deadlocked process.<br>
Usually such a problem will resolve itself, but it may take a while and you will not be able to work with your document in the meantime.<br>
If you try to use this repair function, a new process will be started on the Collabora server and assigned to the last document saved in Moodle.<br>
<strong>Note: Make sure that the document is currently not used by other users. Otherwise they will get an error message if they try to save the document.</strong>';
$string['requiredfortext']                           = 'Required when the format is \'Specified text\'';
$string['requiredforupload']                         = 'Required when the format is \'File upload\'';
$string['restorewindowsize']                         = 'Restore window size';
$string['setting_allowcollaboraserverexplicit']      = 'Explicitly allow Collabora url';
$string['setting_allowcollaboraserverexplicit_help'] = '<strong>Note:</strong> This setting can be a security risk. You only should activate it if your Collabora server is running in a private net or on the same host as Moodle.<br>
If your Collabora server is accessed by one of the in <strong>$CFG->curlsecurityblockedhosts</strong> defined hosts you have to enable this setting or remove the host from the blocked list.';
$string['setting_connection']               = 'Connection';
$string['setting_header_security']          = 'Security';
$string['setting_share_userimages']         = 'Share user images with Collabora users.';
$string['setting_share_userimages_help']    = 'Users who are working together on a document can see the user images in the Collabora editor. If you do not want this, deactivate this setting.';
$string['setting_showlegacytemplates']      = 'Show legacy templates';
$string['setting_showlegacytemplates_help'] = 'When a new document is created, the legacy templates can also be used in addition to the templates defined here.';
$string['setting_showserveraudit']          = 'Show server audit';
$string['setting_showserveraudit_help']     = 'If set, the siteadmin user has an additional menu item "Server audit" in the collaobra editor menu, which show some information about the collabora server';
$string['setting_templates']                = 'Templates';
$string['spreadsheet']                      = 'Spreadsheet';
$string['task_cleanup']                     = 'Cleanup';
$string['template_fixed_text']              = 'Simple text file with predefined content.';
$string['template_fixed_upload']            = 'Upload your own template';
$string['template_legacy_presentation']     = 'Legacy presentation template';
$string['template_legacy_spreadsheet']      = 'Legacy spreadsheet template';
$string['template_legacy_wordprocessor']    = 'Legacy wordprocessor template';
$string['templates']                        = 'Templates';
$string['templates_dynamic']                = 'Dynamic templates';
$string['templates_legacy']                 = 'Legacy templates';
$string['text']                             = 'Specified text';
$string['uicompact']                        = 'Compact interface';
$string['uimode']                           = 'Interface mode';
$string['uiserver']                         = 'Default Interface from server';
$string['uitabbed']                         = 'Tabbed interface';
$string['unlockedlock']                     = 'Document currently unlocked, click here to lock it and prevent editing';
$string['unsupportedtype']                  = 'Unsupported filetype {$a}';
$string['upload']                           = 'File upload';
$string['wordprocessor']                    = 'Wordprocessor document';
