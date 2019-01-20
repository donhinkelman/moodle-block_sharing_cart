<?php

require_once '../../config.php';
require_once './sharing_cart_table.php';

//error_reporting(E_ALL);

require_login();

$record_id = required_param('id', PARAM_INT);
$return_to = urldecode(required_param('return'));
$insert_to = urldecode(optional_param('to'));

// 共有アイテムが存在するかチェック
$record = sharing_cart_table::get_record_by_id($record_id)
    or print_error('err_shared_id', 'block_sharing_cart', $return_to);

// 自分が所有する共有アイテムかチェック
$record->userid == $USER->id
    or print_error('err_capability', 'block_sharing_cart', $return_to);

// 挿入先アイテムIDからソート順を取得 (挿入先未指定＝最後尾へ)
$dest_weight = 0;
if (!empty($insert_to) and $target = get_record('sharing_cart',
        'id', $insert_to, 'tree', $record->tree, 'userid', $USER->id)) {
    $dest_weight = $target->weight;
} else {
    $max_weight = get_field_sql(
        "SELECT MAX(weight) FROM {$CFG->prefix}sharing_cart
        WHERE userid = '$USER->id' AND tree = '$record->tree'");
    $dest_weight = $max_weight + 1;
}

// 挿入先以降のレコードの`weight`をインクリメントしてスペースを確保
$sql = "UPDATE {$CFG->prefix}sharing_cart SET weight = weight + 1
        WHERE userid = '$USER->id' AND tree = '$record->tree'
        AND weight >= $dest_weight";
execute_sql($sql, false);

// 目的のアイテムを移動
$record->weight = $dest_weight;
sharing_cart_table::update_record($record)
    or print_error('err_move', 'block_sharing_cart', $return_to);

//if (headers_sent()) die;

redirect($return_to);
