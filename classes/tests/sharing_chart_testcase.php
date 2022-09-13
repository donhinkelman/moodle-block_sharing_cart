<?php
/**
 * @package     block_sharing_cart\tests
 * @developer   Sam Møller
 * @copyright   2020 Praxis
 * @companyinfo https://praxis.dk
 */

namespace block_sharing_cart\tests;

use advanced_testcase;
use block_sharing_cart\record;
use dml_exception;
use moodle_database;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit test tool
 * @package block_sharing_cart\tests
 */
abstract class sharing_chart_testcase extends advanced_testcase {

    /**
     * @inheritDoc
     */
    protected function setUp(): void {
        $this->resetAfterTest();
    }

    /**
     * Get course section with name
     * @param $course
     * @param int $section
     * @return object
     * @throws dml_exception
     */
    protected function get_course_section($course, int $section): object {
        $record = $this->db()->get_record('course_sections', [
            'course' => $course->id,
            'section' => $section
        ]);

        $record->name = get_section_name($course->id, $section);

        return $record;
    }

    /**
     * @param int $id
     * @return int
     * @throws dml_exception
     */
    protected function get_sharing_cart_weight(int $id): int {
        return $this->db()->get_field(record::TABLE, 'weight', ['id' => $id]);
    }

    /**
     * @param string $module_name
     * @param string $tree
     * @return bool|false|mixed|stdClass
     * @throws dml_exception
     */
    protected function get_sharing_cart_by_module(string $module_name, string $tree = '') {
        return $this->db()->get_record(record::TABLE, ['modname' => $module_name, 'tree' => $tree]);
    }

    /**
     * Get a single sharing cart entity
     * @param array $conditions
     * @return object
     * @throws dml_exception
     */
    protected function get_sharing_cart_entity(array $conditions = []): object {
        return $this->db()->get_record('block_sharing_cart', $conditions);
    }

    /**
     * Get sharing cart entities
     * @param array $conditions
     * @return array
     * @throws dml_exception
     */
    protected function get_sharing_cart_entities(array $conditions = []): array {
        return $this->db()->get_records('block_sharing_cart', $conditions);
    }

    /**
     * Enroll users to the course
     * @param $course
     * @param string $role
     * @param object[] $users
     */
    protected function enrol_users($course, array $users = null, string $role = 'editingteacher'): void {
        global $USER;

        // Add current user to enrollment
        if (empty($users)) {
            $users = [$USER];
        }

        foreach ($users as $user) {
            self::getDataGenerator()->enrol_user($user->id, $course->id, $role);
        }
    }

    /**
     * Set session via GET method
     * @param object $user
     */
    protected function set_session_key(object $user): void {
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
    protected function create_assignment($course, int $section = 0, array $properties = [], array $options = null): object {
        return $this->create_module('assign', $course, $section, $properties, $options);
    }

    protected function disable_assign(): void {
        $this->db()->update_record('modules', (object)['id' => 1, 'visible' => 0]);
    }

    /**
     * Create an url
     * @param $course
     * @param int $section
     * @param array $properties
     * @param array|null $options
     * @return object
     */
    protected function create_url($course, int $section = 0, array $properties = [], array $options = null): object {
        return $this->create_module('url', $course, $section, $properties, $options);
    }

    /**
     * @param string $name
     * @param $course
     * @param int $section
     * @param array $properties
     * @param array|null $options
     * @return object
     */
    protected function create_module(string $name, $course, int $section = 0, array $properties = [], array $options = null): object {
        $properties['course'] = $course->id;

        if (!isset($properties['section'])) {
            $properties['section'] = $section;
        }

        return self::getDataGenerator()->create_module($name, $properties, $options);
    }

    /**
     * Create a user
     * @param array|null $properties
     * @return object
     */
    protected function create_user(array $properties = null): object {
        return self::getDataGenerator()->create_user($properties);
    }

    /**
     * Create a course
     * @param array $properties
     * @return object
     */
    protected function create_course(array $properties = ['section' => 4]): object {
        return self::getDataGenerator()->create_course($properties);
    }

    /**
     * @param $user
     * @param $course
     * @param int $section
     * @param null $filename
     * @return object
     * @throws dml_exception
     */
    protected function create_sharing_chart_record($user, $course, $section = 0, $filename = null): object {
        $filename = $filename ?? md5(random_bytes(16));
        $modname = md5(random_bytes(16));
        $modicon = md5(random_bytes(16));
        $modtext = md5(random_bytes(16));

        $params = [
            'userid' => $user->id,
            'course' => $course->id,
            'section' => $section,
            'ctime' => time(),
            'weight' => 1,
            'filename' => $filename,
            'modname' => $modname,
            'modicon' => $modicon,
            'modtext' => $modtext,
            'tree' => '',
        ];

        $id = $this->db()->insert_record('block_sharing_cart', (object)$params);
        $params['id'] = $id;
        return (object)$params;
    }

    /**
     * Get moodle database
     * @return moodle_database
     */
    protected function db(): moodle_database {
        global $DB;
        return $DB;
    }
}
