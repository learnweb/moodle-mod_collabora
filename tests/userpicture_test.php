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

namespace mod_collabora;

/**
 * Test for serving the pictures of users working on the same document.
 *
 * @package   mod_collabora
 * @copyright 2019 Jan Dageförde, WWU Münster
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class userpicture_test extends \advanced_testcase {
    /** @var \stdClass */
    private $course;

    /**
     * Setup function to create a course we can test with.
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);

        $this->setAdminUser(); // This makes the user with id 2 to the current user.
        $this->set_user_picture(2); // Set the picture for user with id 2.
        $this->setAdminUser(); // This makes the user with id 2 to the current user.
        $this->course = $this->getDataGenerator()->create_course();
    }

    /**
     * Test XYZ
     *
     * @covers \mod_collabora\api\collabora_fs
     * @return void
     */
    public function test_userpicture(): void {
        global $USER;

        $collabora = $this->getDataGenerator()->create_module('collabora',
            [
                'course'      => $this->course,
                'format'      => 'text',
                'initialtext' => 'Test text',
            ]
        );
        // Get the collabora_fs instance.
        $collaborafs = new \mod_collabora\api\collabora_fs($collabora, \context_module::instance($collabora->cmid), 0, $USER->id);

        // Get the userpicture url.
        $userpictureurl = $collaborafs->get_userpicture_url();
        $this->assertStringContainsString('userpic.php', $userpictureurl);

        // Get the params from the userpicture url.
        $params = $this->get_params_from_url($userpictureurl);
        // Validate the doctoken. It should be false because there is no user token at this time.
        $result = \mod_collabora\api\collabora_fs::validate_doctoken($params->doctoken, $params->userid);
        // The result should be false.
        $this->assertFalse($result);

        // Create a user token to simulate starting a edit session.
        $token = $collaborafs->get_user_token();
        // There should be a string as token.
        $this->assertNotEmpty($token);

        // Valdidate the token again. This time it should be true because there is a user token.
        $result = \mod_collabora\api\collabora_fs::validate_doctoken($params->doctoken, $params->userid);
        $this->assertTrue($result);
    }

    /**
     * Helper function to extract and parse query parameters from a given URL.
     *
     * This function takes a URL, parses it to extract the query string,
     * and then converts the query parameters into an object where each
     * parameter is a property.
     *
     * @param string $url The URL to parse and extract parameters from.
     * @return \stdClass An object containing the parsed query parameters,
     *                   where each parameter name is a property and its value
     *                   is the property value.
     */
    private function get_params_from_url(string $url) {
        $urlparts = parse_url($url);
        $queryparts = explode('&', $urlparts['query']);

        $params = new \stdClass();
        foreach ($queryparts as $querypart) {
            list($key, $value) = explode('=', $querypart);
            $params->{$key} = $value;
        }
        return $params;
    }


    /**
     * Helper function to set a user's profile picture using a predefined image.
     *
     * This function creates a new profile picture for a user by using a base64 encoded
     * red dot image. It handles the file creation, processing, and database update.
     *
     * @param int $userid The ID of the user for whom to set the profile picture.
     * @return void
     * @throws \file_exception If there's an error in file operations.
     * @throws \dml_exception If there's an error in database operations.
     */
    private function set_user_picture($userid) {
        global $DB, $CFG;

        require_once("$CFG->libdir/gdlib.php");

        $context = \context_user::instance($userid);
        $contextid = $context->id;
        $filename = "reddot.png";
        $filecontent = "iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38"
            . "GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg==";

        $fs = get_file_storage();
        // Get a free draftitemid.
        $draftitemid = file_get_unused_draft_itemid();

        $filerec = (object) [
            'contextid' => $contextid,
            'userid'    => $userid,
            'component' => 'user',
            'filearea'  => 'draft',
            'itemid'    => $draftitemid,
            'filepath'  => '/',
            'filename'  => $filename,
        ];

        $file = $fs->create_file_from_string(
            $filerec,
            base64_decode($filecontent)
        );

        $iconfile = $file->copy_content_to_temp();
        $newpicture = (int) process_new_icon($context, 'user', 'icon', 0, $iconfile);
        @unlink($iconfile);
        // Remove uploaded file.
        $fs->delete_area_files($context->id, 'user', 'draft', $draftitemid);

        $DB->set_field('user', 'picture', $newpicture, ['id' => $userid]);
    }
}
