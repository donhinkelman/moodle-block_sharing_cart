<?php
/**
 * @package     block_sharing_cart\tests
 * @developer   Sam Møller
 * @copyright   2020 Praxis
 * @companyinfo https://praxis.dk
 */

namespace block_sharing_cart\tests;

use block_sharing_cart\record;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit test tool
 * @package block_sharing_cart\tests
 */
trait sharing_chart_test_tools {

    /**
     * Get course section with name
     * @param $course
     * @param int $section
     * @return object
     * @throws \dml_exception
     */
    private function get_course_section($course, int $section) {
        $record = self::db()->get_record('course_sections', [
            'course' => $course->id,
            'section' => $section
        ]);

        $record->name = get_section_name($course->id, $section);

        return $record;
    }

    /**
     * @param int $id
     * @return int
     * @throws \dml_exception
     */
    private function get_sharing_cart_weight(int $id): int {
        return self::db()->get_field(record::TABLE, 'weight', ['id' => $id]);
    }

    /**
     * @param string $module_name
     * @param string $tree
     * @return bool|false|mixed|\stdClass
     * @throws \dml_exception
     */
    private function get_sharing_cart_by_module(string $module_name, string $tree = '') {
        return self::db()->get_record(record::TABLE, ['modname' => $module_name, 'tree' => $tree]);
    }

    /**
     * Get course section entities with name
     * @param $course
     * @param int $section
     * @return array
     * @throws \dml_exception
     */
    private function get_course_sections($course, int $section = -1): array {
        $conditions = [
            'course' => $course->id,
        ];

        if ($section > -1) {
            $conditions['section'] = $section;
        }

        $section_records = self::db()->get_recordset('course_sections', $conditions);
        $sections = [];

        foreach ($section_records as $record) {
            $record->name = get_section_name($course->id, $record->section);
            $sections[$record->id] = $record;
        }

        $section_records->close();

        return $sections;
    }

    /**
     * Get a single sharing cart entity
     * @param array $conditions
     * @return object
     * @throws \dml_exception
     */
    private function get_sharing_cart_entity(array $conditions = []) {
        return self::db()->get_record('block_sharing_cart', $conditions);
    }

    /**
     * Get sharing cart entities
     * @param array $conditions
     * @return array
     * @throws \dml_exception
     */
    private function get_sharing_cart_entities(array $conditions = []): array {
        return self::db()->get_records('block_sharing_cart', $conditions);
    }

    /**
     * Enroll users to the course
     * @param $course
     * @param string $role
     * @param object[] $users
     */
    private function enrol_users($course, array $users = null, string $role = 'editingteacher') {
        global $USER;

        // Add current user to enrollment
        if (empty($users)) {
            $users = [$USER];
        }

        foreach ($users as $user) {
            $this->getDataGenerator()->enrol_user($user->id, $course->id, $role);
        }
    }

    /**
     * Set session via GET method
     * @param object $user
     */
    private function set_session_key($user): void {
        // Set current user
        self::setUser($user);

        // Sharing cart required session key as parameter
        // Send session key via GET
        $_GET['sesskey'] = sesskey();
    }

    /**
     * Create a assignment
     * @param $course
     * @param int $section
     * @param array $properties
     * @param array|null $options
     * @return object
     */
    private function create_assignment($course, int $section = 0, array $properties = [], array $options = null) {
        return $this->create_module('assign', $course, $section, $properties, $options);
    }

    /**
     * @param string $name
     * @param $course
     * @param int $section
     * @param array $properties
     * @param array|null $options
     * @return object
     */
    private function create_module(string $name, $course, int $section = 0, array $properties = [], array $options = null) {
        $properties['course'] = $course->id;

        if (!isset($properties['section'])) {
            $properties['section'] = $section;
        }

        return $this->getDataGenerator()->create_module($name, $properties, $options);
    }

    /**
     * Create a user
     * @param array|null $properties
     * @return object
     */
    private function create_user(array $properties = null) {
        return $this->getDataGenerator()->create_user($properties);
    }

    /**
     * Create a course
     * @param array $properties
     * @return object
     */
    private function create_course(array $properties = ['section' => 4]) {
        return $this->getDataGenerator()->create_course($properties);
    }

    /**
     * Get moodle database
     * @return \moodle_database
     */
    private static function db(): \moodle_database {
        global $DB;
        return $DB;
    }
}
