<?php

namespace block_sharing_cart;

class module {

    /**
     * @param $cmid
     * @param int $course
     * @return bool
     * @throws \moodle_exception
     */
    public static function has_backup($cmid, $course = 0) {
        global $CFG;

        list($course, $cm) = get_course_and_cm_from_cmid($cmid, '', $course);
        return is_dir($CFG->dirroot . '/mod/' . $cm->modname . '/backup/moodle2');
    }
}
