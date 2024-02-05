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
use context_user;
use file_storage;
use moodle_database;
use stored_file;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class upgrade_test extends advanced_testcase
{
    private file_storage $storage;

    private function db(): moodle_database
    {
        global $DB;
        return $DB;
    }

    protected function setUp(): void
    {
        $this->resetAfterTest();
        $this->storage = get_file_storage();
    }

    public function test_upgrade_sharing_cart_index_and_add_file_id_to_records_and_trim_item_name_that_is_too_long(): void
    {
        global $CFG;
        require_once $CFG->libdir . '/upgradelib.php';
        require_once $CFG->dirroot . '/blocks/sharing_cart/db/upgrade.php';

        $user = self::getDataGenerator()->create_user();
        $deleted_user = self::getDataGenerator()->create_user();
        $course = self::getDataGenerator()->create_course();

        $file1 = $this->create_backup_file([
            'userid' => $user->id
        ]);
        $file_deleted_user = $this->create_backup_file([
            'userid' => $deleted_user->id
        ]);

        $lorem = self::getDataGenerator()->loremipsum;
        $expected_mod_text = trim(substr($lorem, 0, 100));

        $instance_no_file_id = (object)[
            'id' => 1,
            'userid' => $user->id,
            'modname' => 'label',
            'modicon' => '',
            'modtext' => $lorem,
            'ctime' => time(),
            'filename' => $file1->get_filename(),
            'tree' => '',
            'weight' => 1,
            'course' => $course->id,
            'section' => 0,
            'fileid' => 0,
        ];

        $instance_deleted_user = (object)[
            'id' => 2,
            'userid' => $deleted_user->id,
            'modname' => 'label',
            'modicon' => '',
            'modtext' => $lorem,
            'ctime' => time(),
            'filename' => $file_deleted_user->get_filename(),
            'tree' => '',
            'weight' => 2,
            'course' => $course->id,
            'section' => 0,
            'fileid' => 0,
        ];

        $instance_no_file_id->id = (int)$this->db()->insert_record(
            'block_sharing_cart',
            $instance_no_file_id
        );
        $instance_deleted_user->id = (int)$this->db()->insert_record(
            'block_sharing_cart',
            $instance_deleted_user
        );

        $this->db()->set_field(
            'user',
            'deleted',
            1,
            ['id' => $deleted_user->id]
        );

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

        $records = $this->db()->get_records(
            'block_sharing_cart',
            ['userid' => $user->id],
        );
        self::assertCount(1, $records);
        $actual_record = reset($records);

        self::assertEquals(
            $file1->get_id(),
            $actual_record->fileid
        );
        self::assertEquals(
            $expected_mod_text,
            $actual_record->modtext
        );

        $deleted_user_records = $this->db()->get_records(
            'block_sharing_cart',
            ['userid' => $deleted_user->id],
        );

        self::assertCount(0, $deleted_user_records);

        $this->db()->set_field(
            'config_plugins',
            'value',
            $current_version,
            ['plugin' => 'block_sharing_cart', 'name' => 'version']
        );
    }

    private function create_backup_file(array $record = []): stored_file
    {
        global $USER;

        $record['userid'] ??= $USER->id;
        $record['contextid'] ??= context_user::instance($record['userid'])->id;
        $record['component'] ??= storage::COMPONENT;
        $record['filearea'] ??= storage::FILEAREA;
        $record['itemid'] ??= storage::ITEMID;
        $record['filepath'] ??= storage::FILEPATH;
        $record['filename'] ??= hash('md5', random_bytes(16)) . '.mbz';
        $record['timecreated'] ??= time();
        $record['timemodified'] ??= $record['timecreated'];

        $content = $record['content'] ?? random_bytes(16);
        $file = $this->storage->create_file_from_string((object)$record, $content);

        unset($record['content']);

        return $file;
    }
}
