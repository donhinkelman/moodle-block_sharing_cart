<?php

namespace block_sharing_cart\event;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class section_deleted extends \core\event\base {
    protected function init(): void {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'block_sharing_cart_sections';
    }

    public function get_sharing_cart_section_id(): int {
        return $this->data['objectid'];
    }
}