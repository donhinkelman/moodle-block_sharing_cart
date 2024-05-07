<?php

namespace block_sharing_cart\external\task;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use core_external\external_api;
use core_external\external_description;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;

class run_now extends external_api
{
    public static function execute_parameters(): external_function_parameters
    {
        return new external_function_parameters([
            'task_id' => new external_value(PARAM_INT, '', VALUE_REQUIRED),
        ]);
    }

    public static function execute(
        int $task_id,
    ): bool {
        global $USER, $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'task_id' => $task_id,
        ]);

        self::validate_context(
            \context_user::instance($USER->id)
        );

        $task = $DB->get_record(
            'task_adhoc',
            [
                'id' => $params['task_id'],
                'component' => 'block_sharing_cart',
                'faildelay' => 0,
                'timestarted' => null,
                'userid' => $USER->id
            ],
            strictness: MUST_EXIST
        );

        ob_start();
        \core\task\manager::run_adhoc_from_cli($task->id);
        ob_end_clean();

        return true;
    }

    public static function execute_returns(): external_description
    {
        return new external_value(PARAM_BOOL, '', VALUE_REQUIRED);
    }
}