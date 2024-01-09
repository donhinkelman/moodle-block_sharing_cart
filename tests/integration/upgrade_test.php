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
 *  Sharing Cart
 *
 * @package    block_sharing_cart
 * @copyright  2017 (C) VERSION2, INC.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_sharing_cart\integration;

use advanced_testcase;
use block_sharing_cart\storage;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class upgrade_test extends advanced_testcase
{
    private function db(): \moodle_database
    {
        global $DB;
        return $DB;
    }

    protected function setUp(): void
    {
        $this->resetAfterTest();
    }

    public function test_upgrade_sharing_cart_index_and_add_file_id_to_records_and_trim_item_name_that_is_too_long(): void
    {
        global $CFG;
        require_once $CFG->libdir . '/upgradelib.php';
        require_once $CFG->dirroot . '/blocks/sharing_cart/db/upgrade.php';

        $user = self::getDataGenerator()->create_user();
        $course = self::getDataGenerator()->create_course();

        $context = \context_user::instance($user->id);

        $storage = get_file_storage();
        $file = $storage->create_file_from_string((object)[
            'contextid' => $context->id,
            'component' => storage::COMPONENT,
            'filearea' => storage::FILEAREA,
            'itemid' => storage::ITEMID,
            'filepath' => storage::FILEPATH,
            'filename' => 'test.mbz',
            'content' => 'test',
            'userid' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ], 'Test');

        $lorem = self::getDataGenerator()->loremipsum;
        $expected_mod_text = trim(substr($lorem, 0, 100));

        $instance_no_file_id = (object)[
            'id' => 1,
            'userid' => $user->id,
            'modname' => 'label',
            'modicon' => '',
            'modtext' => $lorem,
            'ctime' => time(),
            'filename' => $file->get_filename(),
            'tree' => '',
            'weight' => 1,
            'course' => $course->id,
            'section' => 0,
            'fileid' => 0,
        ];

        $instance_no_file_id->id = (int)$this->db()->insert_record('block_sharing_cart', $instance_no_file_id);

        $downgrade_version = 2024010300 - 1;
        $current_version = $this->db()->get_field(
            'config_plugins',
            'value',
            ['plugin' => 'block_sharing_cart', 'name' => 'version']
        );
        $this->db()->set_field(
            'config_plugins',
            'value',
            $downgrade_version,
            ['plugin' => 'block_sharing_cart', 'name' => 'version']
        );

        xmldb_block_sharing_cart_upgrade(2024010300 - 1);

        $actual_record = $this->db()->get_record('block_sharing_cart', ['id' => $instance_no_file_id->id]);
        self::assertEquals(
            $file->get_id(),
            $actual_record->fileid
        );
        self::assertEquals(
            $expected_mod_text,
            $actual_record->modtext
        );

        $this->db()->set_field(
            'config_plugins',
            'value',
            $current_version,
            ['plugin' => 'block_sharing_cart', 'name' => 'version']
        );
    }
}
