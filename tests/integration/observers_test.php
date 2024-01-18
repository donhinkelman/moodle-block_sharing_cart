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
use core\event\user_deleted;
use moodle_database;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class observers_test extends advanced_testcase
{
    protected function setUp(): void
    {
        $this->resetAfterTest();
    }

    public function test_deleted_user_expect_sharing_cart_record_to_be_remove(): void
    {
        $user = self::getDataGenerator()->create_user();
        $this->create_sharing_cart_record($user->id);

        self::assertTrue(
            $this->has_sharing_cart_item(['userid' => $user->id])
        );

        delete_user($user);

        self::assertFalse(
            $this->has_sharing_cart_item(['userid' => $user->id])
        );
    }

    public function test_emit_user_deleted_event_expect_sharing_cart_record_to_be_remove(): void
    {
        $user = self::getDataGenerator()->create_user();

        $this->create_sharing_cart_record($user->id);
        self::assertTrue(
            $this->has_sharing_cart_item(['userid' => $user->id])
        );

        $context = \context_user::instance($user->id);
        $event = user_deleted::create([
            'objectid' => $user->id,
            'relateduserid' => $user->id,
            'context' => $context,
            'other' => [
                'username' => $user->username,
                'email' => $user->email,
                'idnumber' => $user->idnumber,
                'picture' => $user->picture,
                'mnethostid' => $user->mnethostid
            ]
        ]);
        $event->trigger();

        self::assertFalse(
            $this->has_sharing_cart_item(['userid' => $user->id])
        );
    }

    private function db(): moodle_database
    {
        global $DB;
        return $DB;
    }

    private function has_sharing_cart_item(array $condition): bool
    {
        return $this->db()->record_exists('block_sharing_cart', $condition);
    }

    private function create_sharing_cart_record(int $user_id, array $record = []): object
    {
        $record['userid'] = $user_id;
        $record['modname'] ??= 'label';
        $record['modicon'] ??= '';
        $record['modtext'] ??= 'test';
        $record['ctime'] ??= time();
        $record['filename'] ??= 'test.mbz';
        $record['courseid'] ??= 1;
        $record['fileid'] ??= 0;

        $instance = (object)$record;

        $instance->id = $this->db()->insert_record('block_sharing_cart', $instance);
        return $instance;
    }
}
