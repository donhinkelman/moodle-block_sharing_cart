<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *  Sharing Cart
 *
 * @package    block_sharing_cart
 * @copyright  2017 (C) VERSION2, INC.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = '共有カート';
$string['sharing_cart'] = '共有カート';
$string['sharing_cart_help'] = '<h2 class="helpheading">操作方法</h2>
<dl style="margin-left:0.5em;">
<dt>共有カートへコピー</dt>
    <dd>コースを編集モードに切り替えると、コース上の各コンテンツの操作アイコンの右に
        「共有カートへコピー」アイコンが追加されます。</dd>
<dt>コースへコピー</dt>
    <dd>共有カート内のアイテム操作アイコンの「コースへコピー」をクリックすると、
        コースの各セクションに「ここへコピー」マーカーが現れるので、
        いずれかを選択してコピーを完了するか、上部の「キャンセル」をクリックします。</dd>
<dt>共有カート内にフォルダを作成</dt>
    <dd>共有カート内で「フォルダ移動」アイコンをクリックすると、
        既存のフォルダのリストが表示されるので、その中から移動先を選択するか、
        リスト右の「編集」アイコンをクリックして入力ボックスを表示させ、
        そこに移動先フォルダ名を入力します。</dd>
</dl>';
$string['sharing_cart:addinstance'] = '新しい共有カートブロックを追加する';

$string['backup'] = '共有カートへコピー';
$string['restore'] = 'コースへコピー';
$string['movedir'] = 'フォルダ移動';
$string['copyhere'] = 'ここにコピー';
$string['notarget'] = 'ターゲットが見つかりません';
$string['clipboard'] = 'この共有アイテムをコピーする';
$string['bulkdelete'] = '一括削除';
$string['confirm_backup'] = '共有カートにコピーしますか？';
$string['confirm_backup_section'] = '共有カートにコピーしますか？';
$string['confirm_userdata'] = '共有カートへのコピーにユーザーデータを含めますか？
OK：ユーザーデーターを含めて、コピーする
キャンセル：ユーザーデーターを含めずに、コピーする';
$string['confirm_restore'] = 'コースにコピーしますか？';
$string['confirm_delete'] = '削除してよろしいですか？';
$string['confirm_delete_selected'] = '選択したアイテムを全て削除してもよろしいですか？';
$string['inprogess_pleasewait'] = 'しばらくお待ち下さい…';

$string['settings:userdata_copyable_modtypes'] = 'ユーザーデータをコピー可能なモジュールタイプ';
$string['settings:userdata_copyable_modtypes_desc'] = '共有カートへコピーする際、コピーしようとしているモジュールがここでチェックを付けたモジュールタイプで、
かつ、操作しているユーザーが <strong>moodle/backup:userinfo</strong>,
<strong>moodle/backup:anonymise</strong>, <strong>moodle/restore:userinfo</strong> ケイパビリティを持っていれば、
そのモジュールに付随するユーザーデータをコピーに含めるかどうかを選択するダイアログを表示します。<br />
(既定では「マネージャ」ロールのみがこれらのケイパビリティを持ちます。)';
$string['settings:workaround_qtypes'] = 'リストア不具合対策を行う問題タイプ';
$string['settings:workaround_qtypes_desc'] = 'チェックを付けた問題タイプに対して、リストア不具合対策を行います。
これを有効にすると、リストアしようとしている問題と全く同じ問題が既に存在していて、
しかしながらそのデータに破損が見つかった場合、既存データの再利用を避け、
その問題を再度リストアするように試みます。この対策は、<i>error_question_match_sub_missing_in_db</i> などのエラー回避に有用です。';

$string['invalidoperation'] = '無効な操作です';
$string['unexpectederror'] = '予期しないエラーが発生しました';
$string['recordnotfound'] = '共有アイテムが見つかりません';
$string['forbidden'] = 'この共有アイテムにアクセスする権限がありません';
$string['requirejs'] = 'ブラウザの JavaScript を有効にしてください';
$string['requireajax'] = 'AJAX が有効になっていません';

$string['variouscourse'] = '複数のコースから';

$string['section_name_conflict'] = 'トピック名の選択';
$string['conflict_description'] = 'コースのトピック名を上書きしますか？';
$string['conflict_description_note'] = '※トピックの説明のフォーマット（テキストの色、画像など）がコースコピー後に表現する。';
$string['restore_heavy_load_warning_message'] = 'Load time are longer, because more than 10 activities/resources are being processed. (Translation missing)';
$string['backup_heavy_load_warning_message'] = 'If section contains several activites, processing time will be longer. (Translation missing)';
$string['conflict_no_overwrite'] = 'トピック名を変更しない。<strong>「{$a}」</strong>のままにする。';
$string['conflict_overwrite_title'] = 'タイトルを<strong>「{$a}」</strong>に変更する。';
$string['conflict_submit'] = '続く';
