<?php

require_once '../../config.php';
require_once './sharing_cart_table.php';

//error_reporting(E_ALL);

require_login();

$record_id = required_param('id', PARAM_INT);
$return_to = urldecode(required_param('return'));

// 続行可能な通知メッセージがあれば直接リダイレクトせずにそれを表示
$notifications = array();

// 共有アイテムが存在するかチェック
$record = sharing_cart_table::get_record_by_id($record_id)
    or print_error('err_shared_id', 'block_sharing_cart', $return_to);

// 自分が所有する共有アイテムかチェック
$record->userid == $USER->id
    or print_error('err_capability', 'block_sharing_cart', $return_to);

$zip_path = make_user_directory($USER->id, true).'/'.$record->filename;

// ZIP削除
//$oldmask = umask(0);
//chmod($zip_path, 0666);
unlink($zip_path)
    or $notifications[] = get_string('err_delete', 'block_sharing_cart');
//umask($oldmask);

// DB削除
sharing_cart_table::delete_record($record)
    or print_error('err_delete', 'block_sharing_cart', $return_to);

//if (headers_sent()) die;

if (count($notifications)) {
    notice(implode('<br />', $notifications), $return_to);
} else {
    redirect($return_to);
}
