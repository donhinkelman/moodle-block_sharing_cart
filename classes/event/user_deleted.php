<?php

namespace block_sharing_cart\event;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use block_sharing_cart\app\factory;

class user_deleted
{
    public static function execute(\core\event\user_deleted $event): void
    {
        $user_id = $event->objectid;

        $base_factory = factory::make();
        $items = $base_factory->item()->repository()->get_by_user_id($user_id);

        foreach ($items as $item) {
            $base_factory->item()->repository()->delete_by_id($item->get_id());
        }
    }
}