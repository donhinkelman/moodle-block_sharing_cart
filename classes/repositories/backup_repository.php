<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *  Sharing Cart
 *
 * @package    block_sharing_cart
 * @copyright  2017 (C) VERSION2, INC.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace block_sharing_cart\repositories;


use backup;
use backup_controller;
use backup_plan;
use base_setting;
use block_sharing_cart\event\backup_activity_created;
use block_sharing_cart\event\backup_activity_started;
use block_sharing_cart\event\section_backedup;
use block_sharing_cart\exceptions\no_backup_support_exception;
use block_sharing_cart\record;
use block_sharing_cart\storage;
use block_sharing_cart\task\async_backup_course_module;
use cm_info;
use coding_exception;
use context_course;
use moodle_database;
use section_info;
use stored_file;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

global $CFG;
require_once $CFG->dirroot . '/backup/util/includes/backup_includes.php';
require_once $CFG->dirroot . '/backup/util/includes/restore_includes.php';
require_once $CFG->dirroot . '/blocks/sharing_cart/backup/util/helper/restore_fix_missings_helper.php';


class backup_repository
{
    private const SHARING_CART_TABLE = 'block_sharing_cart';

    private static array $default_settings = [
        'role_assignments' => false,
        'activities' => true,
        'blocks' => false,
        'filters' => false,
        'comments' => false,
        'calendarevents' => false,
        'userscompletion' => false,
        'logs' => false,
        'grade_histories' => false,
        'users' => false,
        'anonymize' => false,
        'badges' => false
    ];

    private moodle_database $db;
    private course_module_repository $cm_repo;

    public function __construct(course_module_repository $cm_repo)
    {
        global $DB;
        $this->db = $DB;
        $this->cm_repo = $cm_repo;
    }

    public static function create(): self
    {
        return new self(
            new course_module_repository()
        );
    }

    public static function create_backup_filename(
        cm_info $cm,
        ?int $current_time = null
    ): string
    {
        $current_time ??= time();
        $json = json_encode([
            'id' => $cm->id,
            'course' => $cm->course,
            'section' => $cm->section,
            'module' => $cm->module,
            'name' => $cm->name,
            'added' => $cm->added,
        ]);
        $checksum = hash('md5', $json);
        return "block_sharing_cart-{$cm->id}-{$checksum}-{$current_time}.mbz";
    }

    public function backup(
        int $user_id,
        int $cm_id,
        int $course_id,
        int $section_id = 0,
        ?object $sharing_cart_record = null,
        ?backup_options $options = null
    ): void
    {
        $cm = $this->cm_repo->get_course_module($cm_id, $course_id);
        $this->backup_course_module($cm, $user_id, $section_id, $sharing_cart_record, $options);
    }

    public function backup_async(
        int $user_id,
        int $cm_id,
        int $course_id,
        int $sharing_cart_section_id = 0,
        ?backup_options $options = null
    ): void
    {
        $user_id ??= $this->get_user()->id;
        $cm = $this->cm_repo->get_course_module($cm_id, $course_id);

        $this->validate_backup_capability($cm, $user_id);

        $options ??= new backup_options();

        $record = $this->get_sharing_cart_record($user_id, $cm, $sharing_cart_section_id);
        $record->id = $this->db->insert_record(record::TABLE, $record);
        $this->backup_course_module_async($cm, $record, $options);
    }

    public function backup_section_async(
        int $user_id,
        int $course_id,
        int $section_id,
        ?string $name = null,
        ?backup_options $options = null
    ): void
    {
        $mod_info = get_fast_modinfo($course_id);
        $section = $mod_info->get_section_info_by_id($section_id, MUST_EXIST);
        if (!$section) {
            throw new coding_exception('Section not found');
        }

        $record = (object)[
            'id' => 0,
            'name' => $name ?? get_section_name($section->course, $section->section),
            'summary' => $section->summary,
            'summaryformat' => $section->summaryformat,
            'availability' => $section->availability,
        ];
        $record->id = $this->db->insert_record('block_sharing_cart_sections', $record);

        $cm_ids = $mod_info->sections[$section->section];
        if (empty($cm_ids)) {
            $this->create_empty_section(
                $user_id,
                $course_id,
                $record->id
            );
            section_backedup::create([
                'context' => context_course::instance($course_id),
                'userid' => $user_id,
                'relateduserid' => $user_id,
                'objectid' => $record->id,
                'other' => $section->id
            ])->trigger();
            return;
        }

        $this->validate_section_backup_capability($section);

        $folder = $this->get_folder_name($user_id, $record->name);
        foreach ($cm_ids as $cm_id) {
            if (!isset($mod_info->cms[$cm_id])) {
                continue;
            }

            $cm = $mod_info->cms[$cm_id];
            $sharing_cart_record = $this->get_sharing_cart_record(
                $user_id,
                $cm,
                $record->id,
                null,
                $folder
            );
            $this->backup_course_module_async($cm, $sharing_cart_record, $options);
        }

        section_backedup::create([
            'context' => context_course::instance($course_id),
            'userid' => $user_id,
            'relateduserid' => $user_id,
            'objectid' => $record->id,
            'other' => $section->id
        ])->trigger();
    }

