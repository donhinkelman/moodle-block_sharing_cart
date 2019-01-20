<?php
/**
 *  Sharing Cart - REST API
 *  
 *  @author  VERSION2, Inc.
 *  @version $Id: rest.php 859 2012-10-12 06:31:50Z malu $
 */

require_once '../../config.php';

require_once __DIR__.'/classes/controller.php';

try {
	$controller = new sharing_cart\controller();
	
	switch (required_param('action', PARAM_TEXT)) {
	case 'render_tree':
		$PAGE->set_context(\context_user::instance($USER->id)); // pix_url() needs this
		echo $controller->render_tree($USER->id);
		exit;
	case 'is_userdata_copyable':
		$cmid = required_param('cmid', PARAM_INT);
		echo $controller->is_userdata_copyable($cmid);
		exit;
	case 'backup':
		$cmid     = required_param('cmid', PARAM_INT);
		$userdata = required_param('userdata', PARAM_BOOL);
		$controller->backup($cmid, $userdata);
		exit;
	case 'movedir':
		$id = required_param('id', PARAM_INT);
		$to = required_param('to', PARAM_TEXT);
		$controller->movedir($id, $to);
		exit;
	case 'move':
		$id = required_param('id', PARAM_INT);
		$to = required_param('to', PARAM_INT);
		$controller->move($id, $to);
		exit;
	case 'delete':
		$id = required_param('id', PARAM_INT);
		$controller->delete($id);
		exit;
	}
	throw new sharing_cart\exception('invalidoperation');
	
} catch (Exception $ex) {
	header('HTTP/1.1 400 Bad Request');
	$json = array(
		'message' => $ex->getMessage(),
		);
	if (!empty($CFG->debug) and $CFG->debug >= DEBUG_DEVELOPER) {
		$json += array(
			'file'  => substr($ex->getFile(), strlen($CFG->dirroot)),
			'line'  => $ex->getLine(),
			'trace' => format_backtrace($ex->getTrace(), true),
			);
	}
	echo json_encode($json);
}
