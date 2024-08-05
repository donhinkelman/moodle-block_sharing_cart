<?php

namespace block_sharing_cart\integration;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use advanced_testcase;
use block_sharing_cart\app\factory;

class observers_test extends advanced_testcase
{
    protected function setUp(): void
    {
        $this->resetAfterTest();
    }

    public function test_deleted_user_expect_sharing_cart_record_to_be_remove(): void
    {
        $user = self::getDataGenerator()->create_user();
        $this->create_sharing_cart_item($user->id);

        self::assertTrue(
            $this->has_sharing_cart_item($user->id)
        );

        delete_user($user);

        self::assertFalse(
            $this->has_sharing_cart_item($user->id)
        );
    }

    private function has_sharing_cart_item(int $user_id): bool
    {
        $base_factory = factory::make();

        return $base_factory->item()->repository()->get_by_user_id($user_id)->not_empty();
    }

    private function create_sharing_cart_item(int $user_id): object
    {
        $base_factory = factory::make();

        $entity = $base_factory->item()->entity((object)[]);
        $entity->set_user_id($user_id);
        $entity->set_file_id(0);
        $entity->set_parent_item_id(0);
        $entity->set_old_instance_id(0);
        $entity->set_type('section');
        $entity->set_name('Some section name');
        $entity->set_status($entity::STATUS_AWAITING_BACKUP);
        $entity->set_sortorder(0);
        $entity->set_timecreated(time());
        $entity->set_timemodified(time());

        $id = $base_factory->item()->repository()->insert($entity);

        $entity->set_id($id);

        return $entity;
    }
}
