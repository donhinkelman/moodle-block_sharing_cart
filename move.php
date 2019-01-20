<?php
/**
 *  Sharing Cart - Move Operation
 *  
 *  @author  VERSION2, Inc.
 *  @version $Id: move.php 785 2012-09-11 09:01:38Z malu $
 */

require_once '../../config.php';

require_once './classes/record.php';

require_login();

$record_id = required_param('id', PARAM_INT);
$return_to = urldecode(required_param('return', PARAM_TEXT));
$insert_to = urldecode(optional_param('to', 0, PARAM_INT));

try {
	$record = sharing_cart\record::from_id($record_id);
	if ($record->userid != $USER->id)
		throw new sharing_cart\exception('capability');
	
	// 挿入先アイテムIDからソート順を取得 (挿入先未指定＝最後尾へ)
	$record->weight = $insert_to
		? sharing_cart\record::from_id($insert_to)->weight
		: $DB->get_field_select(sharing_cart\record::TABLE, 'MAX(weight) + 1',
			'userid = ? AND tree = ?', array($USER->id, $record->tree));
	
	// 挿入先以降のレコードの weight を１つずつずらしてスペースを確保
	$DB->execute('UPDATE {' . sharing_cart\record::TABLE . '}
		SET weight = weight + 1 WHERE userid = ? AND tree = ? AND weight >= ?',
		array($USER->id, $record->tree, $record->weight)
		);
	
	// 移動
	$record->update();
	
	redirect($return_to);
} catch (Exception $ex) {
	if (!empty($CFG->debug) and $CFG->debug >= DEBUG_DEVELOPER) {
		print_error('notlocalisederrormessage', 'error', '', $ex->__toString());
	} else {
		print_error('err:move', 'block_sharing_cart', $return_to);
	}
}
