<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace block_sharing_cart\app\item;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

use block_sharing_cart\app\collection;

global $CFG;
require_once($CFG->dirroot . '/course/format/lib.php');

/**
 * repository class
 *
 * @package   block_sharing_cart
 * @copyright moxis
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository extends \block_sharing_cart\app\repository
{
    /**
     * get_table
     *
     * @return string
     */
    public function get_table(): string {
        return 'block_sharing_cart_items';
    }

    /**
     * map_record_to_entity
     *
     * @param object $record
     * @return entity
     */
    public function map_record_to_entity(object $record): entity {
        return $this->basefactory->item()->entity($record);
    }

    /**
     * get_by_user_id
     *
     * @param int $userid
     * @return collection
     */
    public function get_by_user_id(int $userid): collection {
        return $this->map_records_to_collection_of_entities(
            $this->db->get_records($this->get_table(), ['user_id' => $userid], 'sortorder ASC, id DESC')
        );
    }

    /**
     * get_by_file_id
     *
     * @param int $fileid
     * @return ?entity
     */
    public function get_by_file_id(int $fileid): ?entity {
        $record = $this->db->get_record($this->get_table(), ['file_id' => $fileid]);

        return $record ? $this->map_record_to_entity(
            $record
        ) : null;
    }

    /**
     * get_by_parent_item_id
     *
     * @param ?int $parentitemid
     * @return collection
     */
    public function get_by_parent_item_id(?int $parentitemid): collection {
        return $this->map_records_to_collection_of_entities(
            $this->db->get_records($this->get_table(), ['parent_item_id' => $parentitemid], 'sortorder ASC, id ASC')
        );
    }

    /**
     * delete_by_id
     *
     * @param int $id
     * @return bool
     */
    public function delete_by_id(int $id): bool {
        $fs = get_file_storage();
        if (!$fs) {
            return false;
        }

        $childitems = $this->get_by_parent_item_id($id);
        foreach ($childitems as $childitem) {
            if (!$this->delete_by_id($childitem->get_id())) {
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

    /**
     * insert_activity
     *
     * @param int $coursemoduleid
     * @param int $userid
     * @param ?int $parentitemid
     * @param int $status
     * @return entity
     */
    public function insert_activity(int $coursemoduleid, int $userid, ?int $parentitemid, int $status): entity {
        $courseid = $this->db->get_field('course_modules', 'course', ['id' => $coursemoduleid], MUST_EXIST);

        // Course module info for the activity being inserted.
        $cminfo = \cm_info::create((object)['id' => $coursemoduleid, 'course' => $courseid], $userid);

        $time = time();
        $itemid = $this->insert(
            $entity = $this->basefactory->item()->entity(
                (object)[
                    'user_id' => $userid,
                    'file_id' => null,
                    'parent_item_id' => $parentitemid,
                    'old_instance_id' => $cminfo->id,
                    'type' => "mod_{$cminfo->modname}",
                    'name' => $cminfo->get_formatted_name(),
                    'status' => $status,
                    'version' => entity::CURRENT_BACKUP_VERSION,
                    'timecreated' => $time,
                    'timemodified' => $time,
                ]
            )
        );

        $entity->set_id($itemid);

        return $entity;
    }

    /**
     * insert_section
     *
     * @param object $section
     * @param int $userid
     * @param ?int $parentitemid
     * @param int $status
     * @return entity
     */
    public function insert_section(object $section, int $userid, ?int $parentitemid, int $status): entity {
        $courseformat = course_get_format($section->course);

        $entitytype = isset($section->itemid) ? entity::TYPE_MOD_SUBSECTION : entity::TYPE_SECTION;

        $time = time();
        $itemid = $this->insert(
            $entity = $this->basefactory->item()->entity(
                (object)[
                    'user_id' => $userid,
                    'file_id' => null,
                    'parent_item_id' => $parentitemid,
                    'old_instance_id' => $section->id,
                    'type' => $entitytype,
                    'name' => $courseformat->get_section_name($section),
                    'status' => $status,
                    'version' => entity::CURRENT_BACKUP_VERSION,
                    'timecreated' => $time,
                    'timemodified' => $time,
                ]
            )
        );

        $entity->set_id($itemid);

        return $entity;
    }

    /**
     * Insert a nested section item (a child section of a copied section, as declared by a nesting course format).
     * The item has no file of its own; the backup file lives on the root item.
     *
     * @param int $oldsectionid
     * @param string $name
     * @param int $userid
     * @param int $parentitemid
     * @param int $sortorder
     * @return entity
     */
    public function insert_child_section(
        int $oldsectionid,
        string $name,
        int $userid,
        int $parentitemid,
        int $sortorder
    ): entity {
        $time = time();
        $itemid = $this->insert(
            $entity = $this->basefactory->item()->entity(
                (object)[
                    'user_id' => $userid,
                    'file_id' => null,
                    'parent_item_id' => $parentitemid,
                    'old_instance_id' => $oldsectionid,
                    'type' => entity::TYPE_SECTION,
                    'name' => $name,
                    'status' => entity::STATUS_BACKEDUP,
                    'sortorder' => $sortorder,
                    'version' => entity::CURRENT_BACKUP_VERSION,
                    'timecreated' => $time,
                    'timemodified' => $time,
                ]
            )
        );

        $entity->set_id($itemid);

        return $entity;
    }

    /**
     * insert_activities
     *
     * @param array $activities
     * @param entity $parentitem item the activities are inserted under
     * @return void
     */
    private function insert_activities(array $activities, entity $parentitem): void {
        // Handle a single subsection (with possible nested activities).
        if ($parentitem->get_type() === "mod_subsection") {
            if (empty($activities)) {
                return;
            }
            foreach ($activities[array_key_first($activities)]->subsection_activities as $subsectionactivity) {
                $this->insert_activity(
                    $subsectionactivity->moduleid,
                    $parentitem->get_user_id(),
                    $parentitem->get_id(),
                    entity::STATUS_BACKEDUP
                );
            }
            return;
        }

        // Handle multiple activities.
        foreach ($activities as $activity) {
            if ($activity->modulename === "subsection") {
                $subsectionentity = $this->insert_activity(
                    $activity->moduleid,
                    $parentitem->get_user_id(),
                    $parentitem->get_id(),
                    entity::STATUS_BACKEDUP
                );
                foreach ($activity->subsection_activities as $subsectionactivity) {
                    $this->insert_activity(
                        $subsectionactivity->moduleid,
                        $parentitem->get_user_id(),
                        $subsectionentity->get_id(),
                        entity::STATUS_BACKEDUP
                    );
                }

                continue;
            }

            $this->insert_activity(
                $activity->moduleid,
                $parentitem->get_user_id(),
                $parentitem->get_id(),
                entity::STATUS_BACKEDUP
            );
        }
    }

    /**
     * Build the nested items for a copied section: its own activities under the root item, then one 'section' item
     * per descendant section (depth-first, as stored in $sectiontree) with that section's activities beneath it.
     *
     * @param array $sections output of backup\handler::get_backup_item_tree()
     * @param entity $rootitem
     * @param array $sectiontree depth-first list of {section_id, parent_section_id, sort_order}
     * @return void
     */
    private function insert_section_tree(array $sections, entity $rootitem, array $sectiontree): void {
        $rootoldsectionid = (int)$rootitem->get_old_instance_id();
        $rootsection = $sections[$rootoldsectionid] ?? $sections[array_key_first($sections)];

        $this->insert_activities($rootsection->activities, $rootitem);

        $itemsbyoldsectionid = [$rootoldsectionid => $rootitem];

        foreach ($sectiontree as $node) {
            $node = (object)$node;
            $oldsectionid = (int)$node->section_id;
            $section = $sections[$oldsectionid] ?? null;
            $parentitem = $itemsbyoldsectionid[(int)$node->parent_section_id] ?? null;

            // Not part of the backup (excluded) or its parent was not, so the branch is dropped.
            if ($section === null || $parentitem === null) {
                continue;
            }

            $sectionitem = $this->insert_child_section(
                $oldsectionid,
                (string)(!empty($node->name) ? $node->name : $section->title),
                $rootitem->get_user_id(),
                $parentitem->get_id(),
                (int)$node->sort_order
            );
            $itemsbyoldsectionid[$oldsectionid] = $sectionitem;

            $this->insert_activities($section->activities, $sectionitem);
        }
    }

    /**
     * update_sharing_cart_item_with_backup_file
     *
     * @param entity $rootitem
     * @param \stored_file $file
     * @param array $sectiontree descendant sections captured at backup time; empty for formats that do not nest
     * @return void
     */
    public function update_sharing_cart_item_with_backup_file(
        entity $rootitem,
        \stored_file $file,
        array $sectiontree = []
    ): void {
        foreach ($this->get_by_parent_item_id($rootitem->get_id()) as $childitem) {
            $this->delete_by_id($childitem->get_id());
        }

        $rootitem->set_status(entity::STATUS_BACKEDUP);
        $rootitem->set_file_id($file->get_id());
        $rootitem->set_timemodified(time());

        $courseinfo = $this->basefactory->backup()->handler()->get_backup_course_info($file);
        $rootitem->set_original_course_fullname($courseinfo['fullname'] ?? null);

        $this->update($rootitem);

        $sections = $this->basefactory->backup()->handler()->get_backup_item_tree($file, $sectiontree);

        if (isset($sections['lone_activity'])) {
            return;
        }
        if (empty($sections)) {
            throw new \Exception("Backup file was empty.");
        }

        if ($rootitem->is_subsection()) {
            $this->insert_activities($sections[array_key_first($sections)]->activities, $rootitem);
            return;
        }

        $this->insert_section_tree($sections, $rootitem, $sectiontree);
    }

    /**
     * get_recursively_by_parent_id
     *
     * @param int $itemid
     * @param ?collection $items
     * @return collection
     */
    public function get_recursively_by_parent_id(int $itemid, ?collection $items = null): collection {
        if (!$items) {
            $items = $this->basefactory->collection();

            $rootitem = $this->get_by_id($itemid);
            if (!$rootitem) {
                return $items;
            }

            $items->add($rootitem);
        }

        $children = $this->get_by_parent_item_id($itemid);
        foreach ($children as $child) {
            $items->add($child);
        }

        foreach ($children as $child) {
            $this->get_recursively_by_parent_id($child->get_id(), $items);
        }

        return $items;
    }

    /**
     * get_parent_item_recursively_by_item
     *
     * @param entity $item
     * @return entity
     */
    public function get_parent_item_recursively_by_item(entity $item): entity {
        if ($item->get_parent_item_id()) {
            return $this->get_parent_item_recursively_by_item(
                $this->get_by_id(
                    $item->get_parent_item_id()
                )
            );
        }

        return $item;
    }

    /**
     * Get the stored backup file for a sharing cart item.
     *
     * @param entity $item
     * @return \stored_file|null
     */
    public function get_stored_file_by_item(entity $item): ?\stored_file {
        // File storage instance.
        $fs = get_file_storage();

        if (
            $itemfile = array_values(
                $fs->get_area_files(
                    \core\context\user::instance($item->get_user_id())->id,
                    'block_sharing_cart',
                    'backup',
                    $item->get_id(),
                    includedirs: false,
                    limitnum: 1
                )
            )[0] ?? null
        ) {
            return $itemfile;
        }

        return array_values(
            $fs->get_area_files(
                \core\context\user::instance($item->get_user_id())->id,
                'block_sharing_cart',
                'backup',
                $this->get_parent_item_recursively_by_item($item)->get_id(),
                includedirs: false,
                limitnum: 1
            )
        )[0] ?? null;
    }

    /**
     * get_count
     *
     * @return int
     */
    public function get_count(): int {
        return $this->db->count_records($this->get_table());
    }
}
