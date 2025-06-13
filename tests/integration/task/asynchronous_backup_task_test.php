<?php

namespace block_sharing_cart\integration\task;

use block_sharing_cart\app\factory;
use block_sharing_cart\app\item\entity;
use block_sharing_cart\task\asynchronous_backup_task;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class asynchronous_backup_task_test extends \advanced_testcase
{
    private factory $factory;

    protected function setUp(): void
    {
        $this->resetAfterTest();
        $this->factory = factory::make();
    }

    private function create_section(int $course_id, array $record = []): object
    {
        $db = $this->factory->moodle()->db();

        $record['course'] = $course_id;

        if (!isset($record['section'])) {
            $last_section_number = (int)$db->get_field(
                'course_sections',
                'MAX(section)',
                ['course' => $course_id]
            );
            $record['section'] = $last_section_number + 1;
        }

        $section = self::getDataGenerator()->create_course_section($record);
        return $db->get_record(
            'course_sections',
            ['id' => $section->id],
            '*',
            MUST_EXIST
        );
    }

    private function create_task(asynchronous_backup_task $task): asynchronous_backup_task
    {
        return new class($task) extends asynchronous_backup_task {
            private asynchronous_backup_task $task;

            public function __construct(asynchronous_backup_task $task)
            {
                $this->task = $task;
                $this->task->output = false;
            }

            protected function factory(): factory
            {
                return $this->task->factory();
            }

            protected function db(): \moodle_database
            {
                return $this->task->db();
            }

            protected function get_backup_id(): string
            {
                return $this->task->get_backup_id();
            }

            public function get_backup_controller(): ?\backup_controller
            {
                return $this->task->get_backup_controller();
            }

            public function execute(): void
            {
                $this->task->execute();
            }

            public function retry_until_success(): bool
            {
                return $this->task->retry_until_success();
            }

            protected function before_backup_started_hook(\backup_controller $backup_controller): void
            {
                $this->task->before_backup_started_hook($backup_controller);
            }

            protected function after_backup_finished_hook(\backup_controller $backup_controller): void
            {
                $this->task->after_backup_finished_hook($backup_controller);
            }

            /**
             * @param class-string<asynchronous_backup_task> $method
             * @param mixed ...$args
             * @return mixed
             */
            public function call(string $method, mixed ...$args): mixed
            {
                return $this->task->{$method}(...$args);
            }
        };
    }

    public function test_backup_section_with_quiz_expected_questionbank_setting_value_returns_true(): void
    {
        global $USER;
        self::setAdminUser();

        $generator = self::getDataGenerator();
        $user = $USER;
        $course = $generator->create_course();
        $section = $this->create_section($course->id, [
            'name' => 'Test Section 1',
        ]);
        $generator->create_module('quiz', [
            'course' => $course->id,
            'section' => $section->section,
            'name' => 'Test Quiz 1',
        ]);
        $generator->create_module('label', [
            'course' => $course->id,
            'section' => $section->section,
            'name' => 'Test Label 1',
        ]);
        $generator->enrol_user($user->id, $course->id, 'editingteacher');

        $item = $this->factory->item()->repository()->insert_section(
            $section->id,
            $user->id,
            null,
            entity::STATUS_AWAITING_BACKUP
        );

        $handler = $this->factory->backup()->handler();
        $task = $this->create_task($handler->backup_section(
            $section->id,
            $item,
            [
                'users' => false,
                'anonymize' => false,
            ]
        ));

        $controller = $task->get_backup_controller();
        self::assertNotEmpty($controller);

        $plan = $controller->get_plan();
        if (!$plan->setting_exists('questionbank')) {
            self::markTestSkipped(
                "Skip the test because the 'questionbank' setting does not exist in the backup plan."
            );
        }

        $task->call('before_backup_started_hook', $controller);

        $setting = $plan->get_setting('questionbank');
        self::assertTrue(
            (bool)$setting->get_value(),
            'Expected questionbank setting to be true when quiz is present in the section backup'
        );
    }

    public function test_backup_section_with_no_quiz_expected_questionbank_setting_value_returns_false(): void
    {
        global $USER;
        self::setAdminUser();

        $generator = self::getDataGenerator();
        $user = $USER;
        $course = $generator->create_course();
        $section = $this->create_section($course->id, [
            'name' => 'Test Section 1',
        ]);
        $generator->create_module(
            'label', // Using label instead of quiz to ensure no questionbank setting is present
            [
                'course' => $course->id,
                'section' => $section->section,
                'name' => 'Test Label 1',
            ]
        );

        // Enroll the user as an editing teacher in the course
        $generator->enrol_user($user->id, $course->id, 'editingteacher');

        $item = $this->factory->item()->repository()->insert_section(
            $section->id,
            $user->id,
            null,
            entity::STATUS_AWAITING_BACKUP
        );

        $handler = $this->factory->backup()->handler();
        $task = $this->create_task($handler->backup_section(
            $section->id,
            $item,
            [
                'users' => false,
                'anonymize' => false,
            ]
        ));

        $controller = $task->get_backup_controller();
        self::assertNotEmpty($controller);

        $plan = $controller->get_plan();
        if (!$plan->setting_exists('questionbank')) {
            self::markTestSkipped(
                "Skip the test because the 'questionbank' setting does not exist in the backup plan."
            );
        }

        $task->call('before_backup_started_hook', $controller);

        $setting = $plan->get_setting('questionbank');
        self::assertFalse(
            (bool)$setting->get_value(),
            'Expected questionbank setting to be false when no quiz is present in the section backup'
        );
    }

    public function test_backup_quiz_activity_expected_questionbank_setting_value_returns_true(): void
    {
        global $USER;
        self::setAdminUser();

        $generator = self::getDataGenerator();
        $user = $USER;
        $course = $generator->create_course();
        $section = $this->create_section($course->id, [
            'name' => 'Test Section 1',
        ]);
        $activity = $generator->create_module(
            'quiz', // Using label instead of quiz to ensure no questionbank setting is present
            [
                'course' => $course->id,
                'section' => $section->section,
                'name' => 'Test Quiz 1',
            ]
        );

        // Enroll the user as an editing teacher in the course
        $generator->enrol_user($user->id, $course->id, 'editingteacher');

        $item = $this->factory->item()->repository()->insert_activity(
            $activity->cmid,
            $user->id,
            null,
            entity::STATUS_AWAITING_BACKUP
        );

        $handler = $this->factory->backup()->handler();
        $task = $this->create_task($handler->backup_course_module(
            $activity->cmid,
            $item,
            [
                'users' => false,
                'anonymize' => false,
            ]
        ));

        $controller = $task->get_backup_controller();
        self::assertNotEmpty($controller);

        $plan = $controller->get_plan();
        if (!$plan->setting_exists('questionbank')) {
            self::markTestSkipped(
                "Skip the test because the 'questionbank' setting does not exist in the backup plan."
            );
        }

        $task->call('before_backup_started_hook', $controller);

        $setting = $plan->get_setting('questionbank');
        self::assertTrue(
            (bool)$setting->get_value(),
            'Expected questionbank setting to be true when quiz is present in the course module backup'
        );
    }

    public function test_backup_label_activity_expected_questionbank_setting_value_returns_false(): void
    {
        global $USER;
        self::setAdminUser();

        $generator = self::getDataGenerator();
        $user = $USER;
        $course = $generator->create_course();
        $section = $this->create_section($course->id, [
            'name' => 'Test Section 1',
        ]);
        $activity = $generator->create_module(
            'label', // Using label instead of quiz to ensure no questionbank setting is present
            [
                'course' => $course->id,
                'section' => $section->section,
                'name' => 'Test Label 1',
            ]
        );

        // Enroll the user as an editing teacher in the course
        $generator->enrol_user($user->id, $course->id, 'editingteacher');

        $item = $this->factory->item()->repository()->insert_activity(
            $activity->cmid,
            $user->id,
            null,
            entity::STATUS_AWAITING_BACKUP
        );

        $handler = $this->factory->backup()->handler();
        $task = $this->create_task($handler->backup_course_module(
            $activity->cmid,
            $item,
            [
                'users' => false,
                'anonymize' => false,
            ]
        ));

        $controller = $task->get_backup_controller();
        self::assertNotEmpty($controller);

        $plan = $controller->get_plan();
        if (!$plan->setting_exists('questionbank')) {
            self::markTestSkipped(
                "Skip the test because the 'questionbank' setting does not exist in the backup plan."
            );
        }

        $task->call('before_backup_started_hook', $controller);

        $setting = $plan->get_setting('questionbank');
        self::assertFalse(
            (bool)$setting->get_value(),
            'Expected questionbank setting to be false when no quiz is present in the course module backup'
        );
    }
}
