<?php

namespace block_sharing_cart\app\items;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

use block_sharing_cart\app\collection;

class repository extends \block_sharing_cart\app\repository {
    public function get_table(): string {
        return 'block_sharing_cart_items';
    }

    public function get_by_user_id(int $user_id): collection {
        return $this->base_factory->collection(
            $this->db->get_records($this->get_table(), ['user_id' => $user_id])
        );
    }
}