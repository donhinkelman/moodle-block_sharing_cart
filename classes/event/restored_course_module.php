<?php

namespace block_sharing_cart\event;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class restored_course_module extends restore
{
    protected function get_table(): ?string
    {
        return 'course_modules';
    }

    public function get_course_module_id(): int
    {
        return $this->other['cmid'] ?? 0;
    }

    public function get_description(): string
    {
        return "User with id {$this->relateduserid} has restored"
            . " a course module with id {$this->get_course_module_id()}"
            . " in the course with id {$this->get_course_id()}"
            . " that takes {$this->get_duration()} seconds";
    }

    public static function create_by_course_module(
        int $course_id,
        int $course_module_id,
        string $type,
        int $user_id,
        int $start_time = 0,
        int $finish_time = 0
    ): static
    {
        return static::create([
            'objectid' => $course_module_id,
            'context' => \core\context\module::instance($course_module_id),
            'relateduserid' => $user_id,
            'other' => [
                'courseid' => $course_id,
                'cmid' => $course_module_id,
                'module' => $type,
                'starttime' => $start_time,
                'finishtime' => $finish_time,
            ],
        ]);
    }
}
