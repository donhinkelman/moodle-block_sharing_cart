<?php


defined('MOODLE_INTERNAL') || die();

/**
 * Testing controller functionality
 */
class block_sharing_cart_controller_testcase extends advanced_testcase {
    use \block_sharing_cart\tests\sharing_chart_test_tools;

    /**
     * This method is called before each test.
     */
    protected function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Test add activities to sharing cart
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function test_add_activities_to_sharing_cart(): void {
        // Create course, user and assignments
        $user = $this->create_user();
        $course = $this->create_course();

        $assignment = $this->create_assignment($course, 1);
        $label = $this->create_module('label', $course, 2);
        $forum = $this->create_module('forum', $course, 2);

        // Enrolling user that capable to do backup and restore
        $this->enrol_users($course, [$user]);

        // Set session key and set current user
        $this->set_session_key($user);

        // Test if sharing cart is empty for current user
        $entities = $this->get_sharing_cart_entities(['userid' => $user->id]);
        $this->assertCount(0, $entities);

        $controller = new \block_sharing_cart\controller();
        $controller->backup($assignment->cmid, false, $course->id);
        $controller->backup($label->cmid, false, $course->id);
        $controller->backup($forum->cmid, false, $course->id);

        // Test if sharing cart have 3 copied activities for current user
        $entities = $this->get_sharing_cart_entities(['userid' => $user->id]);
        $this->assertCount(3, $entities);

        foreach ($entities as $entity) {
            switch ($entity->modname) {
                case 'assign':
                    $this->assertEquals($assignment->name, $entity->modtext);
                    break;
                case 'forum':
                    $this->assertEquals($forum->name, $entity->modtext);
                    break;
                case 'label':
                    $this->assertEquals($label->name, strip_tags($entity->modtext));
                    break;
                default:
                    $this->throwException(new Exception('No activity in sharing cart'));
                    break;
            }
        }
    }

