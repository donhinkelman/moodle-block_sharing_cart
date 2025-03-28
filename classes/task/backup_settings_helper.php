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

namespace block_sharing_cart\task;

class backup_settings_helper
{
    public function get_course_settings_by_item(object $item, bool $users): array
    {
        $settings = [];

        [$section_id, $module_id] = $this->get_ids_by_item($item);

        $sections = $this->get_course_sections_by_section_id($section_id);

        $modules = $this->get_course_modules_by_section_id($section_id);

        $settings += $this->get_module_settings($modules, $section_id, $module_id, $users);

        $settings += $this->get_section_settings($sections, $section_id, $users);

        return $settings;
    }

    private function get_ids_by_item(object $item): array
    {
        $module_id = null;

        if($item->type === 'section') {
            return [$item->old_instance_id, $module_id];
        }
        global $DB;
        $module_id = $item->old_instance_id;
        $course = $DB->get_record('course_modules',['id' => $module_id],'section');
        if ($course === false){
            throw new \Exception('Course module Not found.');
        }
        $section_id = $course->section;

        return [$section_id, $module_id];
    }

    private function get_course_sections_by_section_id(mixed $section_id): array
    {
        global $DB;
        // Get all sections in the course by section_id
        $sql = "SELECT cs.id, cs.sequence
                   FROM {course_sections} cs
                  WHERE cs.course = (SELECT cs.course
                                       FROM {course_sections} cs
                                      WHERE cs.id = :section_id)";
        $params =  [
            'section_id' => $section_id
        ];
        $output = $DB->get_records_sql($sql, $params);
        if ($output === []) {
            throw new \Exception('No section found with that id.');
        }
        return $output;
    }

    private function get_course_modules_by_section_id(int $section_id): array
    {
        global $DB;
        // Get all course_modules in within the course by section_id
        $sql = "SELECT cm.id, cm.section, mo.name
                  FROM {course_modules} cm
            INNER JOIN {modules} as mo on cm.module = mo.id
                 WHERE cm.course = (SELECT cs.course
                                      FROM {course_sections} cs
                                     WHERE cs.id = :section_id)";
        $params = [
            'section_id' => $section_id
        ];
        $output = $DB->get_records_sql($sql, $params);
        if ($output === []){
            throw new \Exception('Course have no modules.');
        }
        return $output;
    }

    private function get_section_settings(array $sections, int $section_id, bool $users): array
    {
        $settings = [];
        foreach ($sections as $section){
            $settings += [
                "section_".$section->id."_userinfo" => false,
                "section_".$section->id."_included" => false
            ];
        }

        $settings["section_".$section_id."_userinfo"] = $users;
        $settings["section_".$section_id."_included"] = true;

        return $settings;
    }

    private function get_module_settings(array $modules, int $section_id, int|null $module_id, bool $users): array
    {
        $settings = [];
        foreach ($modules as $module) {
            $settings += [
                $module->name . "_" . $module->id . "_userinfo" => false,
                $module->name . "_" . $module->id . "_included" => false
            ];
        }

        if ($module_id !== null) {
            // get the one module
            $keep_modules = array_filter($modules, function ($v) use ($module_id) {
                return (int) $v->id === $module_id;
            });
        } else {
            // get all section modules
            $keep_modules = array_filter($modules, function ($v) use ($section_id) {
                return (int) $v->section === $section_id;
            });
        }

        if ($keep_modules === []){
            throw new \Exception('No modules to include in section.');
        }

        foreach ($keep_modules as $module) {
            $settings[$module->name . "_" . $module->id . "_userinfo"] = $users;
            $settings[$module->name . "_" . $module->id . "_included"] = true;
        }
        return $settings;
    }
}