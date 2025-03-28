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

namespace block_sharing_cart\integration\task;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use block_accessreview\external\get_module_data;
use block_sharing_cart\app\item\entity;
use block_sharing_cart\task\backup_settings_helper;
use core\context\module;
use core\session\exception;


class backup_settings_helper_test extends \advanced_testcase
{
    protected backup_settings_helper $helper;

    protected \stdClass $course1;
    protected \stdClass $course2;
    protected \stdClass $course3;

    protected \stdClass $section1;
    protected \stdClass $section2;
    protected \stdClass $section3;
    protected \stdClass $section4;

    protected \stdClass $module1;
    protected \stdClass $module2;
    protected \stdClass $module3;
    protected \stdClass $module4;

    protected function setUp(): void
    {
        $this->resetAfterTest();
        $this->helper = new backup_settings_helper();

        $this->generate_courses();
    }

    public function test_get_course_settings_by_item_using_section_item_with_users_false(): void
    {
        $item = new entity();
        $item->set_type('section');
        $item->set_old_instance_id($this->section1->id);

        $output = $this->helper->get_course_settings_by_item($item, false);

        // Section asserts
        $this->assertTrue($output[$this->get_section_include($this->section1)]);
        $this->assertFalse($output[$this->get_section_userinfo($this->section1)]);
        $this->assertFalse($output[$this->get_section_include($this->section2)]);
        $this->assertFalse($output[$this->get_section_userinfo($this->section2)]);

        // Module asserts
        $this->assertTrue($output[$this->get_module_include($this->module1)]);
        $this->assertFalse($output[$this->get_module_userinfo($this->module1)]);
        $this->assertTrue($output[$this->get_module_include($this->module2)]);
        $this->assertFalse($output[$this->get_module_userinfo($this->module2)]);
        $this->assertFalse($output[$this->get_module_include($this->module3)]);
        $this->assertFalse($output[$this->get_module_userinfo($this->module3)]);


        $item->set_old_instance_id($this->section2->id);
        $output = $this->helper->get_course_settings_by_item($item, false);

        // Section asserts
        $this->assertFalse($output[$this->get_section_include($this->section1)]);
        $this->assertFalse($output[$this->get_section_userinfo($this->section1)]);
        $this->assertTrue($output[$this->get_section_include($this->section2)]);
        $this->assertFalse($output[$this->get_section_userinfo($this->section2)]);

        // Module asserts
        $this->assertFalse($output[$this->get_module_include($this->module1)]);
        $this->assertFalse($output[$this->get_module_userinfo($this->module1)]);
        $this->assertFalse($output[$this->get_module_include($this->module2)]);
        $this->assertFalse($output[$this->get_module_userinfo($this->module2)]);
        $this->assertTrue($output[$this->get_module_include($this->module3)]);
        $this->assertFalse($output[$this->get_module_userinfo($this->module3)]);
    }

    public function test_get_course_settings_by_item_using_section_item_with_users_true(): void
    {
        $item = new entity();
        $item->set_type('section');
        $item->set_old_instance_id($this->section1->id);

        $output = $this->helper->get_course_settings_by_item($item, true);

        // Section asserts
        $this->assertTrue($output[$this->get_section_include($this->section1)]);
        $this->assertTrue($output[$this->get_section_userinfo($this->section1)]);
        $this->assertFalse($output[$this->get_section_include($this->section2)]);
        $this->assertFalse($output[$this->get_section_userinfo($this->section2)]);

        // Module asserts
        $this->assertTrue($output[$this->get_module_include($this->module1)]);
        $this->assertTrue($output[$this->get_module_userinfo($this->module1)]);
        $this->assertTrue($output[$this->get_module_include($this->module2)]);
        $this->assertTrue($output[$this->get_module_userinfo($this->module2)]);
        $this->assertFalse($output[$this->get_module_include($this->module3)]);
        $this->assertFalse($output[$this->get_module_userinfo($this->module3)]);


        $item->set_old_instance_id($this->section2->id);
        $output = $this->helper->get_course_settings_by_item($item, true);

        // Section asserts
        $this->assertFalse($output[$this->get_section_include($this->section1)]);
        $this->assertFalse($output[$this->get_section_userinfo($this->section1)]);
        $this->assertTrue($output[$this->get_section_include($this->section2)]);
        $this->assertTrue($output[$this->get_section_userinfo($this->section2)]);

        // Module asserts
        $this->assertFalse($output[$this->get_module_include($this->module1)]);
        $this->assertFalse($output[$this->get_module_userinfo($this->module1)]);
        $this->assertFalse($output[$this->get_module_include($this->module2)]);
        $this->assertFalse($output[$this->get_module_userinfo($this->module2)]);
        $this->assertTrue($output[$this->get_module_include($this->module3)]);
        $this->assertTrue($output[$this->get_module_userinfo($this->module3)]);
    }

