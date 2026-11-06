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

namespace block_sharing_cart\app\restore;

/**
 * Which sections of a cart backup are restored, and the section number each of them gets.
 *
 * The root section (the restored item's own) either merges into the target section or, with insert_as_new_section,
 * is created as a new section under it (target 0 = top level of the course). Descendant sections always become new
 * sections. Everything else in the backup is excluded.
 *
 * @package   block_sharing_cart
 * @copyright moxis
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class section_plan {
    /** @var int Original id of the restored item's own section; 0 for legacy (non section) items. */
    public int $rootoldsectionid = 0;

    /** @var int Section chosen by the user; 0 = top level of the course. */
    public int $targetsectionid;

    /** @var int Section number of the target section; 0 at the top level of the course. */
    public int $targetsectionnumber;

    /** @var bool Whether the root is created as a new section instead of merging into the target. */
    public bool $insertasnewsection = false;

    /** @var bool True when the restored item is a section item and other sections in the backup must be excluded. */
    public bool $selective = false;

    /**
     * Sections to create, keyed by old section id, in depth-first order:
     * {old_section_id, old_parent_section_id, sort_order, new_section_number}.
     *
     * @var object[]
     */
    public array $sections = [];

    /**
     * Plan for one target section.
     *
     * @param int $targetsectionid section chosen by the user; 0 = top level of the course
     * @param int $targetsectionnumber its section number
     */
    public function __construct(int $targetsectionid, int $targetsectionnumber) {
        $this->targetsectionid = $targetsectionid;
        $this->targetsectionnumber = $targetsectionnumber;
    }

    /**
     * Plans a section that the restore creates as a new section.
     *
     * @param int $oldsectionid section id in the backup
     * @param int $oldparentsectionid parent section id in the backup
     * @param int $sortorder position among its siblings
     * @param int $newsectionnumber section number the section gets in the target course
     * @return void
     */
    public function add_section(
        int $oldsectionid,
        int $oldparentsectionid,
        int $sortorder,
        int $newsectionnumber
    ): void {
        $this->sections[$oldsectionid] = (object)[
            'old_section_id' => $oldsectionid,
            'old_parent_section_id' => $oldparentsectionid,
            'sort_order' => $sortorder,
            'new_section_number' => $newsectionnumber,
        ];
    }

    /**
     * Whether the restore creates any new sections.
     *
     * @return bool
     */
    public function has_sections(): bool {
        return !empty($this->sections);
    }

    /**
     * Old ids of every section that is restored: the root plus the planned ones.
     *
     * @return int[]
     */
    public function included_section_ids(): array {
        return array_merge([$this->rootoldsectionid], array_keys($this->sections));
    }

    /**
     * The root merges into an existing section, so its title and description can be replaced.
     *
     * @return bool
     */
    public function merges_into_target(): bool {
        return $this->selective && !$this->insertasnewsection && $this->targetsectionid > 0;
    }
}
