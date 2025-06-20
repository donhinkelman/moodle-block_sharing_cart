<?php

namespace block_sharing_cart\integration\event;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class backup_section_test extends \advanced_testcase
{
    protected function setUp(): void
    {
        $this->resetAfterTest();
    }

    private function get_triggered_event(\core\event\base $event): \block_sharing_cart\event\backup_section
    {
        $event_redirect = $this->redirectEvents();

        $event->trigger();

        $event_redirect->close();

        $events = $event_redirect->get_events();

        $actual = $events[array_key_first($events)];
        if (!$actual instanceof \block_sharing_cart\event\backup_section) {
            throw new \Exception('Expected event to be of type backup_section');
        }
        return $actual;
    }

    public function test_trigger_event(): void
    {
        global $USER;

        self::setAdminUser();

        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        $section = $generator->create_course_section([
            'course' => $course->id,
            'section' => 1,
        ]);

        $event = \block_sharing_cart\event\backup_section::create_by_section(
            $course->id,
            $section->id,
            $USER->id,
        );

        $actual = $this->get_triggered_event($event);

        // Check the event data.
        self::assertEquals($course->id, $actual->get_course_id());
        self::assertEquals($section->id, $actual->get_section_id());
    }
}
