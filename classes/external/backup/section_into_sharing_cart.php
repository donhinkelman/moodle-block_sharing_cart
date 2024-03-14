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

class section_into_sharing_cart extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'section_id' => new external_value(PARAM_INT, '', VALUE_REQUIRED),
        ]);
    }

    public static function execute(int $section_id): object {
        global $USER, $DB;

        $base_factory = factory::make();

        $params = self::validate_parameters(self::execute_parameters(), [
            'section_id' => $section_id,
        ]);

        $course_id = $DB->get_field('course_sections', 'course', ['id' => $params['section_id']], MUST_EXIST);

        self::validate_context(
            \context_course::instance($course_id)
        );

        $item_id = $base_factory->item()->repository()->insert_section($params['section_id'], $USER->id,null,0);
        $item = $base_factory->item()->repository()->get_by_id($item_id);

        $base_factory->backup()->handler()->backup_section($params['section_id'], $item);

        return $item;
    }

    public static function execute_returns(): external_description {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'The id of the item in the sharing cart', VALUE_REQUIRED),
            'user_id' => new external_value(PARAM_INT, 'The id of the user who owns the item', VALUE_REQUIRED),
            'file_id' => new external_value(PARAM_INT, 'The id of the backup file', VALUE_REQUIRED),
            'parent_item_id' => new external_value(PARAM_INT, 'The id of the parent item', VALUE_REQUIRED),
            'type' => new external_value(PARAM_TEXT, 'The type of the item', VALUE_REQUIRED),
            'name' => new external_value(PARAM_TEXT, 'The name of the item', VALUE_REQUIRED),
            'status' => new external_value(PARAM_INT, 'The status of the item', VALUE_REQUIRED),
            'timecreated' => new external_value(PARAM_INT, 'The time the item was created', VALUE_REQUIRED),
            'timemodified' => new external_value(PARAM_INT, 'The time the item was last modified', VALUE_REQUIRED),
        ]);
    }
}