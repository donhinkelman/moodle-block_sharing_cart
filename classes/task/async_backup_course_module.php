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


use block_sharing_cart\backup_task_options;
use block_sharing_cart\repositories\backup_options;
use block_sharing_cart\repositories\backup_repository;
use block_sharing_cart\repositories\course_module_repository;
use core\task\adhoc_task;
use core\task\manager;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class async_backup_course_module extends adhoc_task
{
    public function execute()
    {
        $options = backup_task_options::create_by_json($this->get_custom_data_as_string());
        $repo = new backup_repository(
            new course_module_repository()
        );
        $repo->backup(
            $options->get_user_id(),
            $options->get_cm_id(),
            $options->get_course_id(),
            $options->get_section_id(),
            $options->get_sharing_cart_record(),
            $options->get_backup_options()
        );
    }

    public static function create_by_course_module(
        int $cm_id,
        int $course_id,
        ?object $sharing_cart_record = null,
        ?backup_options $options = null
    ): self
    {
        global $USER;

        $task = new self();
        $task->set_custom_data([
            'cm_id' => $cm_id,
            'course_id' => $course_id,
            'section_id' => 0,
            'user_id' => $USER->id,
            'sharing_cart_record' => $sharing_cart_record,
            'settings' => $options ? $options->get_settings() : [],
        ]);
        return $task;
    }

    public static function add_to_queue_by_course_module(
        int $cm_id,
        int $course_id,
        ?object $sharing_cart_record = null,
        ?backup_options $options = null
    ): async_backup_course_module
    {
        $task = self::create_by_course_module(
            $cm_id,
            $course_id,
            $sharing_cart_record,
            $options
        );
        $id = manager::queue_adhoc_task($task);
        $task->set_id($id);
        return $task;
    }
}
