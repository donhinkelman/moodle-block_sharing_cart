<?php

namespace block_sharing_cart;

defined('MOODLE_INTERNAL') || die();

/**
 * Class
 *
 * @package block_sharing_cart
 */
class module {
    /**
     * @param int $cmid
     * @param int $course
     * @return bool
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public static function has_backup(int $cmid, int $course = 0): bool {
        [$course, $cm] = get_course_and_cm_from_cmid($cmid, '', $course);
        return (bool)plugin_supports('mod', $cm->modname, FEATURE_BACKUP_MOODLE2);
    }

    /**
     * @param int $course_id
     * @return \section_info[]
     * @throws \moodle_exception
     */
    public function get_all_from_course(int $course_id): array {
        if (empty($course_id)) {
            return [];
        }

        /** @var \course_modinfo $modules */
        $modules = get_fast_modinfo($course_id);
        return $modules->get_cms();
    }
}
