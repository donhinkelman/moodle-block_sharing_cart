<?php // $Id: repository.php 430 2009-12-03 09:29:46Z malu $

class sharing_cart_plugin_repository implements sharing_cart_plugin
{
/** implements **/
	public function get_command()
	{
		if (!$this->isEnabled()) {
			return NULL;
		}
		$alt = sharing_cart_plugins::get_string('upload_to_repository', 'repository');
		return
			'<a title="'.$alt.'" href="javascript:void(0);"'.
			' onclick="return sharing_cart.repository_upload(this);">'.
				'<img src="'.$this->dir.'/pix/upload.gif" class="iconsmall" alt="'.$alt.'" />'.
			'</a>';
	}
	public function get_header()
	{
		$alt = sharing_cart_plugins::get_string('settings', 'repository');
		return
			'<a class="icon" title="'.$alt.'" href="'.$this->dir.
			'/settings.php?course='.$GLOBALS['COURSE']->id.'">'.
				'<img src="'.$GLOBALS['CFG']->pixpath.'/i/admin.gif" alt="'.$alt.'" />'.
			'</a>';
	}
	public function get_footer()
	{
		if (!$this->isEnabled()) {
			return NULL;
		}
		$html =
			'<div id="sharing_cart_repository_header">'.
				'<span style="font-weight:bold;">'.
					sharing_cart_plugins::get_string('title', 'repository').
				'</span>'.
				'<span style="margin-left:4px;">'.
					helpbutton('repository', sharing_cart_plugins::get_string('title', 'repository'),
					           'block_sharing_cart/plugins/repository', true, false, '', true).
				'</span>'.
			'</div>';
		
		$base = $GLOBALS['CFG']->wwwroot.'/blocks/sharing_cart/plugins/repository/download.php'
		      . '?course='.$GLOBALS['COURSE']->id.'&amp;repoid=';
		$icon = $GLOBALS['CFG']->wwwroot.'/blocks/sharing_cart/plugins/repository/pix/download.gif';
		$alt  = sharing_cart_plugins::get_string('download', 'repository');
		$html .= '<div>'.sharing_cart_plugins::get_string('download_from', 'repository').'</div>';
		$html .= '<ul class="list">';
		foreach ($this->config as $id => $info) {
			if (empty($info->enabled)) {
				continue;
			}
			if (empty($info->sitename)) {
				$sitename = sprintf('[%s #%u]',
					sharing_cart_plugins::get_string('title', 'repository'), $id);
			} else {
				$sitename = htmlspecialchars($info->sitename);
			}
			$html .=
				'<li class="r0" style="line-height:1.2em;">'.
					'<div class="icon column c0">'.
						'<img src="'.$icon.'" alt="'.$alt.'" />'.
					'</div>'.
					'<div class="column c1">'.
						'<a href="'.$base.$id.'">'.$sitename.'</a>'.
					'</div>'.
				'</li>';
		}
		$html .= '</ul>';
		
		return $html;
	}
	public function get_import()
	{
		return 'repository/script.js';
	}
	public function get_script()
	{
		return NULL;
	}
	
/** internals **/
	public function __construct()
	{
		$this->config = sharing_cart_plugins::get_config('repository', $GLOBALS['USER']->id);
		if (!is_array($this->config)) {
			$this->config = array();
		}
		
		$this->dir = $GLOBALS['CFG']->wwwroot.'/blocks/sharing_cart/plugins/repository';
	}
	private function isEnabled()
	{
		foreach ($this->config as $id => $info) {
			if (!empty($info->enabled)) {
				return TRUE;
			}
		}
		return FALSE;
	}
	private $config = NULL, $dir = '';
}

sharing_cart_plugins::register(new sharing_cart_plugin_repository());

?>