    private function backup_course_module_async(
        cm_info $cm,
        object $sharing_cart_record,
        ?backup_options $options = null
    ): void
    {
        $options ??= new backup_options();
        if (!isset($sharing_cart_record->id) || $sharing_cart_record->id < 1) {
            $sharing_cart_record->id = $this->db->insert_record(record::TABLE, $sharing_cart_record);
        }
        async_backup_course_module::add_to_queue_by_course_module(
            $cm->id,
            $cm->course,
            $sharing_cart_record,
            $options
        );
    }

    private function backup_course_module(
        cm_info $cm,
        int $user_id,
        int $section_id = 0,
        ?object $sharing_cart_record = null,
        ?backup_options $options = null
    ): void
    {
        $this->validate_backup_capability($cm, $user_id);

        $options ??= new backup_options();

        backup_activity_started::create_by_course_module_id(
            $cm->course,
            $cm->id
        )->trigger();

        $controller = $this->get_backup_controller($user_id, $cm);
        $plan = $controller->get_plan();

        $this->set_backup_plan_settings($cm, $plan, $options->get_settings());

        $controller->set_status(backup::STATUS_AWAITING);
        $controller->execute_plan();

        $temp_file = $this->get_backup_file($controller);
        if (!$temp_file) {
            throw new coding_exception('Backup file already has been moved');
        }

        $backup_file = $this->move_backup_file($user_id, $temp_file);

        $sharing_cart_record ??= (object)[
            'userid' => $user_id,
            'modname' => $cm->modname,
            'modicon' => $cm->icon,
            'modtext' => $this->cm_repo->get_title($cm),
            'tree' => '',
            'weight' => $this->get_next_weight_by_path($user_id, ''),
            'course' => $cm->course,
            'section' => $section_id
        ];

        $sharing_cart_record->ctime = time();
        $sharing_cart_record->filename = $backup_file->get_filename();
        $sharing_cart_record->fileid = $backup_file->get_id();

        if (!isset($sharing_cart_record->id) || $sharing_cart_record->id < 1) {
            $sharing_cart_record->id = $this->db->insert_record(record::TABLE, $sharing_cart_record);
        }
        else {
            $this->db->update_record(record::TABLE, $sharing_cart_record);
        }

        backup_activity_created::create_by_course_module_id(
            $cm->course,
            $cm->id,
            $sharing_cart_record->id
        )->trigger();
    }

    private function validate_section_backup_capability(section_info $section): void
    {
        $cm_ids = $section->modinfo->sections[$section->section];
        if (empty($cm_ids)) {
            return;
        }
        foreach ($cm_ids as $cm_id) {
            if (!isset($section->modinfo->cms[$cm_id])) {
                continue;
            }
            $cm = $section->modinfo->cms[$cm_id];
            $this->validate_backup_capability($cm);
        }
    }

    private function get_folder_name(
        int $user_id,
        string $section_name
    ): string
    {
        $folder = $this->cleanup_folder_name($section_name);
        if ($this->has_folder($user_id, $folder)) {
            return $this->increase_folder_number($user_id, $folder);
        }
        return $folder;
    }

    private function increase_folder_number(
        int $user_id,
        string $folder_name
    ): string
    {
        $folder_like = $this->db->sql_like_escape($folder_name);
        $params = [
            'userid' => $user_id,
            'tree' => "{$folder_like} (%)"
        ];

        $tree_like = $this->db->sql_like('tree', ':tree');
        $folder_count = $this->db->count_records_select(
            record::TABLE,
            "userid = :userid AND {$tree_like}",
            $params
        );
        $folder_number = $folder_count + 1;
        return "{$folder_name} ({$folder_number})";
    }

    private function has_folder(
        int $user_id,
        string $folder_name
    ): bool
    {
        return $this->db->record_exists(self::SHARING_CART_TABLE, [
            'tree' => $folder_name,
            'userid' => $user_id
        ]);
    }

    private function get_next_weight_by_path(
        int $user_id,
        string $path
    ): int
    {
        return $this->get_highest_weight_by_path($user_id, $path) + 1;
    }

    private function get_highest_weight_by_path(
        int $user_id,
        string $path
    ): int
    {
        return (int)$this->db->get_field(
            record::TABLE,
            'MAX(weight)',
            [
                'userid' => $user_id,
                'tree' => $path
            ]
        );
    }