    /**
     * Test add sections to sharing cart
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function test_add_sections_to_sharing_cart(): void {
        // Create course, user and assignments
        $user = $this->create_user();
        $course = $this->create_course();
        $assignment1 = $this->create_assignment($course, 1);
        $assignment2 = $this->create_assignment($course, 1);
        $assignment3 = $this->create_assignment($course, 2);
        $assignment4 = $this->create_assignment($course, 2);

        $section1 = $this->get_course_section($course, 1);
        $section2 = $this->get_course_section($course, 1);

        // Enrolling user that capable to do backup and restore
        $this->enrol_users($course, [$user]);

        // Set session key and set current user
        $this->set_session_key($user);

        // Test if sharing cart is empty for current user
        $entities = $this->get_sharing_cart_entities(['userid' => $user->id]);
        $this->assertCount(0, $entities);

        $controller = new \block_sharing_cart\controller();
        $controller->backup_section($section1->id, $section1->name, false, $course->id);
        $controller->backup_section($section2->id, $section2->name, false, $course->id);

        // Test if sharing cart have 4 copied activities for current user
        $entities = $this->get_sharing_cart_entities(['userid' => $user->id]);
        $this->assertCount(4, $entities);
        $names = [
            $assignment1->name,
            $assignment2->name,
            $assignment3->name,
            $assignment4->name,
        ];

        // Test if entities and the same name as assignments
        foreach ($entities as $entity) {
            $this->assertContains($entity->modtext, $names);
        }
    }

    /**
     * Test add section and modules to sharing cart
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function test_add_sections_and_modules_to_sharing_cart(): void {
        // Create course, user and assignments
        $user = $this->create_user();
        $course = $this->create_course();
        $label = $this->create_module('label', $course, 1);
        $assignment1 = $this->create_assignment($course, 2);
        $assignment2 = $this->create_assignment($course, 2);

        $section2 = $this->get_course_section($course, 1);

        // Enrolling user that capable to do backup and restore
        $this->enrol_users($course, [$user]);

        // Set session key and set current user
        $this->set_session_key($user);

        // Test if sharing cart is empty for current user
        $entities = $this->get_sharing_cart_entities(['userid' => $user->id]);
        $this->assertCount(0, $entities);

        $controller = new \block_sharing_cart\controller();
        $controller->backup_section($section2->id, $section2->name, false, $course->id);
        $controller->backup($assignment1->cmid, false, $course->id);
        $controller->backup($assignment2->cmid, false, $course->id);

        // Test if sharing cart have 3 copied activities for current user
        $entities = $this->get_sharing_cart_entities(['userid' => $user->id]);
        $this->assertCount(3, $entities);

        $copied_labels = self::db()->get_records(\block_sharing_cart\record::TABLE, ['modname' => 'label']);
        $copied_assignments = self::db()->get_records(\block_sharing_cart\record::TABLE, ['modname' => 'assign']);
    }

    public function test_restore_modules_from_sharing_cart() {
        $user = $this->create_user();
        $course = $this->create_course();
        $section1 = $this->get_course_section($course, 1);
        $section2 = $this->get_course_section($course, 2);
        $section3 = $this->get_course_section($course, 3);

        $assignment = $this->create_assignment($course, 1);
        $label = $this->create_module('label', $course, 2);
        $forum = $this->create_module('forum', $course, 3);

        $this->enrol_users($course, [$user]);
        $this->set_session_key($user);

        $controller = new \block_sharing_cart\controller();
        $controller->backup($assignment->cmid, false, $course->id);
        $controller->backup($forum->cmid, false, $course->id);
        $controller->backup($label->cmid, false, $course->id);
        $controller->backup_section($section1->id, $section1->name, false, $course->id);
        $controller->backup_section($section2->id, $section2->name, false, $course->id);
        $controller->backup_section($section3->id, $section3->name, false, $course->id);

        $entities = $this->get_sharing_cart_entities(['userid' => $user->id]);
        $this->assertCount(6, $entities);

        $new_course = $this->create_course();
        $this->enrol_users($new_course, [$user]);

        $course_modules = get_course_mods($new_course->id);
        $this->assertCount(0, $course_modules);

        foreach ($entities as $entity) {
            if (empty($entity->tree)) {
                $controller->restore($entity->id, $new_course->id, 1);
            }
            else {
                $controller->restore_directory($entity->tree, $new_course->id, 1, false);
            }
        }

        $new_course_modules = get_course_mods($new_course->id);
        $this->assertNotEmpty($new_course_modules);

        $exclude_properties = [
            'id',
            'cmid',
            'course'
        ];

        foreach ($new_course_modules as $new_module) {
            $module = null;
            switch ($new_module->modname) {
                case 'assign':
                    $module = $assignment;
                    break;
                case 'forum':
                    $module = $forum;
                    break;
                case 'label':
                    $module = $label;
                    break;
            }

            if (empty($module)) {
                throw new Exception('Cannot find any module in the course');
            }

            $this->compare_properties($assignment, $new_module, $exclude_properties);
        }
    }

    /**
     * Test moving item in sharing cart to a new position
     *
     * @throws \block_sharing_cart\exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function test_move_sharing_cart_position() {
        $user = $this->create_user();
        $course = $this->create_course();
        $forum = $this->create_module('forum', $course, 0);
        $assignment = $this->create_assignment($course, 1);
        $label = $this->create_module('label', $course, 2);

        $section1 = $this->get_course_section($course, 1);
        $section2 = $this->get_course_section($course, 2);

        $this->enrol_users($course, [$user]);
        $this->set_session_key($user);

        // Copy modules to the sharing cart
        $controller = new \block_sharing_cart\controller();
        $controller->backup($forum->cmid, false, $course->id);
        $controller->backup($assignment->cmid, false, $course->id);
        $controller->backup($label->cmid, false, $course->id);
        $controller->backup_section($section1->id, $section1->name, false, $course->id);
        $controller->backup_section($section2->id, $section2->name, false, $course->id);

        $sharing_cart_assignment = $this->get_sharing_cart_by_module('assign');
        $sharing_cart_forum = $this->get_sharing_cart_by_module('forum');
        $sharing_cart_label = $this->get_sharing_cart_by_module('label');

        // Order
        // assignment, label, forum
        $controller->move($sharing_cart_forum->id, 0);

        $weight_assignment = $this->get_sharing_cart_weight($sharing_cart_assignment->id);
        $weight_forum = $this->get_sharing_cart_weight($sharing_cart_forum->id);
        $weight_label = $this->get_sharing_cart_weight($sharing_cart_label->id);

        // Assert order position assignment > label > forum
        $this->assertTrue($weight_assignment < $weight_label && $weight_label < $weight_forum);

        // Order
        // label, assignment, forum
        $controller->move($sharing_cart_label->id, $sharing_cart_assignment->id);

        $weight_assignment = $this->get_sharing_cart_weight($sharing_cart_assignment->id);
        $weight_forum = $this->get_sharing_cart_weight($sharing_cart_forum->id);
        $weight_label = $this->get_sharing_cart_weight($sharing_cart_label->id);

        // Assert order position label > assignment > forum
        $this->assertTrue($weight_label < $weight_assignment && $weight_assignment < $weight_forum);

        // Order
        // forum, label, assignment
        $controller->move($sharing_cart_forum->id, $sharing_cart_label->id);
        // forum, assignment, label
        $controller->move($sharing_cart_assignment->id, $sharing_cart_label->id);

        $weight_assignment = $this->get_sharing_cart_weight($sharing_cart_assignment->id);
        $weight_forum = $this->get_sharing_cart_weight($sharing_cart_forum->id);
        $weight_label = $this->get_sharing_cart_weight($sharing_cart_label->id);

        // Assert order original position forum > assign > label
        $this->assertTrue($weight_forum < $weight_assignment && $weight_assignment < $weight_label);

        // Move label from root to section1 folder
        $controller->movedir($sharing_cart_label->id, $section1->name);

        $section_label = $this->get_sharing_cart_entity(['id' => $sharing_cart_label->id]);
        $this->assertEquals($section_label->tree, $section1->name);

        // Move label from section1 to section2 folder
        $controller->movedir($sharing_cart_label->id, $section2->name);

        $section_label = $this->get_sharing_cart_entity(['id' => $sharing_cart_label->id]);
        $this->assertEquals($section_label->tree, $section2->name);

    }

    public function test_delete_sharing_cart() {
        $user = $this->create_user();
        $course = $this->create_course();
        $assignment = $this->create_assignment($course, 1);
        $label = $this->create_module('label', $course, 1);
        $section1 = $this->get_course_section($course, 1);

        $this->enrol_users($course, [$user]);
        $this->set_session_key($user);

        $controller = new \block_sharing_cart\controller();
        $controller->backup($assignment->cmid, false, $course->id);
        $controller->backup($label->cmid, false, $course->id);
        $controller->backup_section($section1->id, $section1->name, false, $course->id);

        $entities = $this->get_sharing_cart_entities(['userid' => $user->id]);

        // Expect user to have 4 copies of modules
        $this->assertCount(4, $entities);

        $entities_no_folder = $this->get_sharing_cart_entities(['userid' => $user->id, 'tree' => '']);
        $entities_with_folder = $this->get_sharing_cart_entities(['userid' => $user->id, 'tree' => $section1->name]);

        // Expect user to have 2 copies of modules
        $this->assertCount(2, $entities_no_folder);

        // Expect user to have 2 copies of modules
        $this->assertCount(2, $entities_with_folder);

        foreach ($entities_no_folder as $entity) {
            $controller->delete($entity->id);
        }

        $entities_no_folder = $this->get_sharing_cart_entities(['userid' => $user->id, 'tree' => '']);
        // Expect user to have no copy of modules left
        $this->assertCount(0, $entities_no_folder);

        $controller->delete_directory($section1->name);
        $entities_with_folder = $this->get_sharing_cart_entities(['userid' => $user->id, 'tree' => $section1->name]);
        // Expect user to have no copy of modules left for this section
        $this->assertCount(0, $entities_with_folder);
    }

    /**
     * @param $a
     * @param $b
     * @param array $exclude_properties
     */
    private function compare_properties($a, $b, array $exclude_properties = []) {
        $obj_a = get_object_vars($a);
        $obj_b = get_object_vars($a);
        $this->assertSameSize($obj_a, $obj_b);

        foreach ($obj_a as $name => $value) {
            if (!in_array($name, $exclude_properties, true)) {
                continue;
            }
            $this->assertEquals($obj_b[$name], $obj_b[$name]);
        }
    }
}
