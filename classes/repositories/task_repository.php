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


use core\notification;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class task_repository
{
    private const RESTORE_IN_PROGRESS_KEY = 'block_sharing_cart_restore_in_progress';

    private \moodle_database $db;

    public function __construct(\moodle_database $db)
    {
        $this->db = $db;
    }


    public static function create()
    {
        global $DB;
        return new self($DB);
    }

    public function get_restore_in_progress_by_course_id(int $course_id): array
    {
        $data = $this->get_restore_in_progress_data($this->user()->id);
        if (isset($data[$course_id])) {
            return array_merge([], ...$data[$course_id]);
        }
        return [];
    }

    public function set_restore_in_progress(
        int $sharing_cart_id,
        int $course_id,
        int $section_number,
        ?int $user_id = null
    ): void
    {
        $user_id ??= $this->user()->id;

        $data = $this->get_restore_in_progress_data($user_id);
        if (isset($data[$course_id][$section_number][$sharing_cart_id])) {
            return;
        }
        $data[$course_id][$section_number][$sharing_cart_id] = $sharing_cart_id;
        $this->set_preference_json(
            self::RESTORE_IN_PROGRESS_KEY,
            $data,
            $user_id
        );
    }

    public function unset_restore_in_progress(
        int $sharing_cart_id,
        int $course_id,
        int $section_number,
        ?int $user_id = null
    ): void
    {
        $user_id ??= $this->user()->id;
        $data = $this->get_restore_in_progress_data($user_id);
        unset($data[$course_id][$section_number][$sharing_cart_id]);
        if (empty($data)) {
            unset_user_preference(self::RESTORE_IN_PROGRESS_KEY, $user_id);
            return;
        }
        $this->set_preference_json(self::RESTORE_IN_PROGRESS_KEY, $data, $user_id);
    }

    public function notify_restore_in_progress_by_course_id(int $course_id): void
    {
        $data = $this->get_restore_in_progress_data($this->user()->id);
        if (!isset($data[$course_id])) {
            return;
        }

        $sharing_cart_ids = array_merge([], ...$data[$course_id]);
        if (empty($sharing_cart_ids)) {
            return;
        }

        [$in_sql, $params] = $this->db->get_in_or_equal($sharing_cart_ids);
        $records = $this->db->get_records_select(
            'block_sharing_cart',
            "id $in_sql",
            $params,
            '',
            'id, modtext'
        );

        foreach ($data[$course_id] as $section_number => $ids) {
            foreach ($ids as $id) {
                if (!isset($records[$id])) {
                    continue;
                }

                notification::info(get_string(
                    'async_restore_in_progress',
                    'block_sharing_cart',
                    (object)[
                        'modtext' => $records[$id]->modtext,
                        'course' => $course_id,
                        'section' => $section_number
                    ]
                ));
            }
        }
    }

    private function user(): object
    {
        global $USER;
        return $USER;
    }

    private function set_preference_json(
        string $name,
        array $data,
        int $user_id
    ): void
    {
        set_user_preference($name, json_encode($data), $user_id);
    }

    private function get_restore_in_progress_data(int $user_id): array
    {
        $json = get_user_preferences(self::RESTORE_IN_PROGRESS_KEY, '{}', $user_id);
        return json_decode($json, true);
    }
}
