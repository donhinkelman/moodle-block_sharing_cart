<?php

namespace block_sharing_cart\app\item;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

use block_sharing_cart\app\factory as base_factory;

class factory {
    private base_factory $base_factory;

    public function __construct(base_factory $base_factory) {
        $this->base_factory = $base_factory;
    }

    public function repository(): repository {
        return new repository($this->base_factory);
    }
}