<?php

namespace block_sharing_cart;

use core_plugin_manager;

/**
 * Class ajax_controller
 * @package block_sharing_cart
 */
class ajax_controller{

	protected $params = [];

	/**
	 * ajax_controller constructor.
	 * @param $params
	 */
	public function __construct(array $params = array()){
		$this->params = $params;
	}

	/**
	 * @return array
	 * @throws \moodle_exception
	 */
	public function ensure_backup_in_module(){
		$cmid = $this->params['cmid'];
		$courseid = !empty($this->params['courseid']) ? $this->params['courseid'] : null;

		return $this->output('', array(
			'has_backup_routine' => module::has_backup($cmid, $courseid)
		));
	}

	/**
	 * @param $action
	 * @return false|string
	 */
	public function invalid_action($action){
		return $this->output('Action "'.$action.'" not found!', array(), 404);
	}

	/**
	 * @param string $message
	 * @param array $data
	 * @param int $http_response
	 * @return false|string
	 */
	public function output($message = '', $data = [], $http_response = 200){
		http_response_code($http_response);

		return json_encode(array(
			'message' => $message,
			'data' => $data,
			'http_response' => $http_response,
		));
	}
}