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

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../../moodle2/restore_root_task_fix_missings.php';

/**
 *  The helper class that fixes restore plan
 */
final class restore_fix_missings_helper {
    /**
     *  Fixes a restore plan to perform a workaround for question bank restore issue
     *
     * @param restore_plan $plan
     */
    public static function fix_plan(restore_plan $plan) {
        // replaces an existing restore_root_task with a restore_root_task_fix_missings
        $tasks = $plan->get_tasks();
        foreach ($tasks as $i => $task) {
            if ($task instanceof restore_root_task) {
                $task = new restore_root_task_fix_missings('root_task');
                // since the task settings already defined by restore_root_task,
                // we need to inject the plan instead of calling set_plan(),
                // to avoid 'error/multiple_settings_by_name_found' error
                self::set_protected_property($task, 'plan', $plan);
                $tasks[$i] = $task;
                break;
            }
        }
        self::set_protected_property($plan, 'tasks', $tasks);
    }

    /**
     *  Sets a protected/private property
     *
     * @param object $obj
     * @param string $prop
     * @param mixed $value
     */
    private static function set_protected_property($obj, $prop, $value) {
        $reflector = new ReflectionProperty(get_class($obj), $prop);
        $reflector->setAccessible(true);
        $reflector->setValue($obj, $value);
    }
}
