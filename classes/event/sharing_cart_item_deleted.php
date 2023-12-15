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

namespace block_sharing_cart\event;

/**
 * @method static self create(array $data)
 */
class sharing_cart_item_deleted extends base
{
    protected function get_crud(): string
    {
        return static::CRUD_DELETE;
    }

    public function get_description(): string
    {
        return "User with id {$this->userid} deleted a sharing cart item with id {$this->objectid}";
    }

    public static function create_by_sharing_cart_item_id(
        int $sharing_cart_item_id,
        int $course_id = 0
    ): self
    {
        $context = $course_id > 0 ? \context_course::instance($course_id) : \context_system::instance();
        return static::create([
            'objectid' => $sharing_cart_item_id,
            'context' => $context,
            'courseid' => $course_id,
        ]);
    }
}
