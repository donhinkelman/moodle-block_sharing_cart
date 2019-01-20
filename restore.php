<?php
/**
 *  Sharing Cart - Restore Operation
 *  
 *  @author  VERSION2, Inc.
 *  @version $Id: restore.php 783 2012-09-11 06:48:57Z malu $
 */

require_once '../../config.php';

require_once __DIR__.'/classes/controller.php';

$id            = required_param('id', PARAM_INT);
$courseid      = required_param('course', PARAM_INT);
$sectionnumber = required_param('section', PARAM_INT);

if ($courseid == SITEID) {
    $returnurl = new moodle_url('/');
} else {
    $returnurl = new moodle_url('/course/view.php', array('id' => $courseid));
}

try {
	$controller = new sharing_cart\controller();
	$controller->restore($id, $courseid, $sectionnumber);
	
	redirect($returnurl);
	
} catch (sharing_cart\exception $ex) {
	print_error($ex->errorcode, $ex->module, $returnurl, $ex->a);
} catch (Exception $ex) {
	if (!empty($CFG->debug) and $CFG->debug >= DEBUG_DEVELOPER) {
		print_error('notlocalisederrormessage', 'error', '', $ex->__toString());
	} else {
		print_error('unexpectederror', 'block_sharing_cart', $returnurl);
	}
}
