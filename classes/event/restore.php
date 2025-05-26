<?php

namespace block_sharing_cart\event;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

abstract class restore extends base
{
    protected function get_table(): ?string
    {
        return null;
    }

    protected function get_crud(): string
    {
        return self::CRUD_CREATE;
    }

    public function get_course_id(): int
    {
        return $this->other['courseid'] ?? 0;
    }

    public function get_start_time(): int
    {
        return $this->other['starttime'] ?? 0;
    }

    public function get_finish_time(): int
    {
        return $this->other['finishtime'] ?? 0;
    }

    public function get_duration(): int
    {
        return $this->get_finish_time() - $this->get_start_time();
    }
}
