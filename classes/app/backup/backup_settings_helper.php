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

namespace block_sharing_cart\app\backup;

use block_sharing_cart\app\item\entity;
use block_sharing_cart\app\factory as basefactory;

/**
 * Backup settings helper for the Sharing Cart block.
 *
 * @package   block_sharing_cart
 * @copyright moxis
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_settings_helper {
    /** @var basefactory $basefactory */
    private basefactory $basefactory;
    /** @var backup_settings_queries $backupsettingsrepository */
    private backup_settings_queries $backupsettingsrepository;

    /**
     * __construct
     *
     * @param basefactory $basefactory
     */
    public function __construct(basefactory $basefactory) {
        $this->basefactory = $basefactory;
        $this->backupsettingsrepository = $basefactory->backup()->settings_repository();
    }

    /**
     * Construct backup plan settings.
     *
     * @param object $customdata
     * @param \core\context $backupcontrollercontext
     * @param false|entity $itementity
     * @return array
     */
    public function construct_backup_plan_settings(
        object $customdata,
        \core\context $backupcontrollercontext,
        false|entity $itementity
    ): array {

        if (!$itementity) {
            throw new \Exception("Item entity not specified. Could not construct backup plan settings.");
        }

        // Base settings.
        $backupplansettings = [
            'role_assignments' => false,
            'activities' => true,
            'blocks' => false,
            'filters' => false,
            'comments' => false,
            'calendarevents' => false,
            'userscompletion' => false,
            'logs' => false,
            'grade_histories' => false,
            'users' => false,
            'anonymize' => false,
            'badges' => false,
            'filename' => 'sharing_cart_backup-' . $itementity->get_id() . '.mbz',
        ];

        $backupsettings = (object)$customdata->backup_settings;

        if (!empty($backupsettings->users)) {
            require_capability('moodle/backup:userinfo', $backupcontrollercontext);
            $backupplansettings['users'] = true;
        }

        if (!empty($backupsettings->anonymize) && !empty($backupplansettings['users'])) {
            require_capability('moodle/backup:anonymise', $backupcontrollercontext);
            $backupplansettings['anonymize'] = true;
        }

        $sectionid = $this->get_section_id($itementity);

        // The copied section plus the descendant sections declared by the course format (nested formats only).
        $sectionids = array_values(array_unique(array_merge(
            [$sectionid],
            self::get_section_tree_ids($backupsettings)
        )));

        // Returns all course modules with the same course number as $itementity.
        $coursemodules = $this->backupsettingsrepository->get_course_modules_by_section_id($sectionid);

        // Returns all sections with the same course number as $itementity.
        $coursesections = $this->backupsettingsrepository->get_course_sections_by_section_id($sectionid);

        // Add module settings.
        $backupplansettings += $this->get_course_module_settings(
            $coursemodules,
            $itementity,
            $sectionids,
            $backupplansettings['users']
        );

        // Add section settings.
        $backupplansettings += $this->get_section_settings($coursesections, $sectionids, $backupplansettings['users']);

        return $backupplansettings;
    }

    /**
     * Section ids of the descendant tree stored in the backup task custom data.
     *
     * @param object $backupsettings
     * @return int[]
     */
    public static function get_section_tree_ids(object $backupsettings): array {
        $ids = [];
        foreach ((array)($backupsettings->section_tree ?? []) as $node) {
            $node = (object)$node;
            if (!empty($node->section_id)) {
                $ids[] = (int)$node->section_id;
            }
        }

        return $ids;
    }

    /**
     * apply_backup_plan_settings
     *
     * @param array $backupplansettings
     * @param \backup_plan $backupplan
     * @return void
     */
    public function apply_backup_plan_settings(array $backupplansettings, \backup_plan $backupplan): void {
        foreach ($backupplansettings as $name => $value) {
            if ($backupplan->setting_exists($name)) {
                $setting = $backupplan->get_setting($name);

                if (\base_setting::NOT_LOCKED !== $setting->get_status()) {
                    continue;
                }

                $setting->set_value($value);
            }
        }
    }

    /**
     * get_section_id
     *
     * @param entity $itementity
     * @return int
     */
    private function get_section_id(entity $itementity): int {
        if ($itementity->get_type() === $itementity::TYPE_SECTION || $itementity->get_type() === $itementity::TYPE_MOD_SUBSECTION) {
            return (int)$itementity->old_instance_id;
        }

        return (int)$this->basefactory->moodle()->db()->get_record(
            'course_modules',
            ['id' => $itementity->old_instance_id],
            'section',
            MUST_EXIST
        )->section;
    }

    /**
     * get_section_settings
     *
     * @param array $sections
     * @param int[] $sectionids
     * @param bool $includeusers
     * @return array
     */
    private function get_section_settings(array $sections, array $sectionids, bool $includeusers): array {
        $settings = [];

        foreach ($sections as $section) {
            $settings["section_" . $section->id . "_userinfo"] = false;
            $settings["section_" . $section->id . "_included"] = false;
        }

        foreach ($sectionids as $sectionid) {
            $settings["section_" . $sectionid . "_userinfo"] = $includeusers;
            $settings["section_" . $sectionid . "_included"] = true;
        }

        return $settings;
    }

    /**
     * get_course_module_settings
     *
     * @param array $coursemodules
     * @param entity $itementity
     * @param int[] $sectionids
     * @param bool $includeusers
     * @return array
     */
    private function get_course_module_settings(
        array $coursemodules,
        entity $itementity,
        array $sectionids,
        bool $includeusers
    ): array {
        $settings = [];

        foreach ($coursemodules as $coursemodule) {
            // Include all immediate child modules of the copied sections in the backup plan settings.
            $isincluded = in_array((int)$coursemodule->section, $sectionids, true);
            $settings = array_merge(
                $settings,
                $this->set_setting(
                    $coursemodule->name,
                    $coursemodule->id,
                    $isincluded,
                    $isincluded ? $includeusers : false
                )
            );
        }

        foreach ($sectionids as $sectionid) {
            $immediatechildmodules = $this->backupsettingsrepository->get_immediate_child_modules_of_section($sectionid);

            if (empty($immediatechildmodules)) {
                continue;
            }

            $childmoduleids = [];
            foreach ($immediatechildmodules as $immediatechildmodule) {
                if (empty($immediatechildmodule->section_id)) {
                    continue;
                }

                // phpcs:disable moodle.Files.LineLength.TooLong
                // Include the section (The corresponding section of the module, must be included.) (Activities don't have corresponding sections).
                // phpcs:enable moodle.Files.LineLength.TooLong
                $settings = array_merge(
                    $settings,
                    $this->set_setting(
                        "section",
                        $immediatechildmodule->section_id,
                        true,
                        $includeusers
                    )
                );

                if (!empty($immediatechildmodule->child_module_ids)) {
                    // Add the module ids of the childrens child modules.
                    $childmoduleids = array_merge(
                        $childmoduleids,
                        explode(',', $immediatechildmodule->child_module_ids)
                    );
                }
            }

            $subsectionchildmodules = array_filter($coursemodules, function ($coursemodule) use ($childmoduleids) {
                return in_array($coursemodule->id, $childmoduleids);
            });

            // Include all subsection's nested child modules.
            foreach ($subsectionchildmodules as $subsectionchildmodule) {
                $settings = array_merge(
                    $settings,
                    $this->set_setting($subsectionchildmodule->name, $subsectionchildmodule->id, true, $includeusers)
                );
            }
        }

        // Subsection's parent section must be included for the backup to work regardless of backup type.
        if ($itementity->get_type() === $itementity::TYPE_MOD_SUBSECTION) {
            $subsectioninfo = $this->backupsettingsrepository->get_mod_subsection_info($sectionids[0]);

            if (empty($subsectioninfo)) {
                throw new \Exception("Could not complete backup plan settings construction. Section was empty.");
            }

            $parentsectionid = $subsectioninfo[array_key_first($subsectioninfo)]->parent_section_id;
            $ownmoduleid = $subsectioninfo[array_key_first($subsectioninfo)]->own_module_id;

            // Include the subsections parent section id (A course section).
            $settings = array_merge($settings, $this->set_setting("section", $parentsectionid, true, $includeusers));

            // The subsection's own module id must also be included.
            $settings = array_merge($settings, $this->set_setting("subsection", $ownmoduleid, true, $includeusers));
        }

        return $settings;
    }

    /**
     * set_setting
     *
     * @param string $settingname
     * @param string $settingid
     * @param bool $settingvalue
     * @param bool $includeusers
     * @return array
     */
    private function set_setting(string $settingname, string $settingid, bool $settingvalue, bool $includeusers): array {
        return [
            $settingname . "_" . $settingid . "_" . "userinfo" => $includeusers,
            $settingname . "_" . $settingid . "_" . "included" => $settingvalue,
        ];
    }
}
