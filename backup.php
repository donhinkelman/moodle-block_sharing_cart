<?php

require_once '../../config.php';

//error_reporting(E_ALL);

require_once './shared/SharingCart_Backup.php';
require_once './sharing_cart_table.php';

$course_id = required_param('course', PARAM_INT);
$section_i = required_param('section', PARAM_INT);
$cm_id     = required_param('module', PARAM_INT);
$return_to = urldecode(required_param('return'));

try {
    // バックアップオブジェクト (※ $preferences は Moodle グローバル変数として予約されているので使用不可)
    $worker = new SharingCart_Backup($course_id, $section_i);

    // サイレントモード
    $worker->setSilent();

    // コースオブジェクト取得
    $course = $worker->getCourse();

    // コースモジュールが存在するかチェック
    $modinfo = get_fast_modinfo($course) and isset($modinfo->cms[$cm_id])
        or print_error('err_module_id', 'block_sharing_cart', $return_to);

    // コースモジュール取得
    $cm = $modinfo->cms[$cm_id];

    // モジュールが存在するかチェック
    $module = get_record('modules', 'name', $cm->modname)
        or print_error('err_module_id', 'block_sharing_cart', $return_to);

    // 設定開始
    $worker->beginPreferences();

    // ZIPファイル名設定
    $zipname = sharing_cart_table::gen_zipname($worker->getUnique());
    $worker->setZipName($zipname);

    // モジュールをバックアップリストに追加
    $worker->addModule($module, $cm->id);

    // 設定完了
    $worker->endPreferences();

    // バックアップ実行
    $worker->execute();


    // モジュールの名前とZIPとの対応などをDBに登録 (ブロック表示で使用)
    $record = new stdClass;
    $record->userid   = $USER->id;
    $record->modname  = addslashes($module->name);
    $record->modicon  = addslashes($cm->icon);
    $record->modtext  = addslashes($module->name == 'label' ? $cm->extra : $cm->name);
    $record->ctime    = $worker->getUnique(); // ZIP名生成に使用したユニーク値 (=タイムスタンプ)
    $record->filename = $zipname;
    $record->weight   = 0;
    sharing_cart_table::insert_record($record);


    if ($worker->succeeded()) {
        // 成功：リダイレクト
        redirect($return_to);
    } else {
        // 失敗：「続行」画面
        print_continue($return_to);
    }

} catch (SharingCart_CourseException $e) {
    //print_error('err_course_id', 'block_sharing_cart', $return_to);
    error((string)$e); // デバッグ用に詳細メッセージを表示

} catch (SharingCart_SectionException $e) {
    //print_error('err_section_id', 'block_sharing_cart', $return_to);
    error((string)$e); // デバッグ用に詳細メッセージを表示

} catch (SharingCart_ModuleException $e) {
    //print_error('err_module_id', 'block_sharing_cart', $return_to);
    error((string)$e); // デバッグ用に詳細メッセージを表示

} catch (SharingCart_XmlException $e) {
    //print_error('err_invalid_xml', 'block_sharing_cart', $return_to);
    error((string)$e); // デバッグ用に詳細メッセージを表示

} catch (SharingCart_Exception $e) {
    //print_error('err_backup', 'block_sharing_cart', $return_to);
    error((string)$e); // デバッグ用に詳細メッセージを表示

}
