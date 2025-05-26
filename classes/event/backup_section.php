<?php

namespace block_sharing_cart\event;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class backup_section extends backup
{
    public function get_section_id(): int
    {
        return $this->other['sectionid'] ?? 0;
    }

    public function get_description(): string
    {
        return "User with id {$this->relateduserid} has backed up a section with id {$this->get_section_id()}"
            . " in course with id {$this->get_course_id()}";
    }

    public static function create_by_section(
        int $course_id,
        int $section_id,
        int $user_id
    ): static
    {
        return static::create([
            'context' => \core\context\user::instance($user_id),
            'relateduserid' => $user_id,
            'other' => [
                'courseid' => $course_id,
                'sectionid' => $section_id,
            ],
        ]);
    }
}
