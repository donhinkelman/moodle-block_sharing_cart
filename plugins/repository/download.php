<?php
/**
 * リポジトリダウンロード
 *
 * @author VERSION2 Inc.
 * @version $Id: download.php 373 2009-10-26 07:26:16Z malu $
 * @package repository
 */

require_once './SharingCart_Repository.php';

require_once $CFG->libdir.'/formslib.php';

require_login();

$course_id = optional_param('course', SITEID, PARAM_INT);
$return_to = $CFG->wwwroot.'/course/view.php?id='.$course_id;

try {
	if ($repo_url = optional_param('repourl')) {
		// リポジトリからの遷移
		$config = SharingCart_Repository::getConfig($USER);
		foreach ($config as $id => $element) {
			if ($element->url == $repo_url) {
				$repo_id = $id;
				break;
			}
		}
		if (empty($repo_id))
			throw new SharingCart_RepositoryException('Repository settings was missing');
	} else {
		// 自サイト内からの遷移
		$repo_id = required_param('repoid', PARAM_INT);
		
		$config = SharingCart_Repository::getConfig($USER);
		
		if (empty($config[$repo_id]))
			throw new SharingCart_RepositoryException('Repository settings was missing');
		if (empty($config[$repo_id]->url))
			throw new SharingCart_RepositoryException('Repository URL was missing');
		if (empty($config[$repo_id]->instance))
			throw new SharingCart_RepositoryException('Repository course ID was missing');
		if (empty($config[$repo_id]->username))
			throw new SharingCart_RepositoryException('Repository username was missing');
	}
	
	/*
	$form = new MoodleQuickForm('download', 'post',
		$config[$repo_id]->url.'/course/format/repository/material.php');
	$form->addElement('hidden', 'mode', 'download');
	$form->addElement('hidden', 'id', $config[$repo_id]->instance);
	
	$form->addElement('hidden', 'username', $config[$repo_id]->username);
	$form->addElement('hidden', 'password', $config[$repo_id]->password);
	
	$form->addElement('hidden', 'repository', $repo_id);
	$form->addElement('hidden', 'usermoodle', $CFG->wwwroot);
	$form->addElement('hidden', 'usercourse', $course_id);
	
	$form->addElement('static', NULL, '', SharingCart_Repository::getString('press_download'));
	
	$form->addElement('submit', 'download', SharingCart_Repository::getString('download'));
	
	SharingCart_Repository::printForm(
		$form,
		SharingCart_Repository::getString('download_from_repository'),
		$course_id
	);
	*/
	
	$params = array(
		'id'         => $config[$repo_id]->instance,
		'mode'       => 'download',
		'username'   => $config[$repo_id]->username,
		'password'   => $config[$repo_id]->password,
		'repository' => $repo_id,
		'usermoodle' => $CFG->wwwroot,
		'usercourse' => $course_id,
	);
	
	SharingCart_Repository::printHeader(
		SharingCart_Repository::getString('download_from_repository'),
		$course_id
	);
	{
		echo '
		<div style="text-align:center; width:100%;">
			<span>'.SharingCart_Repository::getString('press_download').'</span>
		</div>';
		
		echo '
		<div style="text-align:center; width:100%; margin:1em;">
			<form id="downloadform" method="POST"
			 action="'.$config[$repo_id]->url.'/course/format/repository/material.php">
			<div style="display:hidden;">';
			foreach ($params as $k => $v) {
				echo '
				<input type="hidden" name="'.$k.'" value="'.$v.'" />';
			}
			echo '
			</div>
			<div>
				<input type="submit" value="'.SharingCart_Repository::getString('download').'" />
			</div>
			</form>
		</div>';
		
		// 自動でリポジトリのダウンロードページへジャンプ
		echo '
		<script type="text/javascript">
		//<![CDATA[
			setTimeout(function ()
			{
				document.forms["downloadform"].submit();
			}, 100);
		//]]>
		</script>';
	}
	SharingCart_Repository::printFooter();
	
} catch (Exception $e) {
	error((string)$e);
}

?>