<?php

namespace block_sharing_cart\app\item;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

use block_sharing_cart\app\collection;

global $CFG;
require_once($CFG->dirroot . '/course/format/lib.php');

class repository extends \block_sharing_cart\app\repository {
    public function get_table(): string {
        return 'block_sharing_cart_items';
    }

    public function get_by_user_id(int $user_id): collection {
        return $this->base_factory->collection(
            $this->db->get_records($this->get_table(), ['user_id' => $user_id])
        );
    }

    public function insert_activity(int $course_module_id, int $user_id, ?int $parent_item_id, int $status): int {
        $course_id = $this->db->get_field('course_modules', 'course', ['id' => $course_module_id], MUST_EXIST);

        /**
         * @var \cm_info $cm_info
         */
        $cm_info = \cm_info::create((object)['id' => $course_module_id, 'course' => $course_id], $user_id);

        $time = time();
        return $this->insert((object)[
            'user_id' => $user_id,
            'file_id' => null,
            'parent_item_id' => $parent_item_id,
            'type' => "mod_{$cm_info->modname}",
            'name' => $cm_info->get_name(),
            'status' => $status,
            'timecreated' => $time,
            'timemodified' => $time,
        ]);
    }

    public function insert_section(int $section_id, int $user_id, ?int $parent_item_id, int $status): int {
        $section = $this->db->get_record('course_sections', ['id' => $section_id], strictness: MUST_EXIST);

        $course_format = course_get_format($section->course);

        $time = time();
        return $this->insert((object)[
            'user_id' => $user_id,
            'file_id' => null,
            'parent_item_id' => $parent_item_id,
            'type' => 'section',
            'name' => $course_format->get_section_name($section),
            'status' => $status,
            'timecreated' => $time,
            'timemodified' => $time,
        ]);
    }

    private function insert_activities(array $activities, int $root_item_id, int $user_id): void {
        foreach ($activities as $activity) {
            $this->insert_activity($activity->moduleid, $user_id, $root_item_id, 1);
        }
    }

    private function insert_sections(array $sections, int $root_item_id, int $user_id): void {
        foreach ($sections as $section) {
            $section_item_id = $this->insert_section($section->sectionid, $user_id, $root_item_id, 1);

            $this->insert_activities($section->activities, $section_item_id, $user_id);
        }
    }

    public function update_sharing_cart_item_with_backup_file(int $root_item_id, \stored_file $file): void {
        $this->db->delete_records($this->get_table(), ['parent_item_id' => $root_item_id]);

        $root_item = $this->get_by_id($root_item_id);
        $root_item->status = 1;
        $root_item->file_id = $file->get_id();
        $root_item->timemodified = time();

        $this->update($root_item);

        switch ($root_item->type) {
            case 'course':
                $tree = $this->base_factory->backup()->handler()->get_backup_item_tree($file);
                $sections = array_values($tree);
                $this->insert_sections($sections, $root_item_id, $root_item->user_id);
                break;
            case 'section':
                $tree = $this->base_factory->backup()->handler()->get_backup_item_tree($file);
                $section = array_values($tree)[0];
                $this->insert_activities($section->activities, $root_item_id, $root_item->user_id);
                break;
            default:
                return;
        }
    }
}