<?php
/**
 * リポジトリプラグイン共用クラス
 *
 * @author VERSION2 Inc.
 * @version $Id: SharingCart_Repository.php 374 2009-10-26 07:30:37Z malu $
 * @package repository
 */

require_once dirname(__FILE__).'/../../../../config.php';

require_once dirname(__FILE__).'/../../plugins.php';

class SharingCart_RepositoryException extends Exception {}

class SharingCart_Repository
{
	public static function getString($name)
	{
		return sharing_cart_plugins::get_string($name, 'repository');
	}
	
	public static function getConfig($user, $throw = true)
	{
		$config = sharing_cart_plugins::get_config('repository', $user->id);
		if (!is_array($config)) {
			if ($throw)
				throw new SharingCart_RepositoryException('Repository settings was missing');
			$config = array();
		}
		return $config;
	}
	
	public static function setConfig($user, $config)
	{
		return sharing_cart_plugins::set_config('repository', $config, $user->id);
	}
	
	public static function getDownloadName($id)
	{
		static $time = null;
		if (empty($time)) {
			$time = date('Ymd-His');
		}
		return 'material-'.$time.'-'.$id.'.zip';
	}
	
	public static function printHeader($title = '', $course_id = SITEID, $cache = false)
	{
		$navlinks = array();
		if ($course_id != SITEID) {
			$navlinks[] = array(
				'name' => get_field('course', 'shortname', 'id', $course_id),
				'link' => $GLOBALS['CFG']->wwwroot.'/course/view.php?id='.$course_id,
				'type' => 'title'
			);
		}
		$navlinks[] = array(
			'name' => $title,
			'link' => '',
			'type' => 'title'
		);
		print_header_simple($title, '', build_navigation($navlinks), '', '', $cache);
		
		echo '<div style="float:right;">';
		helpbutton('repository', sharing_cart_plugins::get_string('title', 'repository'),
		           'block_sharing_cart/plugins/repository', true, false, '', false);
		echo '</div>';
		echo '<div style="clear:both;"><!-- clear float --></div>';
		
		print_heading($title);
	}
	
	public static function printForm($form, $title = '', $course_id = SITEID,
		$return_to = null, $return_text = null, $cache = false)
	{
		self::printHeader($title, $course_id, $cache);
		{
			if (is_array($form)) {
				foreach ($form as $it) {
					$it->display();
				}
			} else {
				$form->display();
			}
		}
		self::printFooter($return_to, $return_text);
	}
	
	public static function printFooter($return_to = null, $return_text = null)
	{
		if (!empty($return_to)) {
			if (empty($return_text)) {
				$return_text = get_string('back');
			}
			$return_text = htmlspecialchars($return_text);
			echo '<div style="text-align:center; margin:auto;">',
			     '<a href="', $return_to, '" title="', $return_text, '">',
			     $return_text,
			     '</a>',
			     '</div>';
		}
		print_footer();
	}
}

?>