    public function test_get_course_settings_by_item_using_activity_item_with_users_false(): void
    {
        $item = new entity();
        $item->set_type('page');
        $item->set_old_instance_id($this->module1->cmid);

        $output = $this->helper->get_course_settings_by_item($item, false);

        // Section asserts
        $this->assertTrue($output[$this->get_section_include($this->section1)]);
        $this->assertFalse($output[$this->get_section_userinfo($this->section1)]);
        $this->assertFalse($output[$this->get_section_include($this->section2)]);
        $this->assertFalse($output[$this->get_section_userinfo($this->section2)]);

        // Module asserts
        $this->assertTrue($output[$this->get_module_include($this->module1)]);
        $this->assertFalse($output[$this->get_module_userinfo($this->module1)]);
        $this->assertFalse($output[$this->get_module_include($this->module2)]);
        $this->assertFalse($output[$this->get_module_userinfo($this->module2)]);
        $this->assertFalse($output[$this->get_module_include($this->module3)]);
        $this->assertFalse($output[$this->get_module_userinfo($this->module3)]);


        $item->set_old_instance_id($this->module2->cmid);
        $output = $this->helper->get_course_settings_by_item($item, false);

        // Section asserts
        $this->assertTrue($output[$this->get_section_include($this->section1)]);
        $this->assertFalse($output[$this->get_section_userinfo($this->section1)]);
        $this->assertFalse($output[$this->get_section_include($this->section2)]);
        $this->assertFalse($output[$this->get_section_userinfo($this->section2)]);

        // Module asserts
        $this->assertFalse($output[$this->get_module_include($this->module1)]);
        $this->assertFalse($output[$this->get_module_userinfo($this->module1)]);
        $this->assertTrue($output[$this->get_module_include($this->module2)]);
        $this->assertFalse($output[$this->get_module_userinfo($this->module2)]);
        $this->assertFalse($output[$this->get_module_include($this->module3)]);
        $this->assertFalse($output[$this->get_module_userinfo($this->module3)]);
    }

    public function test_get_course_settings_by_item_using_activity_item_with_users_true(): void
    {
        $item = new entity();
        $item->set_type('page');
        $item->set_old_instance_id($this->module1->cmid);

        $output = $this->helper->get_course_settings_by_item($item, true);

        // Section asserts
        $this->assertTrue($output[$this->get_section_include($this->section1)]);
        $this->assertTrue($output[$this->get_section_userinfo($this->section1)]);
        $this->assertFalse($output[$this->get_section_include($this->section2)]);
        $this->assertFalse($output[$this->get_section_userinfo($this->section2)]);

        // Module asserts
        $this->assertTrue($output[$this->get_module_include($this->module1)]);
        $this->assertTrue($output[$this->get_module_userinfo($this->module1)]);
        $this->assertFalse($output[$this->get_module_include($this->module2)]);
        $this->assertFalse($output[$this->get_module_userinfo($this->module2)]);
        $this->assertFalse($output[$this->get_module_include($this->module3)]);
        $this->assertFalse($output[$this->get_module_userinfo($this->module3)]);


        $item->set_old_instance_id($this->module2->cmid);
        $output = $this->helper->get_course_settings_by_item($item, true);

        // Section asserts
        $this->assertTrue($output[$this->get_section_include($this->section1)]);
        $this->assertTrue($output[$this->get_section_userinfo($this->section1)]);
        $this->assertFalse($output[$this->get_section_include($this->section2)]);
        $this->assertFalse($output[$this->get_section_userinfo($this->section2)]);

        // Module asserts
        $this->assertFalse($output[$this->get_module_include($this->module1)]);
        $this->assertFalse($output[$this->get_module_userinfo($this->module1)]);
        $this->assertTrue($output[$this->get_module_include($this->module2)]);
        $this->assertTrue($output[$this->get_module_userinfo($this->module2)]);
        $this->assertFalse($output[$this->get_module_include($this->module3)]);
        $this->assertFalse($output[$this->get_module_userinfo($this->module3)]);
    }

