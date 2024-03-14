<?php

namespace block_sharing_cart\task;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

use block_sharing_cart\app\factory;

class asynchronous_backup_task extends \core\task\asynchronous_backup_task {
    public function execute(): void {
        parent::execute();

        $base_factory = factory::make();

        $custom_data = $this->get_custom_data();

        $backup_item_instance_id = $custom_data->block_sharing_cart_backup_item_instance_id;
        $root_item = $custom_data->block_sharing_cart_root_item;

        $file = $this->get_backup_file($root_item->type, $backup_item_instance_id);
        if(!$file) {
            // File can't be found. What to do?!
            throw new \Exception('Backup file not found');
        }

        $sharing_cart_file = $this->copy_backup_file_to_sharing_cart_filearea($file, $root_item->user_id, $root_item->id);

        $base_factory->item()->repository()->update_sharing_cart_item_with_backup_file($root_item->id, $sharing_cart_file);
    }

    private function copy_backup_file_to_sharing_cart_filearea(\stored_file $file, int $user_id, int $item_id): \stored_file {
        /**
         * @var \file_storage $fs
         */
        $fs = get_file_storage();

        return $fs->create_file_from_storedfile([
            'contextid' => \context_user::instance($user_id)->id,
            'component' => 'block_sharing_cart',
            'filearea' => 'backup',
            'itemid' => $item_id,
            'filepath' => '/',
            'filename' => $file->get_filename(),
        ], $file);
    }
    private function get_backup_file(string $item_type, int $backup_item_instance_id): ?\stored_file {
        /**
         * @var \file_storage $fs
         */
        $fs = get_file_storage();

        $file_record = [
            'component' => 'backup',
            'filearea' => $this->get_backup_filearea_by_item($item_type),
            'contextid' => $this->get_backup_context_by_item($item_type, $backup_item_instance_id)->id,
            'itemid' => $this->get_backup_itemid($item_type, $backup_item_instance_id),
        ];

        mtrace('Fetching backup file using the following:');
        mtrace("... component: {$file_record['component']}");
        mtrace("... filearea: {$file_record['filearea']}");
        mtrace("... contextid: {$file_record['contextid']}");
        mtrace("... itemid: {$file_record['itemid']}");

        return array_values(
            $fs->get_area_files(
                $file_record['contextid'],
                $file_record['component'],
                $file_record['filearea'],
                $file_record['itemid'],
                includedirs: false,
                limitnum: 1
            )
        )[0] ?? null;
    }
    private function get_backup_filearea_by_item(string $item_type): string {
        if ($item_type !== 'course' && $item_type !== 'section') {
            return 'activity';
        }

        return $item_type;
    }
    private function get_backup_context_by_item(string $item_type, int $instance_id): \context {
        global $DB;

        return match ($item_type) {
            'course' => \context_course::instance($instance_id),
            'section' => \context_course::instance(
                $DB->get_field('course_sections', 'course', ['id' => $instance_id], MUST_EXIST)
            ),
            default => \context_module::instance($instance_id),
        };
    }
    private function get_backup_itemid(string $item_type, int $instance_id): int {
        return match ($item_type) {
            'section' => $instance_id,
            default => 0,
        };
    }
}