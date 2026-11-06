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

namespace block_sharing_cart\hook\restore;

/**
 * Dispatched inside the sharing cart restore task right before the restore plan executes.
 *
 * A course format can use it to mark the restore as "section structure managed by the sharing cart", so that its own
 * format restore plugin does not merge or reposition the sections that the cart is about to create.
 *
 * @package   block_sharing_cart
 * @copyright moxis
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\label('Dispatched before a sharing cart restore executes; lets a course format take over section handling')]
#[\core\attribute\tags('restore')]
final class before_sections_restored {
    /**
     * Hook for one sharing cart restore.
     *
     * @param string $restoreid id of the restore controller
     * @param int $courseid target course
     * @param int $targetsectionid section chosen by the user; 0 = top level of the course
     * @param object[] $plannedsections depth-first list of
     *                                  {old_section_id, old_parent_section_id, sort_order, new_section_number}
     */
    public function __construct(
        /** @var string id of the restore controller */
        public readonly string $restoreid,
        /** @var int target course */
        public readonly int $courseid,
        /** @var int section chosen by the user; 0 = top level of the course */
        public readonly int $targetsectionid,
        /** @var object[] sections the restore is about to create or merge */
        public readonly array $plannedsections,
    ) {
    }
}
