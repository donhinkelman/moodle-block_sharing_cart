<?php

namespace block_sharing_cart\external\backup;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

use block_sharing_cart\app\factory;
use core_external\external_api;
use core_external\external_description;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

class course_into_sharing_cart extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, '', VALUE_REQUIRED),
        ]);
    }

    public static function execute(int $course_id): object {
        global $USER;

        $base_factory = factory::make();

        $params = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
        ]);

        $course = get_course($params['course_id']);

        self::validate_context(\context_course::instance($course->id));

        $time = time();
        $item_id = $base_factory->item()->repository()->insert((object)[
            'user_id' => $USER->id,
            'file_id' => null,
            'parent_item_id' => null,
            'type' => 'course',
            'name' => $course->fullname,
            'status' => 0,
            'timecreated' => $time,
            'timemodified' => $time,
        ]);
        $item = $base_factory->item()->repository()->get_by_id($item_id);

        $base_factory->backup()->handler()->backup_course($course_id, $item);

        return $item;
    }

    public static function execute_returns(): external_description {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'The id of the item in the sharing cart', VALUE_REQUIRED),
            'user_id' => new external_value(PARAM_INT, 'The id of the user who owns the item', VALUE_REQUIRED),
            'file_id' => new external_value(PARAM_INT, 'The id of the backup file', VALUE_OPTIONAL),
            'parent_item_id' => new external_value(PARAM_INT, 'The id of the parent item', VALUE_OPTIONAL),
            'type' => new external_value(PARAM_TEXT, 'The type of the item', VALUE_REQUIRED),
            'name' => new external_value(PARAM_TEXT, 'The name of the item', VALUE_REQUIRED),
            'status' => new external_value(PARAM_INT, 'The status of the item', VALUE_REQUIRED),
            'timecreated' => new external_value(PARAM_INT, 'The time the item was created', VALUE_REQUIRED),
            'timemodified' => new external_value(PARAM_INT, 'The time the item was last modified', VALUE_REQUIRED),
        ]);
    }
}