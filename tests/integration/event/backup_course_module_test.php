<?php

namespace block_sharing_cart\integration\event;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class backup_course_module_test extends \advanced_testcase
{
    protected function setUp(): void
    {
        $this->resetAfterTest();
    }

    private function get_triggered_event(\core\event\base $event): \block_sharing_cart\event\backup_course_module
    {
        $event_redirect = $this->redirectEvents();

        $event->trigger();

        $event_redirect->close();

        $events = $event_redirect->get_events();

        $actual = $events[array_key_first($events)];
        if (!$actual instanceof \block_sharing_cart\event\backup_course_module) {
            throw new \Exception('Expected event to be of type backup_course_module');
        }
        return $actual;
    }

    public function test_trigger_event(): void
    {
        global $USER;

        self::setAdminUser();

        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        $course_module = $generator->create_module('assign', [
            'course' => $course->id,
        ]);
        $course_module->modname ??= 'assign';

        $event = \block_sharing_cart\event\backup_course_module::create_by_course_module(
            $course->id,
            $course_module->id,
            $USER->id,
        );

        $actual = $this->get_triggered_event($event);

        // Check the event data.
        self::assertEquals($course->id, $actual->get_course_id());
        self::assertEquals($course_module->id, $actual->get_course_module_id());
    }
}
