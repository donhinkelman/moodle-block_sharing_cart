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

namespace block_sharing_cart;


use core\event\user_deleted;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class observers
{
    public static function user_deleted(user_deleted $event): void
    {
        self::delete_records_by_user_id($event->objectid);
    }

    /**
     * Delete all sharing cart records by user id.
     * Ensure that all sharing cart records are deleted when a user is deleted from moodle,
     * instead of relying on the privacy provider.
     * @param int $user_id
     * @return void
     * @throws \dml_exception
     */
    private static function delete_records_by_user_id(int $user_id): void
    {
        global $DB;
        $DB->delete_records('block_sharing_cart', ['userid' => $user_id]);
    }
}
