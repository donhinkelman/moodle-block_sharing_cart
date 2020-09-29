<?php

defined('MOODLE_INTERNAL') || die();

use \block_sharing_cart\privacy\provider;
use \core_privacy\tests\request\approved_contextlist;
use \core_privacy\local\request\approved_userlist;
use \core_privacy\local\request\userlist;

/**
 *
 * Sharing cart privacy testing class
 */
class block_sharing_cart_privacy_testcase extends \core_privacy\tests\provider_testcase {

    /**
     * This method is called before each test.
     */
    protected function setUp(): void {
        $this->resetAfterTest();
    }

    /**
     * Test getting the context for the user ID related to sharing cart.
     * @test
     */
    public function get_contexts_for_user(): void {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $assign = $this->create_assignment($course, 1);

        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'editingteacher');

        $this->set_session_key($user);
        $this->add_sharing_cart_activity($course, $assign);

        $context = context_user::instance($user->id);
        $contextlist = provider::get_contexts_for_userid($user->id);

        $this->assertContains($context->id, $contextlist->get_contextids());
    }

    /**
     * @test
     */
    public function get_users_in_context(): void {
        $component = 'block_sharing_cart';

        $generator = $this->getDataGenerator();

        // Prepare user, course and assignments
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $course = $generator->create_course(['numsections' => 4]);

        $generator->enrol_user($user1->id, $course->id, 'editingteacher');
        $generator->enrol_user($user2->id, $course->id, 'editingteacher');

        $user1_context = context_user::instance($user1->id);
        $user2_context = context_user::instance($user2->id);

        $this->create_assignment($course, 1);
        $this->create_assignment($course, 2);

        $this->set_session_key($user1);

        $userlist1 = new \core_privacy\local\request\userlist($user1_context, $component);
        provider::get_users_in_context($userlist1);
        $this->assertCount(0, $userlist1);

        // Copy section to sharing cart
        $this->add_sharing_cart_section($course, 1);

        provider::get_users_in_context($userlist1);
        // Expect user1 to have 1 data
        $this->assertCount(1, $userlist1);

        $this->set_session_key($user2);

        $userlist2 = new \core_privacy\local\request\userlist($user2_context, $component);
        provider::get_users_in_context($userlist2);

        // Expect user2 to have 0 data
        $this->assertCount(0, $userlist2);

        // Validate in system context
        $systemcontext = context_system::instance();
        $userlist3 = new \core_privacy\local\request\userlist($systemcontext, $component);
        provider::get_users_in_context($userlist3);
        $this->assertCount(0, $userlist3);
    }



    /**
     * Test exporting user data
     * @test
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function export_user_data(): void {
        $generator = $this->getDataGenerator();

        // Prepare user, course and assignments
        $user = $generator->create_user();
        $course = $generator->create_course(['numsections' => 4]);
        $generator->enrol_user($user->id, $course->id, 'editingteacher');

        $cm1 = $this->create_assignment($course, 0);
        $cm2 = $this->create_assignment($course, 1);
        $cm3 = $this->create_assignment($course, 2);
        $cm4 = $this->create_assignment($course, 2);

        $this->set_session_key($user);

        // Copy assignment and sections to sharing cart
        $this->add_sharing_cart_activity($course, $cm1);
        $this->add_sharing_cart_section($course, 1);
        $this->add_sharing_cart_section($course, 2);
        $this->add_sharing_cart_section($course, 2);

        // Prepare assignments names for assertion
        $root_cm_names = [
            $cm1->name
        ];
        $section1_cm_names = [
            $cm2->name
        ];
        $section2_cm_names = [
            $cm3->name,
            $cm4->name
        ];

        // Prepare user context
        $context = context_user::instance($user->id);
        $component = 'block_sharing_cart';
        $root_name = get_string('pluginname', $component);

        // Privacy data writer
        $writer = \core_privacy\local\request\writer::with_context($context);
        $this->assertFalse($writer->has_any_data());

        // Export user data
        $this->export_context_data_for_user($user->id, $context, $component);

        // Get user data
        $section1_name = get_section_name($course->id, 1);
        $section2_name = get_section_name($course->id, 2);
        $section2_name_copy = "$section2_name (1)";

        $data_root = (array)$writer->get_data([$root_name]);
        $data_section1 = (array)$writer->get_data([$root_name, $section1_name]);
        $data_section2 = (array)$writer->get_data([$root_name, $section2_name]);
        $data_section2_copy = (array)$writer->get_data([$root_name, $section2_name_copy]);

        // Assertions
        $this->assertNotEmpty($data_root);
        $this->assertNotEmpty($data_section1);
        $this->assertNotEmpty($data_section2);
        $this->assertNotEmpty($data_section2_copy);

        foreach ($data_root as $data) {
            $this->assertEquals('assign', $data['type']);
            $this->assertContains($data['name'], $root_cm_names);
        }

        foreach ($data_section1 as $data) {
            $this->assertEquals('assign', $data['type']);
            $this->assertContains($data['name'], $section1_cm_names);
        }

        foreach ($data_section2 as $data) {
            $this->assertEquals('assign', $data['type']);
            $this->assertContains($data['name'], $section2_cm_names);
        }

        foreach ($data_section2_copy as $data) {
            $this->assertEquals('assign', $data['type']);
            $this->assertContains($data['name'], $section2_cm_names);
        }
    }

    /**
     * @test
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function delete_data_for_all_users_in_context(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $assign = $this->create_assignment($course, 1);
        $user = $generator->create_user();
        $context = context_user::instance($user->id);

        $generator->enrol_user($user->id, $course->id, 'editingteacher');

        $this->set_session_key($user);
        $this->add_sharing_cart_section($course, 1);

        $entities = $this->get_user_sharing_cart($user);
        $this->assertCount(1, $entities);

        provider::delete_data_for_all_users_in_context($context);

        $entities = $this->get_user_sharing_cart($user);
        $this->assertCount(0, $entities);
    }

    /**
     * Test delete data for users
     * @test
     */
    public function delete_data_for_users() {
        global $DB;

        $generator = $this->getDataGenerator();
        $component = 'block_sharing_cart';
        $rolename = 'editingteacher';

        // Course
        $course = $generator->create_course(['numsections' => 4]);

        // Assignments
        $section1_assign1 = $this->create_assignment($course, 1);
        $section2_assign1 = $this->create_assignment($course, 2);
        $section2_assign2 = $this->create_assignment($course, 2);
        $section3_assign1 = $this->create_assignment($course, 3);
        $section3_assign2 = $this->create_assignment($course, 3);
        $section3_assign3 = $this->create_assignment($course, 3);
        $section4_assign1 = $this->create_assignment($course, 4);
        $section4_assign2 = $this->create_assignment($course, 4);
        $section4_assign3 = $this->create_assignment($course, 4);
        $section4_assign4 = $this->create_assignment($course, 4);

        // Users
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();

        // Enrolling users
        $generator->enrol_user($user1->id, $course->id, $rolename);
        $generator->enrol_user($user2->id, $course->id, $rolename);
        $generator->enrol_user($user3->id, $course->id, $rolename);
        $generator->enrol_user($user4->id, $course->id, $rolename);

        // Contexts
        $usercontext1 = context_user::instance($user1->id);
        $usercontext2 = context_user::instance($user2->id);
        $usercontext3 = context_user::instance($user3->id);
        $usercontext4 = context_user::instance($user4->id);

        // User list
        $userlist1 = new userlist($usercontext1, $component);
        $userlist2 = new userlist($usercontext2, $component);
        $userlist3 = new userlist($usercontext3, $component);
        $userlist4 = new userlist($usercontext4, $component);

        // Copy assignment to sharing cart for user1
        // user1 have 1 section copied
        $this->set_session_key($user1);
        $this->add_sharing_cart_section($course, 1);

        // Copy assignments to sharing cart for user2
        // user2 have 2 sections copied
        $this->set_session_key($user2);
        $this->add_sharing_cart_section($course, 1);
        $this->add_sharing_cart_section($course, 2);

        // Copy assignments to sharing cart for user3
        // user3 have 3 sections copied
        $this->set_session_key($user3);
        $this->add_sharing_cart_section($course, 1);
        $this->add_sharing_cart_section($course, 2);
        $this->add_sharing_cart_section($course, 3);

        // Copy assignments to sharing cart for user4
        // user4 have 4 sections copied
        $this->set_session_key($user4);
        $this->add_sharing_cart_section($course, 1);
        $this->add_sharing_cart_section($course, 2);
        $this->add_sharing_cart_section($course, 3);
        $this->add_sharing_cart_section($course, 4);

        // Test if data is actual exist for user2 & user4
        $user2_data = $this->get_user_sharing_cart($user2, $course);
        $user4_data = $this->get_user_sharing_cart($user4, $course);
        $this->assertCount(1 + 2, $user2_data);
        $this->assertCount(1 + 2 + 3 + 4, $user4_data);

        // Userlist assertions
        provider::get_users_in_context($userlist1);
        $this->assertCount(1, $userlist1);
        $this->assertEquals([$user1->id], $userlist1->get_userids());

        provider::get_users_in_context($userlist2);
        $this->assertCount(1, $userlist2);
        $this->assertEquals([$user2->id], $userlist2->get_userids());

        provider::get_users_in_context($userlist2);
        $this->assertCount(1, $userlist2);
        $this->assertEquals([$user2->id], $userlist2->get_userids());

        provider::get_users_in_context($userlist4);
        $this->assertCount(1, $userlist4);
        $this->assertEquals([$user4->id], $userlist4->get_userids());

        // Delete data user user2 & user4
        $approvedlist2 = new approved_userlist($usercontext2, $component, $userlist2->get_userids());
        $approvedlist4 = new approved_userlist($usercontext4, $component, $userlist4->get_userids());

        provider::delete_data_for_users($approvedlist2);
        provider::delete_data_for_users($approvedlist4);

        // re-initialize userlist for user2 & user4
        $userlist2 = new userlist($usercontext2, $component);
        $userlist4 = new userlist($usercontext4, $component);
        $this->assertCount(0, $userlist2);
        $this->assertCount(0, $userlist4);

        // Test the actual was deleted for user2 & user4
        $user2_data = $this->get_user_sharing_cart($user2, $course);
        $user4_data = $this->get_user_sharing_cart($user4, $course);
        $this->assertCount(0, $user2_data);
        $this->assertCount(0, $user4_data);
    }

    /**
     * Test delete data for user
     * @test
     */
    public function delete_data_for_user(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $user2 = $generator->create_user();
        $course = $generator->create_course(['numsections' => 4]);
        $assign1 = $this->create_assignment($course, 1);
        $assign2 = $this->create_assignment($course, 2);

        $generator->enrol_user($user->id, $course->id, 'editingteacher');
        $generator->enrol_user($user2->id, $course->id, 'student');
        $this->set_session_key($user);

        $this->add_sharing_cart_activity($course, $assign1);
        $this->add_sharing_cart_section($course, 2);

        // Expect 2 items in this assertion
        $entities = $this->get_user_sharing_cart($user);
        $this->assertCount(2, $entities);

        // Out of context delete data request
        $contextids = [
            context_system::instance()->id,
            context_course::instance($course->id)->id,
        ];
        $contextlist = new approved_contextlist($user,'block_sharing_cart',$contextids);
        // Expect nothing to be delete for the system context
        provider::delete_data_for_user($contextlist);

        // Still expect 2 items in this assertion
        $entities = $this->get_user_sharing_cart($user);
        $this->assertCount(2, $entities);

        // Adding another user context and test if another user context have any affect on current user
        $contextids[] = context_user::instance($user2->id)->id;
        $contextlist = new approved_contextlist($user,'block_sharing_cart', $contextids);
        // Expect nothing to be delete for the system context
        provider::delete_data_for_user($contextlist);

        // Still expect 2 items in this assertion
        $entities = $this->get_user_sharing_cart($user);
        $this->assertCount(2, $entities);

        // Request data deletion for the user
        $context = context_user::instance($user->id);
        $contextlist = new \core_privacy\tests\request\approved_contextlist($user, 'block_sharing_cart', [$context->id]);
        provider::delete_data_for_user($contextlist);

        // Expect 0 item in this assertion
        $entities = $this->get_user_sharing_cart($user);
        $this->assertCount(0, $entities);
    }

    /**
     * Set session via GET method
     * @param object $user
     */
    private function set_session_key(object $user): void {
        // Set current user
        self::setUser($user);

        // Sharing cart required session key as parameter
        // Send session key via GET
        $_GET['sesskey'] = sesskey();
    }

    /**
     * Add activity to sharing cart
     * @param object $course
     * @param object $module
     * @throws moodle_exception
     */
    private function add_sharing_cart_activity(object $course, object $module): void {
        // Creating sharing cart item
        $controller = new \block_sharing_cart\controller();
        $controller->backup($module->cmid, false, $course->id);
    }

    /**
     * Add section to sharing cart
     * @param object $course
     * @param int $section
     * @return string Section name
     * @throws dml_exception
     * @throws moodle_exception
     */
    private function add_sharing_cart_section(object $course, int $section = 0): string {
        global $DB;

        $section_record = $DB->get_record(
            'course_sections',
            ['course' => $course->id, 'section' => $section]
        );

        // Section is out of range
        if (empty($section_record)) {
            throw new InvalidArgumentException('Given section is out of range for the course with id '. $course->id);
        }

        $section_name = get_section_name($course, $section);

        // Backup section for sharing cart
        $controller = new \block_sharing_cart\controller();
        $controller->backup_section($section_record->id, $section_name, false, $course->id);

        return $section_name;
    }

    /**
     * @param $user
     * @param object|null $course
     * @return array
     * @throws dml_exception
     */
    private function get_user_sharing_cart($user, ?object $course = null): array {
        global $DB;

        $params = ['userid' => $user->id];

        if (!empty($course)) {
            $params['course'] = $course->id;
        }

        return $DB->get_records('block_sharing_cart', $params);
    }

    /**
     * @param object $course
     * @param int $section
     * @param array $properties Record properties
     * @param array $options
     * @return object
     */
    private function create_assignment(object $course, int $section = 0, array $properties = [], array $options = []): object {
        $properties['course'] = $course->id;
        $properties['section'] = $section;
        return $this->getDataGenerator()->create_module('assign', $properties, $options);
    }
}