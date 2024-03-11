<?php

namespace block_sharing_cart\app;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class factory {
    public static function make(): self {
        return new self();
    }

    public function collection(array $records): collection {
        return new collection($records);
    }

    public function items(): items\factory {
        return new items\factory($this);
    }
}