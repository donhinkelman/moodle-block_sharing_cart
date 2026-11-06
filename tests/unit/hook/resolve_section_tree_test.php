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

namespace block_sharing_cart\unit\hook;

use block_sharing_cart\hook\backup\resolve_section_tree;

/**
 * Tests for the resolve_section_tree hook.
 *
 * @package   block_sharing_cart
 * @copyright moxis
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \block_sharing_cart\hook\backup\resolve_section_tree
 */
final class resolve_section_tree_test extends \basic_testcase {
    /**
     * Descendants come out depth-first, siblings by sort order.
     *
     * @return void
     */
    public function test_tree_is_depth_first_and_ordered_by_sort_order(): void {
        $hook = new resolve_section_tree(courseid: 1, sectionid: 10);

        // Added out of order on purpose.
        $hook->add_child(sectionid: 12, parentsectionid: 10, sortorder: 2);
        $hook->add_child(sectionid: 111, parentsectionid: 11, sortorder: 1);
        $hook->add_child(sectionid: 11, parentsectionid: 10, sortorder: 1);
        $hook->add_child(sectionid: 1111, parentsectionid: 111, sortorder: 1);

        $ids = array_map(static fn(object $node): int => $node->section_id, $hook->get_tree());

        $this->assertSame([11, 111, 1111, 12], $ids);
    }

    /**
     * Unreachable nodes and self references are dropped.
     *
     * @return void
     */
    public function test_unreachable_nodes_and_self_references_are_dropped(): void {
        $hook = new resolve_section_tree(courseid: 1, sectionid: 10);

        $hook->add_child(sectionid: 11, parentsectionid: 10, sortorder: 1);
        $hook->add_child(sectionid: 99, parentsectionid: 42, sortorder: 1); // Parent is not in the tree.
        $hook->add_child(sectionid: 10, parentsectionid: 11, sortorder: 1); // The root itself.
        $hook->add_child(sectionid: 13, parentsectionid: 13, sortorder: 1); // Self reference.

        $ids = array_map(static fn(object $node): int => $node->section_id, $hook->get_tree());

        $this->assertSame([11], $ids);
    }

    /**
     * Empty tree for flat formats.
     *
     * @return void
     */
    public function test_empty_tree_for_flat_formats(): void {
        $hook = new resolve_section_tree(courseid: 1, sectionid: 10);

        $this->assertSame([], $hook->get_tree());
    }
}
