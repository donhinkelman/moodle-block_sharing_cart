<?php

namespace block_sharing_cart\app\restore;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use block_sharing_cart\app\factory as base_factory;
use block_sharing_cart\app\item\entity;
use block_sharing_cart\task\asynchronous_restore_task;

class handler
{
    private base_factory $base_factory;

    public function __construct(base_factory $base_factory)
    {
        $this->base_factory = $base_factory;
    }

    public function restore_item_into_section(
        entity $item,
        int $section_id,
        array $settings = []
    ): asynchronous_restore_task {
        global $USER, $DB;

        $course_id = $DB->get_field('course_sections', 'course', ['id' => $section_id], MUST_EXIST);

        $settings['move_to_section_id'] = $section_id;

        $backup_file = $this->base_factory->item()->repository()->get_stored_file_by_item($item);
        if (!$backup_file) {
            throw new \Exception('Backup file not found for item (id: ' . $item->get_id() . ')');
        }

        $restore_controller = $this->base_factory->restore()->restore_controller(
            $backup_file,
            $course_id,
            $USER->id
        );

        return $this->queue_async_restore($restore_controller, $item, $settings);
    }

    private function queue_async_restore(
        \restore_controller $restore_controller,
        entity $item,
        array $settings = []
    ): asynchronous_restore_task {
        $restore_controller->execute_precheck(true);

        $asynctask = new asynchronous_restore_task();
        $asynctask->set_custom_data([
            'backupid' => $restore_controller->get_restoreid(),
            'item' => $item->to_array(),
            'course_id' => $restore_controller->get_courseid(),
            'backup_settings' => $settings
        ]);
        $asynctask->set_userid($restore_controller->get_userid());
        \core\task\manager::queue_adhoc_task($asynctask);

        return $asynctask;
    }
}
