<?php

namespace block_sharing_cart\app\item;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use block_sharing_cart\app\collection;

global $CFG;
require_once($CFG->dirroot . '/course/format/lib.php');

class repository extends \block_sharing_cart\app\repository
{
    public function get_table(): string
    {
        return 'block_sharing_cart_items';
    }

    public function map_record_to_entity(object $record): entity
    {
        return $this->base_factory->item()->entity($record);
    }

    public function get_by_id(int $id): false|entity
    {
        return parent::get_by_id($id);
    }

    public function get_by_user_id(int $user_id): collection
    {
        return $this->map_records_to_collection_of_entities(
            $this->db->get_records($this->get_table(), ['user_id' => $user_id])
        );
    }

    public function get_by_parent_item_id(?int $parent_item_id): collection
    {
        return $this->map_records_to_collection_of_entities(
            $this->db->get_records($this->get_table(), ['parent_item_id' => $parent_item_id])
        );
    }

    public function delete_by_id(int $id): bool
    {
        $fs = get_file_storage();
        if (!$fs) {
            return false;
        }

        $child_items = $this->get_by_parent_item_id($id);
        foreach ($child_items as $child_item) {
            if (!$this->delete_by_id($child_item->get_id())) {
                return false;
            }
        }

        $item = $this->get_by_id($id);
        if (!$item) {
            return true;
        }

        if ($item->get_file_id() && $file = $fs->get_file_by_id($item->get_file_id())) {
            $file->delete();
        }

        return parent::delete_by_id($id);
    }

    public function insert_activity(int $course_module_id, int $user_id, ?int $parent_item_id, int $status): entity
    {
        $course_id = $this->db->get_field('course_modules', 'course', ['id' => $course_module_id], MUST_EXIST);

        /**
         * @var \cm_info $cm_info
         */
        $cm_info = \cm_info::create((object)['id' => $course_module_id, 'course' => $course_id], $user_id);

        $time = time();
        $item_id = $this->insert(
            $entity = $this->base_factory->item()->entity(
                (object)[
                    'user_id' => $user_id,
                    'file_id' => null,
                    'parent_item_id' => $parent_item_id,
                    'type' => "mod_{$cm_info->modname}",
                    'name' => $cm_info->get_name(),
                    'status' => $status,
                    'timecreated' => $time,
                    'timemodified' => $time,
                ]
            )
        );

        $entity->set_id($item_id);

        return $entity;
    }

    public function insert_section(int $section_id, int $user_id, ?int $parent_item_id, int $status): entity
    {
        $section = $this->db->get_record('course_sections', ['id' => $section_id], strictness: MUST_EXIST);

        $course_format = course_get_format($section->course);

        $time = time();
        $item_id = $this->insert(
            $entity = $this->base_factory->item()->entity(
                (object)[
                    'user_id' => $user_id,
                    'file_id' => null,
                    'parent_item_id' => $parent_item_id,
                    'type' => entity::TYPE_SECTION,
                    'name' => $course_format->get_section_name($section),
                    'status' => $status,
                    'timecreated' => $time,
                    'timemodified' => $time,
                ]
            )
        );

        $entity->set_id($item_id);

        return $entity;
    }

    public function insert_course(int $course_id, int $user_id, ?int $parent_item_id, int $status): entity
    {
        $course = get_course($course_id);

        $time = time();
        $item_id = $this->insert(
            $entity = $this->base_factory->item()->entity(
                (object)[
                    'user_id' => $user_id,
                    'file_id' => null,
                    'parent_item_id' => $parent_item_id,
                    'type' => entity::TYPE_COURSE,
                    'name' => $course->fullname,
                    'status' => $status,
                    'timecreated' => $time,
                    'timemodified' => $time,
                ]
            )
        );

        $entity->set_id($item_id);

        return $entity;
    }

    private function insert_activities(array $activities, entity $root_item): void
    {
        foreach ($activities as $activity) {
            $this->insert_activity(
                $activity->moduleid,
                $root_item->get_user_id(),
                $root_item->get_id(),
                entity::STATUS_BACKEDUP
            );
        }
    }

    private function insert_sections(array $sections, entity $root_item): void
    {
        foreach ($sections as $section) {
            $section_item = $this->insert_section(
                $section->sectionid,
                $root_item->get_user_id(),
                $root_item->get_id(),
                entity::STATUS_BACKEDUP
            );

            $this->insert_activities($section->activities, $section_item);
        }
    }

    public function update_sharing_cart_item_with_backup_file(entity $root_item, \stored_file $file): void
    {
        $this->db->delete_records($this->get_table(), ['parent_item_id' => $root_item->get_id()]);

        $root_item->set_status(entity::STATUS_BACKEDUP);
        $root_item->set_file_id($file->get_id());
        $root_item->set_timemodified(time());

        $this->update($root_item);

        switch ($root_item->get_type()) {
            case entity::TYPE_COURSE:
                $tree = $this->base_factory->backup()->handler()->get_backup_item_tree($file);
                $sections = array_values($tree);
                $this->insert_sections($sections, $root_item);
                break;
            case entity::TYPE_SECTION:
                $tree = $this->base_factory->backup()->handler()->get_backup_item_tree($file);
                $section = array_values($tree)[0];
                $this->insert_activities($section->activities, $root_item);
                break;
            default:
                return;
        }
    }

    public function get_recursively_by_parent_id(int $item_id, ?collection $items = null): collection
    {
        if (!$items) {
            $items = $this->base_factory->collection();

            $root_item = $this->get_by_id($item_id);
            if (!$root_item) {
                return $items;
            }

            $items->add($root_item);
        }

        $children = $this->get_by_parent_item_id($item_id);
        foreach ($children as $child) {
            $items->add($child);
        }

        foreach ($children as $child) {
            $this->get_recursively_by_parent_id($child->get_id(), $items);
        }

        return $items;
    }
}