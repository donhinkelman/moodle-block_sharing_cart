<?php

namespace block_sharing_cart\integration\event;

use advanced_testcase;
use block_sharing_cart\event\restored_section;
use core\event\base;
use Exception;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class restored_section_test extends advanced_testcase
{
    protected function setUp(): void
    {
        $this->resetAfterTest();
    }

    private function get_triggered_event(base $event): restored_section
    {
        $event_redirect = $this->redirectEvents();

        $event->trigger();

        $event_redirect->close();

        $events = $event_redirect->get_events();

        $actual = $events[array_key_first($events)];
        if (!$actual instanceof restored_section) {
            throw new Exception('Expected event to be of type restored_section');
        }
        return $actual;
    }

    /**
     * Test the event creation and triggering.
     * See if the event is triggered correctly and the data is set as expected.
     * @return void
     * @throws Exception
     */
    public function test_trigger_event(): void
    {
        global $DB;
        self::setAdminUser();

        global $USER;

        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $section_number = $DB->count_records_select('course_sections', "course = ?", [$course->id]);
        $section = $generator->create_course_section([
            'course' => $course->id,
            'section' => $section_number + 1,
        ]);

        $start_time = time();
        $finish_time = time() + 10;

        $event = restored_section::create_by_section(
            $course->id,
            $section->id,
            $USER->id,
            $start_time,
            $finish_time
        );

        $actual = $this->get_triggered_event($event);

        self::assertEquals(
            $event,
            $actual,
            'The event should be the same as the one created.'
        );
    }
}
