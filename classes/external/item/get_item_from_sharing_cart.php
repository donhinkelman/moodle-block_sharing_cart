<?php

namespace block_sharing_cart\external\item;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use block_sharing_cart\app\factory;
use block_sharing_cart\app\item\entity;
use core_external\external_api;
use core_external\external_description;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

class get_item_from_sharing_cart extends external_api
{
    public static function execute_parameters(): external_function_parameters
    {
        return new external_function_parameters([
            'item_id' => new external_value(PARAM_INT, '', VALUE_REQUIRED),
            'course_id' => new external_value(PARAM_INT, '', VALUE_REQUIRED),
        ]);
    }

    private static function format_response(entity $item, int $course_id): object
    {
        global $USER, $DB;

        $backup_tasks = $DB->get_records('task_adhoc', [
            'userid' => $USER->id,
            'classname' => "\\block_sharing_cart\\task\\asynchronous_backup_task",
        ]);
        array_walk($backup_tasks, static function (object $task) {
            $task->item_id = json_decode($task->customdata)?->item?->id;
            unset($task->customdata);
        });
        $backup_tasks = array_combine(
            array_column($backup_tasks, 'item_id'),
            $backup_tasks
        );
        $backup_task = $backup_tasks[$item->get_id()] ?? null;

        $is_running = $backup_task && $backup_task->timestarted !== null;
        $is_failed = $backup_task && $backup_task->faildelay > 0;
        $has_waited_5_seconds = $backup_task && time() - $backup_task->timecreated > 5;

        if ($is_failed || ($backup_task === null && $item->get_status() !== entity::STATUS_BACKEDUP)) {
            $item->set_status(entity::STATUS_BACKUP_FAILED);
            factory::make()->item()->repository()->update($item);
        }

        $response = (object)$item->to_array();

        $allow_to_run_now = has_capability('block/sharing_cart:manual_run_task', \core\context\system::instance(), $USER);
        $response->show_run_now = $allow_to_run_now && !$is_running && !$is_failed && $has_waited_5_seconds;
        $response->can_copy_to_course = has_capability('moodle/restore:restoreactivity', \core\context\course::instance($course_id), $USER);

        return $response;
    }

    public static function execute(int $item_id, int $course_id): ?object
    {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'item_id' => $item_id,
            'course_id' => $course_id,
        ]);

        self::validate_context(
            \core\context\course::instance($course_id)
        );
        $item = factory::make()->item()->repository()->get_by_id($params['item_id']);
        if (!$item) {
            return null;
        }

        if ($item->get_user_id() !== (int)$USER->id) {
            return null;
        }

        return self::format_response($item, $course_id);
    }

    public static function execute_returns(): external_description
    {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'The id of the item in the sharing cart', VALUE_REQUIRED),
            'user_id' => new external_value(PARAM_INT, 'The id of the user who owns the item', VALUE_REQUIRED),
            'file_id' => new external_value(PARAM_INT, 'The id of the backup file', VALUE_OPTIONAL),
            'parent_item_id' => new external_value(PARAM_INT, 'The id of the parent item', VALUE_OPTIONAL),
            'old_instance_id' => new external_value(PARAM_INT, 'The old instance id', VALUE_REQUIRED),
            'type' => new external_value(PARAM_TEXT, 'The type of the item', VALUE_REQUIRED),
            'name' => new external_value(PARAM_TEXT, 'The name of the item', VALUE_REQUIRED),
            'status' => new external_value(PARAM_INT, 'The status of the item', VALUE_REQUIRED),
            'show_run_now' => new external_value(PARAM_BOOL, 'Whether the item can be run now', VALUE_REQUIRED),
            'can_copy_to_course' => new external_value(PARAM_BOOL, 'Whether the item can be copied to the course', VALUE_REQUIRED),
            'timecreated' => new external_value(PARAM_INT, 'The time the item was created', VALUE_REQUIRED),
            'timemodified' => new external_value(PARAM_INT, 'The time the item was last modified', VALUE_REQUIRED),
        ]);
    }
}
