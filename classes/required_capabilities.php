<?php
/**
 * @developer   Johnny Drud
 * @date        30-07-2020
 * @company     https://praxis.dk
 * @copyright   2020 Praxis
 */

namespace block_sharing_cart;

defined('MOODLE_INTERNAL') || die();

/**
 * Class
 *
 * @package block_sharing_cart
 */
class required_capabilities
{
	/**
	 * @var array
	 */
	protected $required_capabilities = [];

	/**
	 * @var array
	 */
	protected $missing_capabilities = [];

	/**
	 * @var int
	 */
	protected $total_capabilities_missing = 0;

	/**
	 * @var array
	 */
	protected $disallowed_actions = [];

	/**
	 * Constructor
	 *
	 * @param array $required_capabilities
	 *
	 * @throws \coding_exception
	 */
	private function __construct(array $required_capabilities) {

		if (empty($required_capabilities)) {
			throw new \RuntimeException(get_string('define_required_capabilities', 'block_sharing_cart'));
		}

		global $COURSE;

		$course_context = \context_course::instance($COURSE->id);
		foreach ($required_capabilities as $required_capability) {

			if (has_capability($required_capability, $course_context)) {
				continue;
			}

			$this->missing_capabilities[] = $required_capability;
			$this->total_capabilities_missing++;

			if (in_array('restore', $this->disallowed_actions, true)) {
				continue;
			}

			$this->disallowed_actions[] = 'restore';
		}
	}

	/**
	 * @param array $args
	 *
	 * @return static
	 * @throws \coding_exception
	 */
	public static function init(... $args) : self {
		return new static(... $args);
	}

	/**
	 * @return array
	 */
	public function get_disallowed_actions() : array {
		return $this->disallowed_actions;
	}

	/**
	 * @return array
	 */
	public function get_missing_capabilities() : array {
		return $this->missing_capabilities;
	}

	/**
	 * @return int
	 */
	public function total_capabilities_missing() : int {
		return $this->total_capabilities_missing;
	}
}
