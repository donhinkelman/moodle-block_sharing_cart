<?php

namespace block_sharing_cart\app\backup;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

use block_sharing_cart\app\item\entity;
use block_sharing_cart\app\factory as base_factory;

class backup_settings_helper
{
    private base_factory $base_factory;

    public function __construct(base_factory $base_factory)
    {
        $this->base_factory = $base_factory;
    }

    public function get_course_settings_by_item(entity $item, bool $include_users): array
    {
        $settings = [];

        [$section_id, $course_module_id] = $this->get_ids_by_item($item);

        $sections = $this->get_course_sections_by_section_id($section_id);

        $course_modules = $this->get_course_modules_by_section_id($section_id);

        $settings += $this->get_course_module_settings($course_modules, $section_id, $course_module_id, $include_users);

        $settings += $this->get_section_settings($sections, $section_id, $include_users);

        return $settings;
    }

    private function get_ids_by_item(entity $item): array
    {
        $module_id = null;

        if($item->type === 'section') {
            return [$item->old_instance_id, $module_id];
        }
        $module_id = $item->old_instance_id;
        $section_id = $this->base_factory->moodle()->db()->get_record(
            'course_modules',
            ['id' => $module_id],
            'section',
            MUST_EXIST
        )->section;

        return [$section_id, $module_id];
    }

    private function get_course_sections_by_section_id(int $section_id): array
    {
        $db = $this->base_factory->moodle()->db();
        // Get all sections in the course by section_id
        $sql = "SELECT cs.id, cs.sequence
                   FROM {course_sections} cs
                  WHERE cs.course = (SELECT cs.course
                                       FROM {course_sections} cs
                                      WHERE cs.id = :section_id)";
        $params =  [
            'section_id' => $section_id
        ];

        return $db->get_records_sql($sql, $params);
    }

    private function get_course_modules_by_section_id(int $section_id): array
    {
        $db = $this->base_factory->moodle()->db();
        // Get all course_modules within course by section_id
        $sql = "SELECT cm.id, cm.section, m.name
                FROM {course_modules} cm
                JOIN {modules} as m on cm.module = m.id
                WHERE cm.course = (SELECT cs.course
                                   FROM {course_sections} cs
                                   WHERE cs.id = :section_id)";
        $params = [
            'section_id' => $section_id
        ];

        return $db->get_records_sql($sql, $params);
    }

    private function get_section_settings(array $sections, int $section_id, bool $include_users): array
    {
        $settings = [];
        foreach ($sections as $section){
            $settings["section_".$section->id."_userinfo"] = false;
            $settings["section_".$section->id."_included"] = false;
        }

        $settings["section_".$section_id."_userinfo"] = $include_users;
        $settings["section_".$section_id."_included"] = true;

        return $settings;
    }

    private function get_course_module_settings(
        array $course_modules,
        int $section_id,
        ?int $course_module_id,
        bool $include_users
    ): array
    {
        $settings = [];
        foreach ($course_modules as $module) {
            $settings[$module->name . "_" . $module->id . "_userinfo"] = false;
            $settings[$module->name . "_" . $module->id . "_included"] = false;

        }

        if ($course_module_id !== null) {
            // get the one module
            $keep_modules = array_filter($course_modules, static function ($course_module) use ($course_module_id) {
                return (int) $course_module->id === $course_module_id;
            });
        } else {
            // get all section modules
            $keep_modules = array_filter($course_modules, static function ($course_module) use ($section_id) {
                return (int) $course_module->section === $section_id;
            });
        }

        foreach ($keep_modules as $module) {
            $settings[$module->name . "_" . $module->id . "_userinfo"] = $include_users;
            $settings[$module->name . "_" . $module->id . "_included"] = true;
        }
        return $settings;
    }
}