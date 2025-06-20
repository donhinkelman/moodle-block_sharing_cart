<?php

namespace block_sharing_cart\event;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class backup_course_module extends backup
{
    public function get_course_module_id(): int
    {
        return $this->other['cmid'] ?? 0;
    }

    public function get_description(): string
    {
        return "User with id {$this->relateduserid} has backed up"
            . " a course module with id {$this->get_course_module_id()}"
            . " in the course with id {$this->get_course_id()}.";
    }

    public static function create_by_course_module(
        int $course_id,
        int $course_module_id,
        int $user_id
    ): static
    {
        global $USER;

        $user_id ??= $USER->id;

        return static::create([
            'context' => \core\context\user::instance($user_id),
            'relateduserid' => $user_id,
            'other' => [
                'courseid' => $course_id,
                'cmid' => $course_module_id,
            ],
        ]);
    }
}
