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

namespace block_sharing_cart\hook\backup;

/**
 * Lets a course format declare the descendant sections of a section that is being copied into the sharing cart.
 *
 * Callbacks call add_child() for every descendant (any depth). The cart includes those sections in the backup and
 * records the tree so it can be recreated on restore.
 *
 * @package   block_sharing_cart
 * @copyright moxis
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\label('Lets a course format add the nested child sections of a section copied to the sharing cart')]
#[\core\attribute\tags('backup')]
final class resolve_section_tree {
    /** @var object[] keyed by section id: {section_id, parent_section_id, sort_order} */
    private array $children = [];

    /**
     * Hook for one copied section.
     *
     * @param int $courseid course of the copied section
     * @param int $sectionid the copied section
     */
    public function __construct(
        /** @var int course of the copied section */
        public readonly int $courseid,
        /** @var int the copied section */
        public readonly int $sectionid,
    ) {
    }

    /**
     * Declares a descendant of the copied section. The copied section itself and self references are ignored.
     *
     * @param int $sectionid descendant section
     * @param int $parentsectionid its parent: the copied section or another descendant
     * @param int $sortorder position among its siblings
     * @return void
     */
    public function add_child(int $sectionid, int $parentsectionid, int $sortorder): void {
        if ($sectionid === $this->sectionid || $sectionid === $parentsectionid) {
            return;
        }

        $this->children[$sectionid] = (object)[
            'section_id' => $sectionid,
            'parent_section_id' => $parentsectionid,
            'sort_order' => $sortorder,
        ];
    }

    /**
     * Descendants in depth-first order (parents before children, siblings by sort order). The copied section itself
     * is not part of the list, and nodes that cannot be reached from it are dropped.
     *
     * @return object[]
     */
    public function get_tree(): array {
        $byparent = [];
        foreach ($this->children as $child) {
            $byparent[$child->parent_section_id][] = $child;
        }
        foreach ($byparent as &$siblings) {
            usort($siblings, static function (object $a, object $b): int {
                return ($a->sort_order <=> $b->sort_order) ?: ($a->section_id <=> $b->section_id);
            });
        }
        unset($siblings);

        $tree = [];
        $visited = [$this->sectionid => true];
        $walk = function (int $parentid) use (&$walk, &$tree, &$visited, $byparent): void {
            foreach ($byparent[$parentid] ?? [] as $child) {
                if (isset($visited[$child->section_id])) {
                    continue;
                }
                $visited[$child->section_id] = true;
                $tree[] = $child;
                $walk($child->section_id);
            }
        };
        $walk($this->sectionid);

        return $tree;
    }
}
