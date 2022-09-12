<?php

namespace block_sharing_cart\event;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class section_restored extends \core\event\base {
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'block_sharing_cart_sections';
    }

    public function get_restored_section_id(): int {
        return $this->data['other']['restored_section_id'];
    }

    public function overwrite_section_settings(): bool {
        return $this->data['other']['overwrite_section_settings'];
    }

    public function get_sharing_cart_section_id(): int {
        return $this->data['objectid'];
    }
}