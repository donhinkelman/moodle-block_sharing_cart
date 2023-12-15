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

namespace block_sharing_cart\event;


use cm_info;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd


/**
 * @method static self create(array $data)
 */
abstract class base extends \core\event\base
{
    private static array $course_modules = [];

    public const CRUD_CREATED = 'c';
    public const CRUD_READ = 'r';
    public const CRUD_UPDATE = 'u';
    public const CRUD_DELETE = 'd';

    protected function get_edu_level(): int
    {
        return static::LEVEL_OTHER;
    }

    protected function get_crud(): string
    {
        return static::CRUD_CREATED;
    }

    protected function init(): void
    {
        $this->data['crud'] = $this->get_crud();
        $this->data['edulevel'] = $this->get_edu_level();
        $this->data['objecttable'] = 'block_sharing_cart';
    }

    protected function get_course_module_id(): int
    {
        return $this->other['cmid'] ?? 0;
    }

    public static function create_by_course_module_id(
        int $course_id,
        int $course_module_id,
        int $sharing_cart_backup_id = 0
    ): self
    {
        return static::create_by_course_module(
            self::get_course_module_by_id($course_id, $course_module_id),
            $sharing_cart_backup_id
        );
    }

    public static function create_by_course_module(
        cm_info $cm,
        int $sharing_cart_backup_id = 0
    ): self
    {
        return static::create([
            'objectid' => $sharing_cart_backup_id,
            'courseid' => $cm->course,
            'context' => $cm->context,
            'other' => [
                'courseid' => $cm->course,
                'sectionid' => $cm->section,
                'sectionnum' => $cm->sectionnum,
                'cmid' => $cm->id,
            ],
        ]);
    }

    private static function get_course_module_by_id(int $course_id, int $course_module_id): cm_info
    {
        return self::$course_modules[$course_id][$course_module_id] ??=
            get_fast_modinfo($course_id)->get_cm($course_module_id);
    }
}
