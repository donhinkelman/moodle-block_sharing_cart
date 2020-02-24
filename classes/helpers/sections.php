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
 * Class sections
 *
 * @package sharing_cart
 */
class sections
{
	/**
	 * Get the sections ID
	 *
	 * @param $course_id
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public static function get($course_id) { global $DB;

		if (empty($course_id)) {
			throw new \Exception('Course ID missing. Can\'t fetch the section');
		}

		try
		{
            $sections = $DB->get_records('course_sections', [
				'course' => $course_id
			], 'id, name');
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}

		return $sections;
	}
}
