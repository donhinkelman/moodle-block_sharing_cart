<?php

namespace block_sharing_cart\event;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class restored_section extends restore
{
    protected function get_table(): ?string
    {
        return 'course_sections';
    }

    public function get_section_id(): int
    {
        return $this->other['sectionid'] ?? 0;
    }

    public function get_description(): string
    {
        return "User with id {$this->relateduserid} has restored a section with id {$this->get_section_id()}"
            . " in course with id {$this->get_course_id()}"
            . " that takes {$this->get_duration()} seconds";
    }

    public static function create_by_section(
        int $course_id,
        int $section_id,
        int $user_id,
        int $start_time = 0,
        int $finish_time = 0
    ): static
    {
        return static::create([
            'objectid' => $section_id,
            'context' => \core\context\course::instance($course_id),
            'relateduserid' => $user_id,
            'other' => [
                'courseid' => $course_id,
                'sectionid' => $section_id,
                'starttime' => $start_time,
                'finishtime' => $finish_time,
            ],
        ]);
    }
}
