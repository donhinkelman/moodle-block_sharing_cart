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

class delete_item_from_sharing_cart extends external_api
{
    public static function execute_parameters(): external_function_parameters
    {
        return new external_function_parameters([
            'item_id' => new external_value(PARAM_INT, '', VALUE_REQUIRED),
        ]);
    }

    public static function execute(int $item_id): bool
    {
        global $USER;

        $base_factory = factory::make();

        $params = self::validate_parameters(self::execute_parameters(), [
            'item_id' => $item_id,
        ]);

        self::validate_context(
            \context_user::instance($USER->id)
        );

        $item = $base_factory->item()->repository()->get_by_id($params['item_id']);
        if (!$item) {
            return true;
        }

        if ($item->get_user_id() !== (int)$USER->id) {
            return false;
        }

        return $base_factory->item()->repository()->delete_by_id($item->get_id());
    }

    public static function execute_returns(): external_description
    {
        return new external_value(PARAM_BOOL, 'Whether the item was deleted', VALUE_REQUIRED);
    }
}