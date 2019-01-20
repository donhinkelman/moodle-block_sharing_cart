<?php
/**
 *  Sharing Cart - Delete Operation
 *  
 *  @author  VERSION2, Inc.
 *  @version $Id: delete.php 785 2012-09-11 09:01:38Z malu $
 */

require_once '../../config.php';

require_once './classes/storage.php';
require_once './classes/record.php';

require_login();

$record_id = required_param('id', PARAM_INT);
$return_to = urldecode(required_param('return', PARAM_TEXT));

try {
	$record = sharing_cart\record::from_id($record_id);
	if ($record->userid != $USER->id)
		throw new sharing_cart\exception('capability');
	
	$storage = new sharing_cart\storage();
	$storage->delete($record->filename);
	
	$record->delete();
	
	redirect($return_to);
} catch (Exception $ex) {
	if (!empty($CFG->debug) and $CFG->debug >= DEBUG_DEVELOPER) {
		print_error('notlocalisederrormessage', 'error', '', $ex->__toString());
	} else {
		print_error('err:delete', 'block_sharing_cart', $return_to);
	}
}
