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
 * Test ensuring that module icons depend on the content of the module.
 *
 * @package   mod_collabora
 * @copyright 2019 Jan Dageförde, WWU Münster
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class icons_test extends \advanced_testcase {
    /** @var \stdClass */
    private $course;

    /**
     * Setup function to create a course we can test with.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $this->course = $this->getDataGenerator()->create_course();
    }

    /**
     * Test for default icon.
     *
     * @covers \mod_collabora\api\collabora_fs::get_module_icon
     * @return void
     */
    public function test_default_module_icon(): void {
        global $USER;

        $collabora = $this->getDataGenerator()->create_module('collabora',
            [
                'course'      => $this->course,
                'format'      => 'text',
                'initialtext' => 'Test text',
            ]
        );
        $collaborafs = new \mod_collabora\api\collabora_fs($collabora, \context_module::instance($collabora->cmid), 0, $USER->id);

        $this->assertEquals('txt', $collaborafs->get_module_icon());
    }

    /**
     * Test for wordprocessor icon.
     *
     * @covers \mod_collabora\api\collabora_fs::get_module_icon
     * @return void
     */
    public function test_wordprocessor_module_icon(): void {
        global $USER;

        $collabora = $this->getDataGenerator()->create_module('collabora',
            [
                'course' => $this->course,
                'format' => 'wordprocessor',
            ]
        );
        $collaborafs = new \mod_collabora\api\collabora_fs($collabora, \context_module::instance($collabora->cmid), 0, $USER->id);

        $this->assertEquals('odt', $collaborafs->get_module_icon());
    }

    /**
     * Test for spreadsheet icon.
     *
     * @covers \mod_collabora\api\collabora_fs::get_module_icon
     * @return void
     */
    public function test_spreadsheet_module_icon(): void {
        global $USER;

        $collabora = $this->getDataGenerator()->create_module('collabora',
            [
                'course' => $this->course,
                'format' => 'spreadsheet',
            ]
        );
        $collaborafs = new \mod_collabora\api\collabora_fs($collabora, \context_module::instance($collabora->cmid), 0, $USER->id);

        $this->assertEquals('ods', $collaborafs->get_module_icon());
    }

    /**
     * Test for presentation icon.
     *
     * @covers \mod_collabora\api\collabora_fs::get_module_icon
     * @return void
     */
    public function test_presentation_module_icon(): void {
        global $USER;

        $collabora = $this->getDataGenerator()->create_module('collabora',
            [
                'course' => $this->course,
                'format' => 'presentation',
            ]
        );
        $collaborafs = new \mod_collabora\api\collabora_fs($collabora, \context_module::instance($collabora->cmid), 0, $USER->id);

        $this->assertEquals('odp', $collaborafs->get_module_icon());
    }
}
