<?php

namespace block_sharing_cart\external\restore;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use block_sharing_cart\app\factory;
use core_external\external_api;
use core_external\external_description;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

class item_into_section extends external_api
{
    public static function execute_parameters(): external_function_parameters
    {
        return new external_function_parameters([
            'item_id' => new external_value(PARAM_INT, '', VALUE_REQUIRED),
            'section_id' => new external_value(PARAM_INT, '', VALUE_REQUIRED),
            'sections_to_include' => new \external_multiple_structure(
                new external_value(PARAM_INT, '', VALUE_REQUIRED)
            ),
            'course_modules_to_include' => new \external_multiple_structure(
                new external_value(PARAM_INT, '', VALUE_REQUIRED)
            ),
        ]);
    }

    public static function execute(
        int $item_id,
        int $section_id,
        array $sections_to_include,
        array $course_modules_to_include
    ): bool {
        global $USER;

        $base_factory = factory::make();

        $params = self::validate_parameters(self::execute_parameters(), [
            'item_id' => $item_id,
            'section_id' => $section_id,
            'sections_to_include' => $sections_to_include,
            'course_modules_to_include' => $course_modules_to_include,
        ]);

        self::validate_context(
            \context_user::instance($USER->id)
        );

        $item = $base_factory->item()->repository()->get_by_id($params['item_id']);
        if (!$item) {
            return false;
        }

        if ($item->get_user_id() !== (int)$USER->id) {
            return false;
        }

        $base_factory->restore()->handler()->restore_item_into_section($item, $params['section_id'], [
            'sections_to_include' => $params['sections_to_include'],
            'course_modules_to_include' => $params['course_modules_to_include'],
        ]);

        return true;
    }

    public static function execute_returns(): external_description
    {
        return new external_value(PARAM_BOOL, '', VALUE_REQUIRED);
    }
}