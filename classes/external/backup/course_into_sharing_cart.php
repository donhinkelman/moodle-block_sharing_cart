<?php

namespace block_sharing_cart\external\backup;

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

class course_into_sharing_cart extends external_api
{
    public static function execute_parameters(): external_function_parameters
    {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, '', VALUE_REQUIRED),
            'settings' => new external_single_structure([
                'users' => new external_value(PARAM_BOOL, 'Whether to include user data in the backup', VALUE_REQUIRED),
                'anonymize' => new external_value(
                    PARAM_BOOL, 'Whether to anonymize user data in the backup', VALUE_REQUIRED
                ),
            ], 'The settings of the item')
        ]);
    }

    public static function execute(int $course_id, array $settings): object
    {
        global $USER;

        $base_factory = factory::make();

        $params = self::validate_parameters(self::execute_parameters(), [
            'course_id' => $course_id,
            'settings' => $settings,
        ]);

        self::validate_context(\context_course::instance($params['course_id']));

        $item = $base_factory->item()->repository()->insert_course(
            $course_id,
            $USER->id,
            null,
            entity::STATUS_AWAITING_BACKUP
        );

        $base_factory->backup()->handler()->backup_course($course_id, $item, $settings);

        return (object)$item->to_array();
    }

    public static function execute_returns(): external_description
    {
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