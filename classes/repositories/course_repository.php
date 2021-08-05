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

use coding_exception;
use dml_exception;
use moodle_database;

defined('MOODLE_INTERNAL') || die();

/**
 * Class course_repository
 * @package block_sharing_cart\repositories
 */
class course_repository
{
    /** @var moodle_database */
    private $db;

    /**
     * course_repository constructor.
     * @param moodle_database $database
     */
    public function __construct(moodle_database $database){
        $this->db = $database;
    }

    /**
     * @param array $entities
     * @return string[] Return [id => course full name]
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_course_fullnames_by_sharing_carts(array $entities): array {
         $course_ids = $this->extract_course_ids($entities);
         return $this->get_course_fullnames($course_ids);
    }

    /**
     * @param array $ids Course IDs
     * @param string $default_name
     * @return string[] Return [id => course full name]
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_course_fullnames(array $ids, string $default_name = ''): array {

        if (empty($ids)) {
            return [];
        }

        [$in_sql, $params] = $this->db->get_in_or_equal($ids);
        $records = $this->db->get_recordset_select('course', 'id ' . $in_sql, $params);

        $courses = [];
        foreach ($records as $id => $record) {
            $courses[(int)$id] = $record->fullname ?? $default_name;
        }
        $records->close();

        return $courses;
    }

    /**
     * @param array $entities
     * @return array
     */
    private function extract_course_ids(array $entities): array {
        $ids = [];
        foreach ($entities as $entity) {
            $ids[] = (int)$entity->course;
        }
        return $ids;
    }
}