    public function test_get_course_settings_by_item_invalid_section_item_id(): void
    {
        // Edge case
        // Adhoc task runs after the section have been deleted
        $item = new entity();
        $item->set_type('section');
        $item->set_old_instance_id($this->module1->cmid);

        $this->expectException(\Exception::class);
        try {
            $this->helper->get_course_settings_by_item($item, true);
        }catch (\Exception $e){
            $this->assertEquals('No section found with that id.',$e->getMessage());
            throw new \Exception('');
        }
    }

    public function test_get_course_settings_by_item_invalid_activity_item_id(): void
    {
        // Edge case
        // Adhoc task runs after the module have been deleted
        $item = new entity();
        $item->set_type('page');
        $item->set_old_instance_id($this->section1->id);

        $this->expectException(\Exception::class);
        try {
            $this->helper->get_course_settings_by_item($item, true);
        }catch (\Exception $e){
            $this->assertEquals('Course module Not found.',$e->getMessage());
            throw new \Exception('');
        }
    }

    public function test_get_course_settings_by_item_empty_section_backup(): void
    {
        // Edge case
        // Adhoc task runs after all modules in a section have been deleted
        // or the section is empty (UI element on empty sections are disabled)
        $item = new entity();
        $item->set_type('section');
        $item->set_old_instance_id($this->section2->id + 1);

        $this->expectException(\Exception::class);
        try {
            $this->helper->get_course_settings_by_item($item, true);
        }catch (\Exception $e){
            $this->assertEquals('No modules to include in section.',$e->getMessage());
            throw new \Exception('');
        }
    }

    public function test_get_course_settings_by_item_course_with_no_modules(): void
    {
        // Edge case
        // Adhoc task runs after all modules in course have been deleted
        $item = new entity();
        $item->set_type('section');
        $item->set_old_instance_id($this->section4->id);

        $this->expectException(\Exception::class);
        try {
            $this->helper->get_course_settings_by_item($item, true);
        }catch (\Exception $e){
            $this->assertEquals('Course have no modules.',$e->getMessage());
            throw new \Exception('');
        }
    }

    protected function generate_courses(): void
    {
        global $DB;

        //Course1
        $this->course1 = self::getDataGenerator()->create_course();

        $this->section1 = $DB->get_record('course_sections',['course' => $this->course1->id,'section' => 0]);
        $this->module1 = self::getDataGenerator()->create_module('page',['course'=> $this->course1->id,'section' => $this->section1->section]);
        $this->module2 = self::getDataGenerator()->create_module('page',['course'=> $this->course1->id,'section' => $this->section1->section]);

        $this->section2 = $DB->get_record('course_sections',['course' => $this->course1->id,'section' => 1]);
        $this->module3 = self::getDataGenerator()->create_module('page',['course'=> $this->course1->id,'section' => $this->section2->section]);

        // Course2
        $this->course2 = self::getDataGenerator()->create_course();

        $this->section3 = $DB->get_record('course_sections',['course' => $this->course2->id,'section' => 0]);
        $this->module4 = self::getDataGenerator()->create_module('page',['course'=> $this->course2->id,'section' => $this->section3->section]);

        // Course3
        $this->course3 = self::getDataGenerator()->create_course();

        $this->section4 = $DB->get_record('course_sections',['course' => $this->course3->id,'section' => 0]);
    }

    protected function get_module_include(\stdClass $module): string
    {
        return 'page_' . $module->cmid . '_included';
    }

    private function get_module_userinfo(\stdClass $module): string
    {
        return 'page_' . $module->cmid . '_userinfo';
    }

    protected function get_section_include(\stdClass $section): string
    {
        return 'section_' . $section->id . '_included';
    }

    private function get_section_userinfo(\stdClass $section): string
    {
        return 'section_' . $section->id . '_userinfo';
    }
}

