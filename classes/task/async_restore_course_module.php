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

namespace block_sharing_cart\task;


use block_sharing_cart\controller;
use block_sharing_cart\restore_task_options;
use core\task\adhoc_task;
use core\task\manager;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class async_restore_course_module extends adhoc_task
{
    public function execute()
    {
        $options = restore_task_options::create_by_json($this->get_custom_data_as_string());
        $controller = new controller();
        $controller->restore(
            $options->get_sharing_cart_id(),
            $options->get_course_id(),
            $options->get_section_number(),
            $options->get_user_id()
        );
    }

    public static function create_from_restore(
        int $sharing_cart_id,
        int $course_id,
        int $section_number,
        ?int $user_id = null
    ): self
    {
        global $USER;
        $user_id ??= $USER->id;

        $task = new self();
        $task->set_custom_data([
            'sharing_cart_id' => $sharing_cart_id,
            'course_id' => $course_id,
            'section_number' => $section_number,
            'user_id' => $user_id,
        ]);
        return $task;
    }

    public static function add_to_queue(
        int $sharing_cart_id,
        int $course_id,
        int $section_number,
        ?int $user_id = null
    ): self
    {
        global $USER;
        $user_id ??= $USER->id;

        $task = self::create_from_restore(
            $sharing_cart_id,
            $course_id,
            $section_number,
            $user_id
        );
        $id = manager::queue_adhoc_task($task);
        $task->set_id($id);
        return $task;
    }
}
