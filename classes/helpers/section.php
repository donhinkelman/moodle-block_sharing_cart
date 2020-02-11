<?php
/**
 * Created by PhpStorm.
 * User: Johnny Drud
 * Date: 30-08-2019
 * Time: 23:27
 */

namespace sharing_cart\helpers;

defined('MOODLE_INTERNAL') || die();

/**
 * Class section
 *
 * @package sharing_cart
 */
class section
{
	/**
	 * Get the section ID
	 *
	 * @param $course_id
	 * @param $section_number
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public static function get($course_id, $section_number) { global $DB;

		if (empty($course_id)) {
			throw new \Exception('Course ID missing. Can\'t fetch the section');
		}

		if (!isset($section_number)) {
			throw new \Exception('Section number missing. Can\'t fetch the section');
		}

		try
		{
			$section = $DB->get_record('course_sections', [
				'course' => $course_id,
				'section' => $section_number
			], 'id, name');
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}

		return $section;
	}
}
