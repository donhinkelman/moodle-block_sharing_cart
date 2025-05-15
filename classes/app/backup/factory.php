<?php

namespace block_sharing_cart\app\backup;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use block_sharing_cart\app\factory as base_factory;

class factory
{
    private base_factory $base_factory;

    public function __construct(base_factory $base_factory)
    {
        $this->base_factory = $base_factory;
    }

    public function backup_controller(string $type, int $instance_id, int $user_id): \backup_controller
    {
        return new \backup_controller(
            $type,
            $instance_id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_ASYNC,
            $user_id,
            \backup::RELEASESESSION_YES
        );
    }

    public function handler(): handler
    {
        return new handler($this->base_factory);
    }

    public function settings_helper()
    {
        return new backup_settings_helper($this->base_factory);
    }
}