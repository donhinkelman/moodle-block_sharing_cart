<?php
/**
 * リポジトリ設定
 *
 * @author VERSION2 Inc.
 * @version $Id: settings.php 448 2010-01-05 02:05:42Z malu $
 * @package repository
 */

require_once './Form/Settings.php';

require_login();

$course_id = optional_param('course', SITEID, PARAM_INT);
$return_to = $CFG->wwwroot.'/course/view.php?id='.$course_id;

try {
	// 既存の設定を得る
	$config = SharingCart_Repository::getConfig($USER, FALSE);
	
	if ($update_id = is_array($update = optional_param('update')) ? reset($update) : NULL) {
		// 更新
		
		if ($config[$update_id]->enabled = !optional_param("disabled$update_id")) {
			// Enabled
			
			$url = trim(required_param("url$update_id"));
			if (preg_match('@^(.+?)/course/view\.php\?id=(\d+)@', $url, $m)) {
				// URLを解析してコースIDを分離
				$url      = $m[1];
				$instance = $m[2];
			} else {
				// 末尾のスラッシュを除去
				$url      = rtrim($url, '/');
				$instance = NULL;
			}
			if (empty($instance)) {
				// インスタンスIDが未指定の場合はRepositoryからリストを取得
				// 現在は選択インターフェースが無いので複数返った場合は最初のIDを使用
				require_once './lib/FileTransfer.php';
				$tmp_file = tempnam(make_upload_directory('temp/download', FALSE), 'instance');
				$response = FileTransfer::downloadFile($tmp_file,
					"$url/course/format/repository/instance.php");
				if (preg_match('@^HTTP/1.. (\d+) @', $response, $m)) {
					// HTTPステータスコード
					$status_code = intval($m[1]);
					switch ($status_code) {
					case 200: // OK
						$instance_ids = explode(',', file_get_contents($tmp_file));
						$instance_ids = array_map('intval', $instance_ids);
						// 最初のIDを使用
						$instance = array_shift($instance_ids);
						break;
					case 404: // Not Found
						break;
					}
				}
				@unlink($tmp_file);
			}
			$username = required_param("username$update_id");
			
			$config[$update_id]->enabled  = TRUE;
			$config[$update_id]->url      = $url;
			$config[$update_id]->instance = $instance;
			$config[$update_id]->username = $username;
			
			if (empty($config[$update_id])) {
				$config[$update_id]           = new stdClass;
				$config[$update_id]->sitename = '';
				
				// 新規の場合はパスワード必須
				$config[$update_id]->password = required_param("password$update_id");
			} else {
				// 更新の場合はパスワードがフォームに自動入力されないので
				// 入力値が空でなければ更新
				if ($new_password = optional_param("password$update_id")) {
					$config[$update_id]->password = $new_password;
				}
			}
			
			if (!empty($url)) {
				// リポジトリサイト名をHTTP通信で取得
				require_once './lib/FileTransfer.php';
				$tmp_file = tempnam(make_upload_directory('temp/download', FALSE), 'title');
				$response = FileTransfer::downloadFile($tmp_file,
					"$url/course/format/repository/title.php",
					array(
						'instance' => $instance,
						'username' => $config[$update_id]->username,
						'password' => $config[$update_id]->password,
						'usersite' => $CFG->wwwroot,
						'sitename' => $SITE->fullname,
					)
				);
				if (preg_match('@^HTTP/1.. (\d+) @', $response, $m)) {
					// HTTPステータスコード
					$status_code = intval($m[1]);
					switch ($status_code) {
					case 200: // OK
						$sitename = file_get_contents($tmp_file);
						if (strspn($sitename, "\r\n")) {
							// 改行文字が含まれていたらRepository側で
							// PHPエラーが発生したと判断
						} else {
							$config[$update_id]->sitename = $sitename;
							SharingCart_Repository::setConfig($USER, $config);
							$succeeded = TRUE;
						}
						break;
					case 403: // Forbidden
						$forbidden = TRUE;
						break;
					case 404: // Not Found
						break;
					}
				}
				@unlink($tmp_file);
			}
		} else {
			// Disabled
			SharingCart_Repository::setConfig($USER, $config);
			$succeeded = TRUE;
		}
	}
	
	if (empty($config) || optional_param('add')) {
		$info = new stdClass;
		{
			$info->enabled  = FALSE;
			$info->url      = '';
			$info->instance = '';
			$info->username = '';
			$info->password = '';
			$info->sitename = '';
		}
		if (empty($config)) {
			$config = array();
			$config[1] = $info;
		} else {
			$next = max(array_keys($config)) + 1;
			$config[$next] = $info;
		}
	}
	
	$forms = array();
	foreach ($config as $id => $info) {
		if ($id == $update_id) {
			if (!empty($succeeded)) {
				$message = get_string('updated', NULL, SharingCart_Repository::getString('settings'));
			} elseif (!empty($forbidden)) {
				$message = SharingCart_Repository::getString('auth_fail');
			} else {
				$message = SharingCart_Repository::getString('not_found');
			}
			$forms[] = new SharingCart_Repository_Form_Settings($course_id, $id, $info, $message);
		} else {
			$forms[] = new SharingCart_Repository_Form_Settings($course_id, $id, $info);
		}
	}
	
//* (暫定) UL/DL機能が複数設定に対応していないので機能隠蔽
	if (!optional_param('add')) {
		class add_repository_form extends moodleform
		{
			public function definition()
			{
				$this->_form->addElement('submit', 'add', get_string('add'));
			}
		}
		$forms[] = new add_repository_form();
	}
//*/
	
	SharingCart_Repository::printForm(
		$forms,
		SharingCart_Repository::getString('settings'),
		$course_id,
		$return_to
	);
	
} catch (Exception $e) {
	error((string)$e);
}

?>