<?php

use block_sharing_cart\ajax_controller;

require_once __DIR__.'/../../../config.php';
header("Content-Type: application/json", true);

try{
	require_sesskey();
	$action = required_param('action', PARAM_ALPHANUMEXT);
	$params = optional_param_array('params', [], PARAM_RAW);
	$controller = new ajax_controller($params);

	switch($action){
		case "ensure_backup_present":
			$json = $controller->ensure_backup_in_module();
		break;
		default:
			$json = $controller->invalid_action($action);
		break;
	}
	die($json);
}
catch(Exception $e){
	die(json_encode(array(
		'http_response' => 500,
		'message' => $e->getMessage(),
		'data' => [],
	)));
}

