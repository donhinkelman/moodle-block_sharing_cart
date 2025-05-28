<?php

namespace block_sharing_cart\event;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

abstract class backup extends base
{
    protected function get_crud(): string
    {
        return self::CRUD_CREATE;
    }

    protected function get_table(): ?string
    {
        return null;
    }

    public function get_course_id(): int
    {
        return $this->other['courseid'] ?? 0;
    }
}
