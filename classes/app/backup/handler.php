<?php

namespace block_sharing_cart\app\backup;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use block_sharing_cart\app\factory as base_factory;
use block_sharing_cart\app\item\entity;
use block_sharing_cart\task\asynchronous_backup_task;

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

class handler
{
    private base_factory $base_factory;

    public function __construct(base_factory $base_factory)
    {
        $this->base_factory = $base_factory;
    }

    private function get_backup_info(\stored_file $file): object
    {
        /**
         * @var \file_storage $fs
         */
        $fs = get_file_storage();
        $file_path = $fs->get_file_system()->get_local_path_from_storedfile($file);

        /** @var object $info */
        $info = \backup_general_helper::get_backup_information_from_mbz($file_path);

        return $info;
    }

    public function backup_course_module(
        int $course_module_id,
        entity $root_item,
        array $settings = []
    ): asynchronous_backup_task {
        global $USER;

        $course_id = $this->base_factory->moodle()->db()->get_record(
            'course_modules',
            ['id' =>  $course_module_id],
            'course',
            MUST_EXIST
        )->course;

        $backup_controller = $this->base_factory->backup()->backup_controller(
            \backup::TYPE_1COURSE,
            $course_id,
            $USER->id
        );

        return $this->queue_async_backup($backup_controller, $root_item, $settings);
    }

    public function backup_section(int $section_id, entity $root_item, array $settings = []): asynchronous_backup_task
    {
        global $USER;

        $course_id = $this->base_factory->moodle()->db()->get_record(
            'course_sections',
            ['id' =>  $section_id],
            'course',
            MUST_EXIST
        )->course;

        $backup_controller = $this->base_factory->backup()->backup_controller(
            \backup::TYPE_1COURSE,
            $course_id,
            $USER->id
        );

        return $this->queue_async_backup($backup_controller, $root_item, $settings);
    }

    public function get_backup_course_info(\stored_file $file): array
    {
        $info = $this->get_backup_info($file);

        return [
            'id' => $info->original_course_id,
            'fullname' => $info->original_course_fullname
        ];
    }
    public function get_backup_item_tree(\stored_file $file): array
    {
        $tree = [];

        $info = $this->get_backup_info($file);

        foreach ($info->sections as $section) {
            $tree[$section->sectionid] = (object)[
                'sectionid' => $section->sectionid,
                'title' => $section->title,
                'activities' => []
            ];
        }
        foreach ($info->activities as $activity) {
            $tree[$activity->sectionid]->activities[$activity->moduleid] = (object)[
                'moduleid' => $activity->moduleid,
                'sectionid' => $activity->sectionid,
                'modulename' => $activity->modulename,
                'title' => $activity->title
            ];
        }

        return $tree;
    }

    private function queue_async_backup(
        \backup_controller $backup_controller,
        entity $root_item,
        array $settings = []
    ): asynchronous_backup_task {
        $asynctask = new asynchronous_backup_task();
        $asynctask->set_custom_data([
            'backupid' => $backup_controller->get_backupid(),
            'item' => $root_item->to_array(),
            'backup_settings' => $settings
        ]);
        $asynctask->set_userid($backup_controller->get_userid());
        $task_id = \core\task\manager::queue_adhoc_task($asynctask);

        $asynctask->set_id($task_id);

        return $asynctask;
    }
}
