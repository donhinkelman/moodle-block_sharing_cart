<?php

namespace block_sharing_cart\integration\event;

use advanced_testcase;
use block_sharing_cart\event\restored_course_module;
use core\event\base;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class restored_course_module_test extends advanced_testcase
{
    protected function setUp(): void
    {
        $this->resetAfterTest();
    }

    private function get_triggered_event(base $event): restored_course_module
    {
        $event_redirect = $this->redirectEvents();

        $event->trigger();

        $event_redirect->close();

        $events = $event_redirect->get_events();

        $actual = $events[array_key_first($events)];
        if (!$actual instanceof restored_course_module) {
            throw new \Exception('Expected event to be of type restored_course_module');
        }
        return $actual;
    }

    /**
     * Test the event creation and triggering.
     * See if the event is triggered correctly and the data is set as expected.
     * @return void
     * @throws \Exception
     */
    public function test_trigger_event(): void
    {
        self::setAdminUser();

        global $USER;

        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        $course_module = $generator->create_module('assign', [
            'course' => $course->id,
        ]);
        $course_module->modname ??= 'assign';

        $start_time = time();
        $finish_time = time() + 10;

        $event = restored_course_module::create_by_course_module(
            $course->id,
            $course_module->cmid,
            $course_module->modname,
            $USER->id,
            $start_time,
            $finish_time
        );

        $actual = $this->get_triggered_event($event);

        self::assertEquals(
            $event,
            $actual
        );
    }
}
