<?php
/**
 *  Sharing Cart - Move Directory Operation
 *  
 *  @author  VERSION2, Inc.
 *  @version $Id: movedir.php 785 2012-09-11 09:01:38Z malu $
 */

require_once '../../config.php';

require_once './classes/record.php';
require_once './classes/utils.php';

require_login();

$record_id = optional_param('id', 0, PARAM_INT);
$return_to = urldecode(required_param('return', PARAM_TEXT));
$move_to   = urldecode(required_param('to', PARAM_TEXT));

// Unicodeエスケープ文字をUTF-8にデコード
$move_to = sharing_cart\utils\js_urldecode(trim($move_to));

// パスの階層構造から空の要素を除去 ("/foo//bar/" → "foo/bar")
$move_to = implode('/', array_filter(explode('/', $move_to), 'strlen'));

try {
	if (empty($record_id)) {
		// id パラメータが空の場合はフォルダの移動 (ブロック側UIは未実装)
		$move_from = urldecode(optional_param('from', '', PARAM_TEXT));
		$move_from = sharing_cart\utils\js_urldecode(trim($move_from));
		
		$DB->set_field(sharing_cart\record::TABLE, 'tree', $move_to,
			array('userid' => $USER->id, 'tree' => $move_from));
	} else {
		$record = sharing_cart\record::from_id($record_id);
		if ($record->userid != $USER->id)
			throw new sharing_cart\exception('capability');
		
		$record->tree = $move_to;
		$record->update();
	}
	
	// フォルダ表示状態のクッキーをリセット
	if (!headers_sent()) {
		@setcookie('sharing_cart-dirs', '');
	}
	
	redirect($return_to);
} catch (Exception $ex) {
	if (!empty($CFG->debug) and $CFG->debug >= DEBUG_DEVELOPER) {
		print_error('notlocalisederrormessage', 'error', '', $ex->__toString());
	} else {
		print_error('err:move', 'block_sharing_cart', $return_to);
	}
}
