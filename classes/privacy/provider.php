<?php

namespace block_sharing_cart\privacy;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use core_privacy\local\metadata\collection;

class provider implements \core_privacy\local\metadata\provider
{
    public static function get_metadata(collection $collection): collection
    {
        $collection->add_database_table('block_sharing_cart_items', [
            'user_id' => 'privacy:metadata:sharing_cart_items:user_id',
            'file_id' => 'privacy:metadata:sharing_cart_items:file_id',
            'parent_item_id' => 'privacy:metadata:sharing_cart_items:parent_item_id',
            'old_instance_id' => 'privacy:metadata:sharing_cart_items:old_instance_id',
            'type' => 'privacy:metadata:sharing_cart_items:type',
            'name' => 'privacy:metadata:sharing_cart_items:name',
            'status' => 'privacy:metadata:sharing_cart_items:status',
            'timecreated' => 'privacy:metadata:sharing_cart_items:timecreated',
            'timemodified' => 'privacy:metadata:sharing_cart_items:timemodified',
        ]);

        return $collection;
    }
}