    private function get_sharing_cart_record(
        int $user_id,
        cm_info $cm,
        int $sharing_cart_section_id,
        ?stored_file $backup_file = null,
        ?string $folder_path = null
    ): object
    {
        $record = [
            'userid' => $user_id,
            'modname' => $cm->modname,
            'modicon' => $cm->icon,
            'modtext' => $this->cm_repo->get_title($cm),
            'ctime' => time(),
            'filename' => $backup_file ? $backup_file->get_filename() : $this->get_backup_filename($cm),
            'course' => $cm->course,
            'section' => $sharing_cart_section_id,
            'tree' => '',
            'weight' => 0,
            'fileid' => 0
        ];
        if ($folder_path !== null) {
            $record['tree'] = $folder_path;
            $record['weight'] = $this->get_next_weight_by_path($user_id, $folder_path);
        }
        return (object)$record;
    }


    private function create_empty_section(
        int $user_id,
        int $course_id,
        int $sharing_cart_section_id
    ): void
    {
        $record = (object)[
            'userid' => $user_id,
            'modname' => '',
            'modicon' => '',
            'modtext' => '',
            'ctime' => time(),
            'filename' => '',
            'tree' => '',
            'weight' => 0,
            'course' => $course_id,
            'section' => $sharing_cart_section_id,
            'fileid' => 0
        ];
        $this->db->insert_record('block_sharing_cart', $record);
    }

    private function get_section_by_id(int $course_id, int $section_id): object
    {
        $mod_info = get_fast_modinfo($course_id);
        return $mod_info->get_section_info_by_id($section_id, MUST_EXIST);
    }

    private function get_backup_controller(
        int $user_id,
        cm_info $cm
    ): backup_controller
    {
        return new backup_controller(
            backup::TYPE_1ACTIVITY,
            $cm->id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $user_id,
            backup::RELEASESESSION_YES
        );
    }

    private function rearrange_sharing_cart_by_path(int $user_id, string $path): void
    {
        $records = $this->db->get_recordset(record::TABLE, [
            'userid' => $user_id,
            'tree' => $path
        ], 'weight ASC', 'id, weight, modtext');

        $items = [];
        foreach ($records as $record) {
            $items[(int)$record->id] = $record;
        }
        $records->close();

        uasort($items, static function ($a, $b) {
            $weight = $a->weight <=> $b->weight;
            return $weight === 0 ? strnatcasecmp($a->modtext, $b->modtext) : $weight;
        });

        $weight = 0;
        foreach ($items as $id => $record) {
            $weight++;
            $this->db->update_record(record::TABLE, (object)[
                'id' => $id,
                'weight' => $weight,
            ]);
        }
    }

    private function validate_backup_capability(cm_info $cm, ?int $user_id = null): void
    {
        $this->validate_support_for_backup($cm);
        $this->validate_backup_permission($cm, $user_id);
    }

    private function validate_support_for_backup(cm_info $cm): void
    {
        if (!$this->cm_repo->is_backup_supported($cm)) {
            throw new no_backup_support_exception('No backup in module',
                'Module not implementing: https://docs.moodle.org/dev/Backup_API');
        }
    }

    private function validate_backup_permission(
        cm_info $cm,
        ?int $user_id = null
    ): void
    {
        require_capability('moodle/backup:backupactivity', $cm->context, $user_id);
        require_capability('moodle/backup:userinfo', $cm->context, $user_id);
    }

    private function set_backup_plan_settings(
        cm_info $cm,
        backup_plan $plan,
        array $settings
    ): void
    {
        foreach ($settings as $name => $value) {
            if (!$plan->setting_exists($name)) {
                continue;
            }

            $setting = $plan->get_setting($name);
            if (base_setting::NOT_LOCKED !== $setting->get_status()) {
                continue;
            }
            $setting->set_value($value);
        }

        if (!$plan->setting_exists('filename')) {
            throw new coding_exception('Cannot create backup file');
        }

        $filename = $this->get_backup_filename($cm);
        $plan->get_setting('filename')->set_value($filename);
    }

    private function move_backup_file(
        int $user_id,
        stored_file $file
    ): stored_file
    {
        if ($this->is_user_backup_file($user_id, $file)) {
            return $file;
        }

        $storage = new storage($user_id);
        $backup = $storage->copy_stored_file($file);
        $file->delete();

        return $backup;
    }

    private function is_user_backup_file(
        int $user_id,
        stored_file $file
    ): bool
    {
        return (int)$file->get_userid() === $user_id
            && $file->get_component() === storage::COMPONENT
            && $file->get_filearea() === storage::FILEAREA
            && (int)$file->get_itemid() === storage::ITEMID
            && $file->get_filepath() === storage::FILEPATH;
    }

    private function cleanup_folder_name(string $name): string
    {
        return str_replace('/', '-', $name);
    }

    private function get_user(): object
    {
        global $USER;
        return $USER;
    }

    private function get_backup_file(backup_controller $controller): ?stored_file
    {
        $results = $controller->get_results();
        if (isset($results['backup_destination']) && $results['backup_destination'] instanceof stored_file) {
            return $results['backup_destination'];
        }
        return null;
    }

    private function get_backup_filename(cm_info $cm): string
    {
        return self::create_backup_filename($cm);
    }
}
