<?php

namespace block_sharing_cart\external\item;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use block_sharing_cart\app\factory;
use core_external\external_api;
use core_external\external_description;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_value;

class reorder_sharing_cart_items extends external_api
{
    public static function execute_parameters(): external_function_parameters
    {
        return new external_function_parameters([
            'item_ids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Item ID', VALUE_REQUIRED), 'Item IDs', VALUE_REQUIRED,
            )
        ]);
    }

    public static function execute(array $item_ids): bool
    {
        global $USER;

        $base_factory = factory::make();

        $params = self::validate_parameters(self::execute_parameters(), [
            'item_ids' => $item_ids,
        ]);

        self::validate_context(
            \context_user::instance($USER->id)
        );

        /**
         * @var \block_sharing_cart\app\item\entity[] $items
         */
        $items = $base_factory->item()->repository()->get_by_user_id($USER->id);
        foreach ($items as $item) {
            if ($item->get_parent_item_id()) {
                continue;
            }

            $item->set_sortorder(array_search($item->get_id(), $params['item_ids'], true));
            $base_factory->item()->repository()->update($item);
        }

        return true;
    }

    public static function execute_returns(): external_description
    {
        return new external_value(PARAM_BOOL, 'Whether the item was deleted', VALUE_REQUIRED);
    }
}