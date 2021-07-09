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
}
