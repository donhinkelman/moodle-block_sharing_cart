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

use block_sharing_cart\app\factory;
use block_sharing_cart\app\item\entity;
use block_sharing_cart\app\restore\section_details_replacement;
use block_sharing_cart\external\backup\section_into_sharing_cart;
use block_sharing_cart\external\restore\item_into_section;
use block_sharing_cart\hook\backup\resolve_section_tree;
use block_sharing_cart\hook\restore\after_sections_restored;
use block_sharing_cart\hook\restore\before_sections_restored;
use block_sharing_cart\task\asynchronous_backup_task;
use block_sharing_cart\task\asynchronous_restore_task;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * Async restore tests for the Sharing Cart block.
 *
 * Nested section copy and restore, driven through the section hierarchy hooks with a test callback standing in for a
 * nesting course format. Uses plain topics courses: to the cart a "descendant" is just another section id.
 *
 * @package   block_sharing_cart
 * @copyright moxis
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \block_sharing_cart\task\asynchronous_restore_task
 */
final class asynchronous_restore_task_test extends \advanced_testcase
{
    /** @var factory $factory */
    private factory $factory;

    /**
     * setUp
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->factory = factory::make();

        // The cart tasks mtrace their progress.
        $this->expectOutputRegex('/.*/');
    }

    /**
     * Topics course with the given number of sections, optionally named.
     *
     * @param int $numsections
     * @param array $names section number => name
     * @return object course with a 'sections' map: section number => course_sections record
     */
    private function create_course(int $numsections, array $names = []): object {
        global $DB;

        $course = $this->getDataGenerator()->create_course(['format' => 'topics', 'numsections' => $numsections]);
        foreach ($names as $number => $name) {
            $DB->set_field('course_sections', 'name', $name, ['course' => $course->id, 'section' => $number]);
        }

        $course->sections = [];
        foreach ($DB->get_records('course_sections', ['course' => $course->id], 'section ASC') as $section) {
            $course->sections[(int)$section->section] = $section;
        }

        return $course;
    }

    /**
     * Adds a page activity to a section of the course.
     *
     * @param object $course as returned by create_course()
     * @param int $sectionnum
     * @param string $name
     */
    private function add_page(object $course, int $sectionnum, string $name): void {
        $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'section' => $sectionnum,
            'name' => $name,
        ]);
    }

    /**
     * Stand in for a nesting course format.
     *
     * @param array $tree list of [section_id, parent_section_id, sort_order]
     */
    private function declare_section_tree(array $tree): void {
        \core\di::get(\core\hook\manager::class)->phpunit_redirect_hook(
            resolve_section_tree::class,
            static function (resolve_section_tree $hook) use ($tree): void {
                foreach ($tree as [$sectionid, $parentsectionid, $sortorder]) {
                    $hook->add_child((int)$sectionid, (int)$parentsectionid, (int)$sortorder);
                }
            }
        );
    }

    /**
     * Copies a section into the cart through the web service and runs the backup.
     *
     * @param int $sectionid
     * @return int id of the root cart item
     */
    private function copy_section_to_cart(int $sectionid): int {
        $result = section_into_sharing_cart::execute($sectionid, ['users' => false, 'anonymize' => false]);
        $this->runAdhocTasks(asynchronous_backup_task::class);

        $item = $this->factory->item()->repository()->get_by_id((int)$result->id);
        $this->assertSame(entity::STATUS_BACKEDUP, $item->get_status());

        return (int)$result->id;
    }

    /**
     * Queues a restore through the web service and runs it.
     *
     * @param int $itemid
     * @param int $sectionid
     * @param bool $insertasnewsection
     * @param int $courseid
     * @param bool $replacesectiondetails
     */
    private function restore(
        int $itemid,
        int $sectionid,
        bool $insertasnewsection = false,
        int $courseid = 0,
        bool $replacesectiondetails = false
    ): void {
        $this->assertTrue(item_into_section::execute(
            $itemid,
            $sectionid,
            [],
            [],
            $insertasnewsection,
            $courseid,
            $replacesectiondetails
        ));
        $this->runAdhocTasks(asynchronous_restore_task::class);
    }

    /**
     * Names of the course modules in a section, sorted alphabetically.
     *
     * @param int $sectionid
     * @return string[]
     */
    private function module_names(int $sectionid): array {
        global $DB;

        $sequence = (string)$DB->get_field('course_sections', 'sequence', ['id' => $sectionid], MUST_EXIST);
        $names = [];
        foreach (array_filter(explode(',', $sequence)) as $cmid) {
            $names[] = get_coursemodule_from_id('', (int)$cmid, 0, false, MUST_EXIST)->name;
        }
        sort($names);

        return $names;
    }

    /**
     * Sections of a course keyed by name.
     *
     * @param int $courseid
     * @return array<string, object> course_sections records keyed by name
     */
    private function sections_by_name(int $courseid): array {
        global $DB;

        $byname = [];
        foreach ($DB->get_records('course_sections', ['course' => $courseid], 'section ASC') as $section) {
            $byname[(string)$section->name] = $section;
        }

        return $byname;
    }

    /**
     * Cart items below a root item, grouped by parent.
     *
     * @param int $rootitemid
     * @return array<int, entity[]> child items keyed by parent item id, keyed again by name
     */
    private function items_by_parent(int $rootitemid): array {
        $byparent = [];
        foreach ($this->factory->item()->repository()->get_recursively_by_parent_id($rootitemid) as $item) {
            if ($item->get_parent_item_id() !== null) {
                $byparent[$item->get_parent_item_id()][$item->get_name()] = $item;
            }
        }

        return $byparent;
    }

    public function test_nested_sections_are_restored_after_the_target_section(): void {
        $source = $this->create_course(3, [1 => 'Root', 2 => 'Child', 3 => 'Grandchild']);
        $this->add_page($source, 1, 'Page root');
        $this->add_page($source, 2, 'Page child');
        $this->add_page($source, 3, 'Page grandchild');
        $this->declare_section_tree([
            [$source->sections[2]->id, $source->sections[1]->id, 1],
            [$source->sections[3]->id, $source->sections[2]->id, 1],
        ]);

        $rootitemid = $this->copy_section_to_cart((int)$source->sections[1]->id);

        // The cart holds the tree: Root > [Page root, Child > [Page child, Grandchild > [Page grandchild]]].
        $byparent = $this->items_by_parent($rootitemid);
        $this->assertEqualsCanonicalizing(['Page root', 'Child'], array_keys($byparent[$rootitemid]));
        $childitem = $byparent[$rootitemid]['Child'];
        $this->assertTrue($childitem->is_section());
        $this->assertEqualsCanonicalizing(['Page child', 'Grandchild'], array_keys($byparent[$childitem->get_id()]));
        $grandchilditem = $byparent[$childitem->get_id()]['Grandchild'];
        $this->assertEqualsCanonicalizing(['Page grandchild'], array_keys($byparent[$grandchilditem->get_id()]));

        $target = $this->create_course(2, [1 => 'Target', 2 => 'After']);
        $this->add_page($target, 1, 'Page target');
        $targetsectionid = (int)$target->sections[1]->id;

        $payload = null;
        \core\di::get(\core\hook\manager::class)->phpunit_redirect_hook(
            after_sections_restored::class,
            static function (after_sections_restored $hook) use (&$payload): void {
                $payload = $hook;
            }
        );

        $this->restore($rootitemid, $targetsectionid);

        // The root merged into the target; the descendants are new sections placed directly after it.
        $this->assertSame(['Page root', 'Page target'], $this->module_names($targetsectionid));

        $sections = $this->sections_by_name($target->id);
        $this->assertEqualsCanonicalizing(['', 'Target', 'Child', 'Grandchild', 'After'], array_keys($sections));
        $this->assertSame(2, (int)$sections['Child']->section);
        $this->assertSame(3, (int)$sections['Grandchild']->section);
        $this->assertSame(4, (int)$sections['After']->section);
        $this->assertSame(['Page child'], $this->module_names((int)$sections['Child']->id));
        $this->assertSame(['Page grandchild'], $this->module_names((int)$sections['Grandchild']->id));

        // The format is told the new hierarchy.
        $this->assertNotNull($payload);
        $this->assertSame((int)$target->id, $payload->courseid);
        $this->assertSame($targetsectionid, $payload->targetsectionid);
        $this->assertCount(2, $payload->restoredsections);
        [$child, $grandchild] = $payload->restoredsections;
        $this->assertSame((int)$sections['Child']->id, $child->new_section_id);
        $this->assertSame($targetsectionid, $child->new_parent_section_id);
        $this->assertSame((int)$sections['Grandchild']->id, $grandchild->new_section_id);
        $this->assertSame((int)$sections['Child']->id, $grandchild->new_parent_section_id);
    }

    public function test_section_without_activities_but_with_populated_descendants_can_be_copied(): void {
        $source = $this->create_course(2, [1 => 'Structural parent', 2 => 'Child']);
        $this->add_page($source, 2, 'Page child');
        $this->declare_section_tree([[$source->sections[2]->id, $source->sections[1]->id, 1]]);

        $rootitemid = $this->copy_section_to_cart((int)$source->sections[1]->id);

        $byparent = $this->items_by_parent($rootitemid);
        $this->assertSame(['Child'], array_keys($byparent[$rootitemid]));
        $this->assertSame(['Page child'], array_keys($byparent[$byparent[$rootitemid]['Child']->get_id()]));
    }

    public function test_empty_section_without_descendants_is_rejected(): void {
        $source = $this->create_course(1, [1 => 'Empty']);

        $this->expectException(\moodle_exception::class);
        section_into_sharing_cart::execute((int)$source->sections[1]->id, ['users' => false, 'anonymize' => false]);
    }

    public function test_replace_section_details_takes_the_copied_title_and_description(): void {
        global $DB;

        $source = $this->create_course(1, [1 => 'Copied']);
        $this->add_page($source, 1, 'Page copied');
        $DB->set_field('course_sections', 'summary', '<p>Copied summary</p>', ['id' => $source->sections[1]->id]);
        $rootitemid = $this->copy_section_to_cart((int)$source->sections[1]->id);

        $target = $this->create_course(1, [1 => 'Target']);
        $targetsectionid = (int)$target->sections[1]->id;
        $DB->set_field('course_sections', 'summary', '<p>Target summary</p>', ['id' => $targetsectionid]);
        $this->add_section_file($target->id, $targetsectionid, 'old.txt');

        $this->restore($rootitemid, $targetsectionid, replacesectiondetails: true);

        $section = $DB->get_record('course_sections', ['id' => $targetsectionid], '*', MUST_EXIST);
        $this->assertSame('Copied', $section->name);
        $this->assertStringContainsString('Copied summary', $section->summary);
        $this->assertFalse($this->section_file_exists($target->id, $targetsectionid, 'old.txt'));
        $this->assertFalse($this->factory->restore()->section_details_replacement()->has_snapshot($target->id, $targetsectionid));
        $this->assertSame(['Page copied'], $this->module_names($targetsectionid));
    }

    public function test_replace_section_details_is_rolled_back_when_the_restore_fails(): void {
        global $DB;

        $source = $this->create_course(1, [1 => 'Copied']);
        $this->add_page($source, 1, 'Page copied');
        $rootitemid = $this->copy_section_to_cart((int)$source->sections[1]->id);

        $target = $this->create_course(1, [1 => 'Target']);
        $targetsectionid = (int)$target->sections[1]->id;
        $DB->set_field('course_sections', 'summary', '<p>Target summary</p>', ['id' => $targetsectionid]);
        $this->add_section_file($target->id, $targetsectionid, 'keep.txt');

        // Fail after the details were blanked and before the plan runs.
        \core\di::get(\core\hook\manager::class)->phpunit_redirect_hook(
            before_sections_restored::class,
            static function (before_sections_restored $hook): void {
                throw new \Exception('Simulated restore failure');
            }
        );

        $this->restore($rootitemid, $targetsectionid, replacesectiondetails: true);

        $section = $DB->get_record('course_sections', ['id' => $targetsectionid], '*', MUST_EXIST);
        $this->assertSame('Target', $section->name);
        $this->assertSame('<p>Target summary</p>', $section->summary);
        $this->assertTrue($this->section_file_exists($target->id, $targetsectionid, 'keep.txt'));
        $this->assertFalse($this->factory->restore()->section_details_replacement()->has_snapshot($target->id, $targetsectionid));
        $this->assertSame([], $this->module_names($targetsectionid));
    }

    public function test_insert_as_new_top_level_section(): void {
        $source = $this->create_course(2, [1 => 'Copied', 2 => 'Child']);
        $this->add_page($source, 1, 'Page copied');
        $this->add_page($source, 2, 'Page child');
        $this->declare_section_tree([[$source->sections[2]->id, $source->sections[1]->id, 1]]);
        $rootitemid = $this->copy_section_to_cart((int)$source->sections[1]->id);

        $target = $this->create_course(1, [1 => 'Existing']);

        $payload = null;
        \core\di::get(\core\hook\manager::class)->phpunit_redirect_hook(
            after_sections_restored::class,
            static function (after_sections_restored $hook) use (&$payload): void {
                $payload = $hook;
            }
        );

        $this->restore($rootitemid, 0, insertasnewsection: true, courseid: (int)$target->id);

        $sections = $this->sections_by_name($target->id);
        $this->assertEqualsCanonicalizing(['', 'Existing', 'Copied', 'Child'], array_keys($sections));
        $this->assertSame(2, (int)$sections['Copied']->section);
        $this->assertSame(3, (int)$sections['Child']->section);
        $this->assertSame(['Page copied'], $this->module_names((int)$sections['Copied']->id));
        $this->assertSame(['Page child'], $this->module_names((int)$sections['Child']->id));
        $this->assertSame([], $this->module_names((int)$sections['Existing']->id));

        $this->assertSame(0, $payload->targetsectionid);
        [$copied, $child] = $payload->restoredsections;
        $this->assertSame(0, $copied->new_parent_section_id);
        $this->assertSame((int)$sections['Copied']->id, $child->new_parent_section_id);
    }

    /**
     * Adds a file to a section's summary file area.
     *
     * @param int $courseid
     * @param int $sectionid
     * @param string $filename
     */
    private function add_section_file(int $courseid, int $sectionid, string $filename): void {
        get_file_storage()->create_file_from_string([
            'contextid' => \core\context\course::instance($courseid)->id,
            'component' => 'course',
            'filearea' => 'section',
            'itemid' => $sectionid,
            'filepath' => '/',
            'filename' => $filename,
        ], 'content');
    }

    /**
     * Whether a section's summary file area holds the file.
     *
     * @param int $courseid
     * @param int $sectionid
     * @param string $filename
     * @return bool
     */
    private function section_file_exists(int $courseid, int $sectionid, string $filename): bool {
        return get_file_storage()->file_exists(
            \core\context\course::instance($courseid)->id,
            'course',
            'section',
            $sectionid,
            '/',
            $filename
        );
    }

    /**
     * create_section
     *
     * @param int $courseid
     * @param array $record
     * @return object
     */
    private function create_section(int $courseid, array $record = []): object {
        $db = $this->factory->moodle()->db();

        $record['course'] = $courseid;

        if (!isset($record['section'])) {
            $lastsectionnumber = (int)$db->get_field(
                'course_sections',
                'MAX(section)',
                ['course' => $courseid]
            );
            $record['section'] = $lastsectionnumber + 1;
        }

        $section = self::getDataGenerator()->create_course_section($record);
        return $db->get_record(
            'course_sections',
            ['id' => $section->id],
            '*',
            MUST_EXIST
        );
    }

    /**
     * backup_label_into_cart
     *
     * @param object $course
     * @param object $section
     * @param object $user
     * @return entity
     */
    private function backup_label_into_cart(object $course, object $section, object $user): entity {
        $generator = self::getDataGenerator();
        $label = $generator->create_module('label', [
            'course' => $course->id,
            'section' => $section->section,
            'name' => 'Cart Label',
            'intro' => 'Cart label intro',
        ]);

        $item = $this->factory->item()->repository()->insert_activity(
            $label->cmid,
            $user->id,
            null,
            entity::STATUS_AWAITING_BACKUP
        );

        $task = $this->factory->backup()->handler()->backup_course_module(
            $label->cmid,
            $item,
            [
                'users' => false,
                'anonymize' => false,
            ]
        );
        $ref = new \ReflectionProperty($task, 'output');
        $ref->setAccessible(true);
        $ref->setValue($task, false);
        $task->execute();

        $item = $this->factory->item()->repository()->get_by_id($item->get_id());
        self::assertNotNull($item);
        self::assertSame(entity::STATUS_BACKEDUP, $item->get_status());
        self::assertNotNull(
            $this->factory->item()->repository()->get_stored_file_by_item($item)
        );

        return $item;
    }

    /**
     * test_queue_does_not_create_backup_controller_or_tempdir
     *
     * @return void
     */
    public function test_queue_does_not_create_backup_controller_or_tempdir(): void {
        global $USER, $DB;

        self::setAdminUser();

        $generator = self::getDataGenerator();
        $source = $generator->create_course();
        $target = $generator->create_course();
        $sourcesection = $this->create_section($source->id, ['name' => 'Source']);
        $targetsection = $this->create_section($target->id, ['name' => 'Target']);
        $generator->enrol_user($USER->id, $source->id, 'editingteacher');
        $generator->enrol_user($USER->id, $target->id, 'editingteacher');

        $item = $this->backup_label_into_cart($source, $sourcesection, $USER);

        $controllersbefore = $DB->count_records('backup_controllers');

        $restoretask = $this->factory->restore()->handler()->restore_item_into_section(
            $item,
            $targetsection->id,
            $item->get_id(),
            [
                'course_modules_to_include' => [(int)$item->get_old_instance_id()],
            ]
        );
        self::assertInstanceOf(asynchronous_restore_task::class, $restoretask);

        $customdata = $restoretask->get_custom_data();
        self::assertObjectNotHasProperty('backupid', $customdata);
        self::assertSame((int)$target->id, (int)$customdata->courseid);
        self::assertNotEmpty($customdata->item);
        self::assertSame(
            (int)$targetsection->id,
            (int)($customdata->backup_settings->move_to_section_id ?? 0)
        );
        self::assertSame(
            [(int)$item->get_old_instance_id()],
            array_map('intval', (array)($customdata->backup_settings->course_modules_to_include ?? []))
        );

        self::assertSame($controllersbefore, $DB->count_records('backup_controllers'));
    }

    /**
     * test_restore_extracts_and_succeeds_on_task_execute
     *
     * @return void
     */
    public function test_restore_extracts_and_succeeds_on_task_execute(): void {
        global $USER, $DB;

        self::setAdminUser();

        $generator = self::getDataGenerator();
        $source = $generator->create_course();
        $target = $generator->create_course();
        $sourcesection = $this->create_section($source->id, ['name' => 'Source']);
        $targetsection = $this->create_section($target->id, ['name' => 'Target']);
        $generator->enrol_user($USER->id, $source->id, 'editingteacher');
        $generator->enrol_user($USER->id, $target->id, 'editingteacher');

        $item = $this->backup_label_into_cart($source, $sourcesection, $USER);

        $cmsbefore = $DB->count_records('course_modules', ['course' => $target->id]);

        $restoretask = $this->factory->restore()->handler()->restore_item_into_section(
            $item,
            $targetsection->id,
            $item->get_id(),
            [
                'course_modules_to_include' => [(int)$item->get_old_instance_id()],
            ]
        );
        self::assertInstanceOf(asynchronous_restore_task::class, $restoretask);

        ob_start();
        $restoretask->execute();
        ob_get_clean();

        $cmsafter = $DB->count_records('course_modules', ['course' => $target->id]);
        self::assertGreaterThan($cmsbefore, $cmsafter);
    }
}
