<?php

namespace block_sharing_cart\app;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

class factory
{
    public static function make(): self
    {
        return new self();
    }

    public function collection(array $records = []): collection
    {
        return new collection($records);
    }

    public function backup(): backup\factory
    {
        return new backup\factory($this);
    }

    public function restore(): restore\factory
    {
        return new restore\factory($this);
    }

    public function item(): item\factory
    {
        return new item\factory($this);
    }

    public function moodle(): moodle\factory
    {
        return new moodle\factory($this);
    }
}