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
 * Privacy class for requesting user data.
 *
 * @package    mod_collabora
 * @copyright  2019 Justus Dieckmann, WWU; based on code by Benjamin Ellis, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_collabora\privacy;

defined('MOODLE_INTERNAL') || die;

global $CFG;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\metadata\provider as metadata_provider;
use core_privacy\local\request\plugin\provider as plugin_provider;

/**
 * Privacy class for requesting user data.
 *
 * @package    mod_collabora
 * @copyright  2019 Justus Dieckmann, WWU; based on code by Benjamin Ellis, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements metadata_provider, plugin_provider, core_userlist_provider {
    /**
     * Return meta data about this plugin.
     *
     * @param  collection $collection a list of information to add to
     * @return collection return the collection after adding to it
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_subsystem_link('core_files', [], 'privacy:metadata:core_files');
        $collection->add_external_location_link('collabora_extsystem', [
                'UserFriendlyName' => 'privacy:metadata:collabora_extsystem:username',
                'LastModifiedTime' => 'privacy:metadata:collabora_extsystem:lastmodified',
                'FileContent'      => 'privacy:metadata:collabora_extsystem:filecontent',
        ], 'privacy:metadata:collabora_extsystem');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param  int         $userid the user to search
     * @return contextlist $contextlist  the contextlist containing the list of contexts used in this plugin
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        // We don't delete files, because they were submitted as a group. To avoid confusion, we don't export them either.
        return new contextlist();
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist the approved contexts to export information for
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        // We don't delete files, because they were submitted as a group. To avoid confusion, we don't export them either.
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the specific context to delete data for
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        // We don't delete files, because they were submitted as a group. To avoid confusion, we don't export them either.
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist the approved contexts and user information to delete information for
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        // We don't delete files, because they were submitted as a group. To avoid confusion, we don't export them either.
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist the userlist containing the list of users who have data in this context/plugin combination
     */
    public static function get_users_in_context(userlist $userlist) {
        // We don't delete files, because they were submitted as a group. To avoid confusion, we don't export them either.
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist the approved context and user information to delete information for
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        // We don't delete files, because they were submitted as a group. To avoid confusion, we don't export them either.
    }
}
