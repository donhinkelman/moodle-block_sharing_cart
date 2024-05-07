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
use core_external\external_multiple_structure;
use core_external\external_value;

class delete_items_from_sharing_cart extends external_api
{
    public static function execute_parameters(): external_function_parameters
    {
        return new external_function_parameters([
            'item_ids' => new external_multiple_structure(
                new external_value(PARAM_INT, '', VALUE_REQUIRED),
            )
        ]);
    }

    public static function execute(array $item_ids): array
    {
        global $USER;

        $base_factory = factory::make();

        $params = self::validate_parameters(self::execute_parameters(), [
            'item_ids' => $item_ids,
        ]);

        self::validate_context(
            \context_user::instance($USER->id)
        );

        $deleted_item_ids = [];

        foreach ($params['item_ids'] as $item_id) {
            $item = $base_factory->item()->repository()->get_by_id($item_id);
            if (!$item) {
                continue;
            }

            if ($item->get_user_id() !== (int)$USER->id) {
                continue;
            }

            if ($base_factory->item()->repository()->delete_by_id($item->get_id())) {
                $deleted_item_ids[] = $item->get_id();
            }
        }

        return $deleted_item_ids;
    }

    public static function execute_returns(): external_description
    {
        return new external_multiple_structure(
            new external_value(PARAM_INT, 'Item id which was deleted', VALUE_REQUIRED)
        );
    }
}