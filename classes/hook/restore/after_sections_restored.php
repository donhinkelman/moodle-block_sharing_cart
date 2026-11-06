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
 * Dispatched inside the sharing cart restore task after the restore plan executed and the new sections were placed
 * after the target section.
 *
 * A course format that nests sections should attach the restored sections to its hierarchy here.
 *
 * @package   block_sharing_cart
 * @copyright moxis
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\label('Dispatched after a sharing cart restore created nested sections; lets a course format attach them')]
#[\core\attribute\tags('restore')]
final class after_sections_restored {
    /**
     * Hook for one finished sharing cart restore.
     *
     * @param int $courseid target course
     * @param int $targetsectionid the section the root merged into; 0 = top level of the course
     * @param object[] $restoredsections depth-first list of
     *                                   {old_section_id, new_section_id, new_parent_section_id, sort_order};
     *                                   new_parent_section_id is the target section for first level children.
     */
    public function __construct(
        /** @var int target course */
        public readonly int $courseid,
        /** @var int the section the root merged into; 0 = top level of the course */
        public readonly int $targetsectionid,
        /** @var object[] the sections the restore created */
        public readonly array $restoredsections,
    ) {
    }
}
