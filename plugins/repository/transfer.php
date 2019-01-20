<?php
/**
 * リポジトリファイル転送
 *
 * @author VERSION2 Inc.
 * @version $Id: transfer.php 760 2012-05-30 03:12:19Z malu $
 * @package repository
 */

require_once './SharingCart_Repository.php';
require_once '../../sharing_cart_table.php';
require_once '../../sharing_cart_lib.php';
require_once './lib/FileTransfer.php';

require_login(); // ダウンロードの際は受け先のMoodleにログインしていることを前提とする

try {
	$repository = required_param('repository');
	$usercourse = required_param('usercourse');
	$sessionkey = required_param('sessionkey');
	
	$return_to = $CFG->wwwroot.'/course/view.php?id='.$usercourse;
	
	$materials = required_param('materials');
	$downloads = optional_param('downloads');
	$completes = optional_param('completes');
	
	if (!is_array($materials))
		throw new SharingCart_RepositoryException('Material data were missing');
	
	$config = SharingCart_Repository::getConfig($USER);
	if (empty($config[$repository]->url))
		throw new SharingCart_RepositoryException('Repository URL was missing');
	
	$repository_wwwroot = $config[$repository]->url;
	
	
	// ダウンロード一覧フォーム
	// 
	// リポジトリ→SCへのPOSTは教材IDと各種フィールドのみ渡し、
	// SC側のスクリプトがリポジトリにあるファイルをダウンロードする。
	// 
	// TODO: AJAX化？
	//
	print_header_simple();
	{
		$str_download = get_string('download', 'block_sharing_cart');
		$str_complete = SharingCart_Repository::getString('complete');
		$str_failure  = SharingCart_Repository::getString('failure');
		
		// 教材のリストを表示
		echo '
		<div>
			'.SharingCart_Repository::getString('begin_transfer').'
		</div>
		<form action="transfer.php" method="post">
		<div style="display:none;">
			<input type="hidden" name="repository" value="'.$repository.'" />
			<input type="hidden" name="usercourse" value="'.$usercourse.'" />
			<input type="hidden" name="sessionkey" value="'.$sessionkey.'" />
		</div>
		<table>';
		foreach ($materials as $id => $fields) {
			// フィールドを分解
			// @see /course/format/repository/RepositoryMaterial.php # download()
			list ($sha1, $type, $icon, $text) = explode('|', $fields, 4);
			
			// ダウンロードボタンがクリックされたら、教材ファイルをHTTP通信により取得
			if (!empty($downloads[$id])) {
				$user_dir = make_user_directory($USER->id);
				$temp_dir = make_upload_directory('temp/download', false);
				$zip_name = SharingCart_Repository::getDownloadName($id);
				
				if (!is_dir($user_dir) || !is_dir($temp_dir))
					throw new SharingCart_RepositoryException('Directory creation failure');
				
				if (is_file($user_dir.'/'.$zip_name))
					throw new SharingCart_RepositoryException('File already exists');
				
				$response_header = FileTransfer::downloadFile(
					$temp_dir.'/'.$zip_name,
					$repository_wwwroot.'/course/format/repository/material.php',
					array(
						'mode'       => 'transfer',
						'material'   => $id,
						'sessionkey' => $sessionkey
					)
				);
				
				// ダウンロードしたファイルのハッシュを比較してダウンロード成功かチェック
				if (sha1_file($temp_dir.'/'.$zip_name) == $sha1) {
					
					if (!rename($temp_dir.'/'.$zip_name, $user_dir.'/'.$zip_name))
						throw new SharingCart_RepositoryException('File rename failure');
					
					$record = new stdClass;
					$record->userid   = $USER->id;
					$record->modname  = $type;
					$record->modicon  = $icon;
					$record->modtext  = $text;
					$record->ctime    = time();
					$record->filename = $zip_name;
					
					sharing_cart_table::insert_record($record);
					
					$completes[$id] = TRUE;
				}
			}
			
			if (empty($type) && empty($icon)) {
				$img = '<img src="'.$CFG->pixpath.'/f/unknown.gif" alt="" class="icon" />';
			} else {
				$img = sharing_cart_lib::get_icon($type, $icon);
			}
			echo '
			<tr>
				<td class="icon">'.$img.'</td><td>'.htmlspecialchars($text).'</td>
				<td class="separator"><span class="arrow sep">&#x25BA;</span></td>
				<td class="download">
					<span style="display:none;">
						<input type="hidden" name="materials['.$id.']" value="'.htmlspecialchars($fields).'" />
					</span>';
				if (!empty($completes[$id])) {
					echo '
					<span style="display:none;">
						<input type="hidden" name="completes['.$id.']" value="true" />
					</span>
					<span>'.$str_complete.'</span>';
				} else {
					echo '
					<span>
						<input type="submit" name="downloads['.$id.']" value="'.$str_download.'" />
					</span>';
					if (!empty($downloads[$id])) {
						// ダウンロードが正常に完了しなかった
						echo '
					<span style="error">'.$str_failure.'</span>';
					}
				}
				echo '
				</td>
			</tr>';
		}
		echo '
		</table>
		</form>';
		
		if (count($completes) == count($materials)) {
			// 全て完了したら続行ボタン表示
			print_continue($return_to);
		}
	}
	print_footer();
	
} catch (Exception $e) {
	error((string)$e);
}

?>