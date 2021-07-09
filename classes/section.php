<?php
/**
 * Created by PhpStorm.
 * User: Johnny Drud
 * Date: 30-08-2019
 * Time: 23:27
 */

namespace block_sharing_cart;

defined('MOODLE_INTERNAL') || die();

/**
 * Class section
 *
 * @package sharing_cart
 */
class section {

    /**
     * Get the section ID
     *
     * @param int $course_id
     * @param int $section_number
     *
     * @return mixed
     * @throws \Exception
     */
    public static function get(int $course_id, int $section_number) {
        global $DB;

        if (empty($course_id)) {
            throw new \Exception('Course ID missing. Can\'t fetch the section');
        }

        if (!isset($section_number)) {
            throw new \Exception('Section number missing. Can\'t fetch the section');
        }

        try {
            $section = $DB->get_record('course_sections', [
                    'course' => $course_id,
                    'section' => $section_number
            ], 'id, name');
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $section;
    }

    /**
     * @param int $course_id
     * @return \section_info[]
     * @throws \moodle_exception
     */
    public function all(int $course_id): array {
        get_fast_modinfo($course_id, 0, true);
        /** @var \course_modinfo $sections */
        $sections = get_fast_modinfo($course_id);
        return $sections->get_section_info_all();
    }
}
