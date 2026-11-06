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

use block_sharing_cart\app\factory as basefactory;
use block_sharing_cart\app\item\entity;

/**
 * Builds and applies the section plan of a cart restore.
 *
 * Core places a restored section by the <number> in its section.xml: an existing number is reused (merge), an unused
 * number creates a new section. Modules follow the course_section mapping first and only fall back to their
 * <sectionnumber>. The section number cannot be changed through the restore_controller API, hence the rewrite of the
 * extracted backup on disk.
 *
 * @package   block_sharing_cart
 * @copyright moxis
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class section_planner {
    /** @var basefactory $basefactory */
    private basefactory $basefactory;

    /**
     * __construct
     *
     * @param basefactory $basefactory
     */
    public function __construct(basefactory $basefactory) {
        $this->basefactory = $basefactory;
    }

    /**
     * Decides which sections of the backup are restored and the section number each of them gets.
     *
     * @param \restore_controller $restorecontroller
     * @param entity|false $item the restored cart item
     * @param int $targetsectionid 0 = top level of the course
     * @param bool $insertasnewsection
     * @param int[] $sectionstoinclude old ids of nested sections to restore; empty = all
     * @return section_plan
     */
    public function plan(
        \restore_controller $restorecontroller,
        entity|false $item,
        int $targetsectionid,
        bool $insertasnewsection,
        array $sectionstoinclude
    ): section_plan {
        $db = $this->basefactory->moodle()->db();
        $courseid = (int)$restorecontroller->get_courseid();

        $targetsectionnumber = 0;
        if ($targetsectionid > 0) {
            $targetsectionnumber = (int)$db->get_field(
                'course_sections',
                'section',
                ['id' => $targetsectionid, 'course' => $courseid],
                MUST_EXIST
            );
        }

        $plan = new section_plan($targetsectionid, $targetsectionnumber);

        // Core subsection items and single activity items keep the legacy behaviour.
        if (!$item || !$item->is_section()) {
            if ($targetsectionid === 0) {
                throw new \Exception('Only section items can be restored to the top level of a course.');
            }
            return $plan;
        }

        $plan->rootoldsectionid = (int)$item->get_old_instance_id();
        $plan->selective = true;
        $plan->insertasnewsection = $insertasnewsection;

        $maxnumber = (int)$db->get_field_sql(
            'SELECT MAX(section) FROM {course_sections} WHERE course = ?',
            [$courseid]
        );
        $counter = 0;

        // As a new section the root is planned like a descendant, with the target (or the top level) as parent.
        if ($insertasnewsection) {
            $plan->add_section(
                $plan->rootoldsectionid,
                0,
                (int)($item->get_sortorder() ?? 0),
                $maxnumber + (++$counter)
            );
        }

        $childrenbyparent = $this->section_children_by_parent_item($item);

        $walk = function (entity $parent) use (&$walk, $plan, &$counter, $childrenbyparent, $sectionstoinclude, $maxnumber): void {
            foreach ($childrenbyparent[$parent->get_id()] ?? [] as $child) {
                $oldsectionid = (int)$child->get_old_instance_id();

                // An excluded section drops its whole branch.
                if (!empty($sectionstoinclude) && !in_array($oldsectionid, $sectionstoinclude, true)) {
                    continue;
                }

                $plan->add_section(
                    $oldsectionid,
                    (int)$parent->get_old_instance_id(),
                    (int)($child->get_sortorder() ?? 0),
                    $maxnumber + (++$counter)
                );

                $walk($child);
            }
        };
        $walk($item);

        return $plan;
    }

    /**
     * Rewrite the section numbers in the extracted backup and switch off the section (and module) tasks that are not
     * part of the plan.
     *
     * @param \restore_controller $restorecontroller
     * @param section_plan $plan
     * @return void
     */
    public function apply(\restore_controller $restorecontroller, section_plan $plan): void {
        $targetnumber = $plan->targetsectionnumber;
        $planned = $plan->sections;

        // Old module id => old section id, and delegated (core subsection) section id => the section owning its
        // parent module, used to tell whether something belongs to an included branch.
        $modulesections = [];
        foreach ($restorecontroller->get_info()->activities ?? [] as $activity) {
            $modulesections[(int)$activity->moduleid] = (int)$activity->sectionid;
        }
        $delegatedowners = [];
        foreach ($restorecontroller->get_info()->sections ?? [] as $section) {
            if (!empty($section->parentcmid) && isset($modulesections[(int)$section->parentcmid])) {
                $delegatedowners[(int)$section->sectionid] = $modulesections[(int)$section->parentcmid];
            }
        }

        $includedsectionids = $plan->included_section_ids();
        $isincluded = static function (int $oldsectionid) use ($includedsectionids, $delegatedowners): bool {
            $oldsectionid = $delegatedowners[$oldsectionid] ?? $oldsectionid;
            return in_array($oldsectionid, $includedsectionids, true);
        };

        foreach ($restorecontroller->get_plan()->get_tasks() as $task) {
            if ($task instanceof \restore_activity_task) {
                $oldsectionid = $modulesections[(int)$task->get_old_moduleid()] ?? 0;
                if ($plan->selective && !$isincluded($oldsectionid)) {
                    // Its section is not restored, so the module must not be either (whatever the modal sent).
                    $task->get_setting('included')->set_value(false);
                    continue;
                }
                if (!$plan->selective || !isset($planned[$oldsectionid])) {
                    $this->rewrite_xml_number("{$task->get_taskbasepath()}/module.xml", 'sectionnumber', $targetnumber);
                }
                continue;
            }

            if (!($task instanceof \restore_section_task)) {
                continue;
            }

            $oldsectionid = $this->old_section_id_of_task($task);
            $sectionxmlpath = "{$task->get_taskbasepath()}/section.xml";

            if (!$plan->selective) {
                // Legacy behaviour: everything merges into the target section.
                $this->rewrite_xml_number($sectionxmlpath, 'number', $targetnumber);
                continue;
            }

            if ($task->get_delegated_cm() !== null) {
                // Core numbers delegated sections itself; only make sure excluded branches stay excluded.
                if (!$isincluded($oldsectionid)) {
                    mtrace("...Excluding delegated section (id: $oldsectionid)");
                    $task->get_setting('included')->set_value(false);
                }
                continue;
            }

            if (isset($planned[$oldsectionid])) {
                // A descendant, or the root itself when it is inserted as a new section.
                $newnumber = (int)$planned[$oldsectionid]->new_section_number;
                mtrace("...Restoring section (id: $oldsectionid) as new section number $newnumber");
                $this->rewrite_xml_number($sectionxmlpath, 'number', $newnumber);
            } else if ($oldsectionid === $plan->rootoldsectionid) {
                $this->rewrite_xml_number($sectionxmlpath, 'number', $targetnumber);
            } else {
                mtrace("...Excluding section (id: $oldsectionid)");
                $task->get_setting('included')->set_value(false);
            }
        }
    }

    /**
     * Map the planned sections to the sections core created, in depth-first order.
     *
     * The backup_ids temp table is dropped by the last restore step, but every section task still holds the id of
     * the section it created or merged into (set by restore_section_structure_step::process_section).
     *
     * @param \restore_controller $restorecontroller
     * @param section_plan $plan
     * @return object[] {old_section_id, new_section_id, new_parent_section_id, sort_order}
     */
    public function resolve_restored_sections(\restore_controller $restorecontroller, section_plan $plan): array {
        $createdids = [];
        foreach ($restorecontroller->get_plan()->get_tasks() as $task) {
            if ($task instanceof \restore_section_task) {
                $createdids[$this->old_section_id_of_task($task)] = (int)$task->get_sectionid();
            }
        }

        $newids = [];
        $restored = [];
        foreach ($plan->sections as $planned) {
            $newsectionid = $createdids[(int)$planned->old_section_id] ?? 0;

            if ($newsectionid === 0 || $newsectionid === $plan->targetsectionid) {
                mtrace("...No new section found for nested section (id: {$planned->old_section_id}), skipping");
                continue;
            }

            $newids[$planned->old_section_id] = $newsectionid;

            // The parent is the new id of the parent section when that was created in this restore (a descendant,
            // or the root inserted as a new section); otherwise it is the target section (or 0 = top level).
            $restored[] = (object)[
                'old_section_id' => (int)$planned->old_section_id,
                'new_section_id' => $newsectionid,
                'new_parent_section_id' => $newids[(int)$planned->old_parent_section_id] ?? $plan->targetsectionid,
                'sort_order' => (int)$planned->sort_order,
            ];
        }

        return $restored;
    }

    /**
     * Generic placement: put the restored sections directly after the target section, keeping depth-first order.
     * Nesting formats reorder afterwards through the after_sections_restored hook.
     *
     * @param int $courseid
     * @param int $targetsectionid 0 = top level of the course
     * @param object[] $restoredsections as returned by resolve_restored_sections()
     * @return void
     */
    public function place_after_target(int $courseid, int $targetsectionid, array $restoredsections): void {
        // Top level of the course: the fresh numbers already put the new sections at the end.
        if (empty($restoredsections) || $targetsectionid === 0) {
            return;
        }

        $actions = \core_courseformat\formatactions::section($courseid);

        $precedingsectionid = $targetsectionid;
        foreach ($restoredsections as $restored) {
            $modinfo = get_fast_modinfo($courseid);
            $section = $modinfo->get_section_info_by_id($restored->new_section_id, IGNORE_MISSING);
            $preceding = $modinfo->get_section_info_by_id($precedingsectionid, IGNORE_MISSING);
            if (!$section || !$preceding) {
                continue;
            }

            $actions->move_after($section, $preceding);
            $precedingsectionid = $restored->new_section_id;
        }
    }

    /**
     * Child section items keyed by parent item id, siblings in cart order.
     *
     * @param entity $item the restored cart item
     * @return array<int, entity[]>
     */
    private function section_children_by_parent_item(entity $item): array {
        $childrenbyparent = [];
        foreach ($this->basefactory->item()->repository()->get_recursively_by_parent_id($item->get_id()) as $descendant) {
            if (!$descendant->is_section() || $descendant->get_parent_item_id() === null) {
                continue;
            }
            $childrenbyparent[$descendant->get_parent_item_id()][] = $descendant;
        }
        foreach ($childrenbyparent as &$siblings) {
            usort($siblings, static function (entity $a, entity $b): int {
                return (($a->get_sortorder() ?? 0) <=> ($b->get_sortorder() ?? 0)) ?: ($a->get_id() <=> $b->get_id());
            });
        }
        unset($siblings);

        return $childrenbyparent;
    }

    /**
     * Original (backup side) id of the section a section task restores. restore_task::get_info() returns the whole
     * backup manifest, so the id is read from the task directory, which core names sections/section_<oldid>.
     *
     * @param \restore_section_task $task
     * @return int
     */
    private function old_section_id_of_task(\restore_section_task $task): int {
        return (int)substr(basename($task->get_taskbasepath()), strlen('section_'));
    }

    /**
     * Sets a numeric element of an extracted backup XML file.
     *
     * @param string $path
     * @param string $element
     * @param int $value
     * @return void
     */
    private function rewrite_xml_number(string $path, string $element, int $value): void {
        $xml = simplexml_load_string(file_get_contents($path));
        $xml->{$element} = $value;
        $xml->asXML($path);
    }
}
