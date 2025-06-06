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

class section_into_sharing_cart extends external_api
{
    public static function execute_parameters(): external_function_parameters
    {
        return new external_function_parameters([
            'section_id' => new external_value(PARAM_INT, '', VALUE_REQUIRED),
            'settings' => new external_single_structure([
                'users' => new external_value(PARAM_BOOL, 'Whether to include user data in the backup', VALUE_REQUIRED),
                'anonymize' => new external_value(
                    PARAM_BOOL, 'Whether to anonymize user data in the backup', VALUE_REQUIRED
                ),
            ], 'The settings of the item')
        ]);
    }

    public static function execute(int $section_id, array $settings): object
    {
        global $USER, $DB;

        $base_factory = factory::make();

        $params = self::validate_parameters(self::execute_parameters(), [
            'section_id' => $section_id,
            'settings' => $settings,
        ]);

        $course_id = $DB->get_field('course_sections', 'course', ['id' => $params['section_id']], MUST_EXIST);

        self::validate_context(
            \context_course::instance($course_id)
        );

        $sequence = $DB->get_field('course_sections', 'sequence', ['id' => $params['section_id']], MUST_EXIST);
        if (empty($sequence)) {
            throw new \Exception('Section is empty');
        }

        $item = $base_factory->item()->repository()->insert_section(
            $params['section_id'],
            $USER->id,
            null,
            entity::STATUS_AWAITING_BACKUP
        );

        $backup_task = $base_factory->backup()->handler()->backup_section($params['section_id'], $item, $settings);

        $return = $item->to_array();
        $return['task_id'] = $backup_task->get_id();

        return (object)$return;
    }

    public static function execute_returns(): external_description
    {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'The id of the item in the sharing cart', VALUE_REQUIRED),
            'user_id' => new external_value(PARAM_INT, 'The id of the user who owns the item', VALUE_REQUIRED),
            'file_id' => new external_value(PARAM_INT, 'The id of the backup file', VALUE_REQUIRED),
            'parent_item_id' => new external_value(PARAM_INT, 'The id of the parent item', VALUE_REQUIRED),
            'old_instance_id' => new external_value(PARAM_INT, 'The old instance id', VALUE_REQUIRED),
            'task_id' => new external_value(PARAM_INT, 'The task id of backup adhoc task', VALUE_REQUIRED),
            'type' => new external_value(PARAM_TEXT, 'The type of the item', VALUE_REQUIRED),
            'name' => new external_value(PARAM_TEXT, 'The name of the item', VALUE_REQUIRED),
            'status' => new external_value(PARAM_INT, 'The status of the item', VALUE_REQUIRED),
            'version' => new external_value(PARAM_INT, 'The version of the item', VALUE_REQUIRED),
            'timecreated' => new external_value(PARAM_INT, 'The time the item was created', VALUE_REQUIRED),
            'timemodified' => new external_value(PARAM_INT, 'The time the item was last modified', VALUE_REQUIRED),
        ]);
    }
}
