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


use cm_info;
use moodle_database;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class course_module_repository
{
    private moodle_database $db;

    public function __construct(?moodle_database $db = null)
    {
        global $DB;
        $this->db = $db ?? $DB;
    }

    public static function create(): self
    {
        return new self();
    }

    private function get_label_intro(cm_info $cm): string
    {
        try {
            $record = $this->db->get_record(
                'label',
                ['id' => $cm->instance],
                'id, intro, introformat',
                MUST_EXIST
            );
            if (!empty($record)) {
                return format_text(
                    $record->intro,
                    $record->introformat,
                    [
                        'noclean' => true,
                        'para' => false,
                        'filter' => true,
                        'context' => $cm->context
                    ]
                );
            }
        }
        catch (\Exception $e) { }

        return $cm->name;
    }

    public function get_course_module(
        int $cm_id,
        int $course_id,
        int $user_id = 0
    ): cm_info
    {
        return get_fast_modinfo($course_id, $user_id)->get_cm($cm_id);
    }

    public function get_title(cm_info $cm): string
    {
        return $cm->modname === 'label' ? $this->get_label_intro($cm) : $cm->name;
    }

    public function is_backup_supported(cm_info $cm): bool
    {
        return (bool)plugin_supports(
            'mod',
            $cm->modname,
            FEATURE_BACKUP_MOODLE2,
            false
        );
    }
}
