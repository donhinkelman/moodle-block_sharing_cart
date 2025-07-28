<?php

namespace block_sharing_cart\integration\capabilities;

use core\context\course;
use core\context\system;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class manual_run_task_test extends \advanced_testcase
{
    protected function setUp(): void
    {
        $this->resetAfterTest();
    }

    private function db(): \moodle_database
    {
        global $DB;
        return $DB;
    }

    public function test_user_expected_not_allowed(): void
    {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        $course_context = course::instance($course->id);
        $system_context = system::instance();

        self::assertFalse(
            has_capability('block/sharing_cart:manual_run_task', $course_context, $user),
        );
        self::assertFalse(
            has_capability('block/sharing_cart:manual_run_task', $system_context, $user),
        );

    }

    public function test_user_with_manager_role_expected_not_allowed(): void
    {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();

        $generator->enrol_user($user->id, $course->id, 'manager');
        $context = course::instance($course->id);

        self::assertFalse(
            has_capability('block/sharing_cart:manual_run_task', $context, $user),
        );
    }

    public function test_user_enrolled_in_course_with_capable_role_expected_allowed(): void
    {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();

        $context = course::instance($course->id);
        $role = $this->db()->get_record(
            'role',
            ['id' => $generator->create_role(['shortname' => 'testrole'])]
        );

        $generator->create_role_capability(
            $role->id,
            ['block/sharing_cart:manual_run_task' => 'allow'],
            $context
        );
        $generator->enrol_user(
            $user->id,
            $course->id,
            $role->shortname
        );

        self::assertTrue(
            has_capability('block/sharing_cart:manual_run_task', $context, $user),
        );
    }

    public function test_user_assign_to_capable_role_expected_allowed(): void
    {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $system_context = system::instance();
        $role = $this->db()->get_record(
            'role',
            ['id' => $generator->create_role(['shortname' => 'testrole'])]
        );

        $generator->create_role_capability(
            $role->id,
            ['block/sharing_cart:manual_run_task' => 'allow'],
            $system_context
        );

        $generator->role_assign(
            $role->id,
            $user->id,
            $system_context->id
        );

        self::assertTrue(
            has_capability('block/sharing_cart:manual_run_task', $system_context, $user),
        );
    }

    public function test_user_with_site_admin_expected_allowed(): void
    {
        global $USER;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        self::setAdminUser();

        $generator->enrol_user($USER->id, $course->id, 'editingteacher');
        $context = course::instance($course->id);

        self::assertTrue(
            has_capability('block/sharing_cart:manual_run_task', $context, $USER),
        );
    }
}
