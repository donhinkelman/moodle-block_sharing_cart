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

namespace block_sharing_cart\app\backup;

use block_sharing_cart\app\factory as basefactory;
use block_sharing_cart\app\item\entity;
use block_sharing_cart\event\backup_course_module;
use block_sharing_cart\event\backup_section;
use block_sharing_cart\hook\backup\resolve_section_tree;
use block_sharing_cart\task\asynchronous_backup_task;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/course/format/lib.php');

/**
 * Backup handler for the Sharing Cart block.
 *
 * @package   block_sharing_cart
 * @copyright moxis
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class handler
{
    /** @var basefactory $basefactory */
    private basefactory $basefactory;

    /**
     * __construct
     *
     * @param basefactory $basefactory
     */
    public function __construct(basefactory $basefactory) {
        $this->basefactory = $basefactory;
    }

    /**
     * get_backup_info
     *
     * @param \stored_file $file
     * @return object
     */
    private function get_backup_info(\stored_file $file): object {
        // File storage instance.
        $fs = get_file_storage();
        $filepath = $fs->get_file_system()->get_local_path_from_storedfile($file, true);

        // Backup information from the MBZ file.
        $info = \backup_general_helper::get_backup_information_from_mbz($filepath);

        return $info;
    }

    /**
     * backup_course_module
     *
     * @param int $coursemoduleid
     * @param entity $rootitem
     * @param array $settings
     * @return asynchronous_backup_task
     */
    public function backup_course_module(
        int $coursemoduleid,
        entity $rootitem,
        array $settings = []
    ): asynchronous_backup_task {
        global $USER;

        $record = $this->basefactory->moodle()->db()->get_record(
            'course_modules',
            ['id' => $coursemoduleid],
            'id, course',
            MUST_EXIST
        );

        $backupcontroller = $this->basefactory->backup()->backup_controller(
            \backup::TYPE_1ACTIVITY,
            $coursemoduleid,
            $USER->id
        );

        backup_course_module::create_by_course_module(
            $record->course,
            $coursemoduleid,
            $USER->id
        )->trigger();

        return $this->queue_async_backup($backupcontroller, $rootitem, $settings);
    }

    /**
     * backup_section
     *
     * @param object $section course_sections record
     * @param entity $rootitem
     * @param array $settings
     * @param array|null $sectiontree descendants as returned by resolve_section_tree(); resolved here when null
     * @return array
     */
    public function backup_section(
        object $section,
        entity $rootitem,
        array $settings = [],
        ?array $sectiontree = null
    ): array {
        global $USER;

        $courseid = $this->basefactory->moodle()->db()->get_record(
            'course_sections',
            ['id' => $section->id],
            'course',
            MUST_EXIST
        )->course;

        // The descendant sections of the copied section, as declared by the course format (nested formats only).
        $settings['section_tree'] = $sectiontree ?? $this->resolve_section_tree((int)$courseid, (int)$section->id);

        $backupcontroller = $this->basefactory->backup()->backup_controller(
            \backup::TYPE_1COURSE,
            $courseid,
            $USER->id
        );

        $task = $this->queue_async_backup($backupcontroller, $rootitem, $settings);

        backup_section::create_by_section(
            $courseid,
            $section->id,
            $USER->id
        )->trigger();

        return ["task" => $task, "controller" => $backupcontroller];
    }

    /**
     * Depth-first list of {section_id, parent_section_id, sort_order, name} describing the descendants of a section,
     * as declared by the course format through the resolve_section_tree hook. Empty for formats that do not nest.
     *
     * @param int $courseid
     * @param int $sectionid
     * @return array
     */
    public function resolve_section_tree(int $courseid, int $sectionid): array {
        $hook = new resolve_section_tree($courseid, $sectionid);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);

        $tree = $hook->get_tree();
        if (empty($tree)) {
            return [];
        }

        // Record the display names now; the backup manifest only carries section numbers for unnamed sections.
        $courseformat = course_get_format($courseid);
        $sections = $this->basefactory->moodle()->db()->get_records_list(
            'course_sections',
            'id',
            array_map(static fn(object $node): int => $node->section_id, $tree)
        );

        return array_map(static function (object $node) use ($courseformat, $sections): array {
            $section = $sections[$node->section_id] ?? null;

            $name = '';
            if ($section) {
                $name = (string)$section->name !== ''
                    ? format_string($section->name)
                    : $courseformat->get_section_name($section);
            }

            return [
                'section_id' => $node->section_id,
                'parent_section_id' => $node->parent_section_id,
                'sort_order' => $node->sort_order,
                'name' => $name,
            ];
        }, $tree);
    }

    /**
     * Whether there is anything to copy: the section itself or one of its descendants holds a course module. A
     * structural parent that only contains subsections is copyable through its descendants.
     *
     * @param object $section course_sections record with at least 'sequence'
     * @param array $sectiontree descendants as returned by resolve_section_tree()
     * @return bool
     */
    public function section_has_content(object $section, array $sectiontree): bool {
        if (trim((string)($section->sequence ?? '')) !== '') {
            return true;
        }

        $sectionids = array_map(static fn(array|object $node): int => (int)((object)$node)->section_id, $sectiontree);
        if (empty($sectionids)) {
            return false;
        }

        $descendants = $this->basefactory->moodle()->db()->get_records_list(
            'course_sections',
            'id',
            $sectionids,
            '',
            'id, sequence'
        );
        foreach ($descendants as $descendant) {
            if (trim((string)$descendant->sequence) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * get_backup_course_info
     *
     * @param \stored_file $file
     * @return array
     */
    public function get_backup_course_info(\stored_file $file): array {
        $info = $this->get_backup_info($file);

        return [
            'id' => $info->original_course_id,
            'fullname' => $info->original_course_fullname,
        ];
    }

    /**
     * Sections contained in the backup, keyed by their original section id and ordered root first, then the stored
     * section tree. Core subsections (mod_subsection) are attached to the section owning their parent module and
     * appear in that section's activities with a 'subsection_activities' list of their own.
     *
     * @param \stored_file $file
     * @param array $sectiontree depth-first list of {section_id, parent_section_id, sort_order} captured at backup
     *                            time; empty for backups of a single section.
     * @return array
     */
    public function get_backup_item_tree(\stored_file $file, array $sectiontree = []): array {
        $info = $this->get_backup_info($file);

        $sections = [];
        $subsections = [];

        foreach ($info->sections as $section) {
            if (isset($section->modname) && $section->modname === 'subsection') {
                $subsections[$section->sectionid] = (object)[
                    'moduleid' => $section->parentcmid,
                    'sectionid' => $section->sectionid,
                    'title' => $section->title,
                    'modulename' => $section->modname,
                    'subsection_activities' => [],
                ];
                continue;
            }

            $sections[$section->sectionid] = (object)[
                'sectionid' => $section->sectionid,
                'title' => $section->title,
                'modulename' => $section->modname,
                'parent_section_id' => 0,
                'sort_order' => 0,
                'activities' => [],
            ];
        }

        // Order the sections root first, then by the stored tree, and record the parent relations.
        if (!empty($sectiontree) && !empty($sections)) {
            $ordered = [];
            foreach ($sections as $sectionid => $section) {
                $intree = false;
                foreach ($sectiontree as $node) {
                    if ((int)((object)$node)->section_id === (int)$sectionid) {
                        $intree = true;
                        break;
                    }
                }
                if (!$intree) {
                    $ordered[$sectionid] = $section;
                }
            }
            foreach ($sectiontree as $node) {
                $node = (object)$node;
                if (!isset($sections[$node->section_id])) {
                    continue;
                }
                $sections[$node->section_id]->parent_section_id = (int)$node->parent_section_id;
                $sections[$node->section_id]->sort_order = (int)$node->sort_order;
                $ordered[$node->section_id] = $sections[$node->section_id];
            }
            $sections = $ordered;
        }

        // Attach core subsections to the section that owns their parent module.
        $modulesections = [];
        foreach ($info->activities as $activity) {
            $modulesections[$activity->moduleid] = $activity->sectionid;
        }
        foreach ($subsections as $subsection) {
            $ownersectionid = $modulesections[$subsection->moduleid] ?? null;
            if ($ownersectionid !== null && isset($sections[$ownersectionid])) {
                $sections[$ownersectionid]->activities['sub_' . $subsection->sectionid] = $subsection;
            } else if (!empty($sections)) {
                $sections[array_key_first($sections)]->activities['sub_' . $subsection->sectionid] = $subsection;
            }
        }

        if (empty($sections)) {
            if (empty($subsections)) {
                // If no sections and no subsections are supplied, it's a single activity. Make artificial activities array.
                $sections["lone_activity"] = (object) ['activities' => []];
            } else {
                $sections = $subsections;
            }
        }

        foreach ($info->activities as $activity) {
            if (isset($activity->modulename) && $activity->modulename === 'subsection') {
                continue;
            }

            // Activities that live in a section.
            if (isset($sections[$activity->sectionid])) {
                $sections[$activity->sectionid]->activities[$activity->moduleid] = (object)[
                    'moduleid' => $activity->moduleid,
                    'sectionid' => $activity->sectionid,
                    'modulename' => $activity->modulename,
                    'title' => $activity->title,
                    'activities' => [],
                ];
                continue;
            }

            // Activities that live under a core subsection.
            if (isset($subsections[$activity->sectionid])) {
                $subsections[$activity->sectionid]->subsection_activities[] = $activity;
                continue;
            }

            if (isset($sections["lone_activity"])) {
                $sections["lone_activity"]->activities[] = $activity;
            }
        }

        return $sections;
    }

    /**
     * queue_async_backup
     *
     * @param \backup_controller $backupcontroller
     * @param entity $rootitem
     * @param array $settings
     * @return asynchronous_backup_task
     */
    private function queue_async_backup(
        \backup_controller $backupcontroller,
        entity $rootitem,
        array $settings = []
    ): asynchronous_backup_task {
        $asynctask = new asynchronous_backup_task();
        $asynctask->set_custom_data([
            'backupid' => $backupcontroller->get_backupid(),
            'item' => $rootitem->to_array(),
            'backup_settings' => $settings,
        ]);
        $asynctask->set_userid($backupcontroller->get_userid());
        $taskid = \core\task\manager::queue_adhoc_task($asynctask);

        $asynctask->set_id($taskid);

        return $asynctask;
    }
}
