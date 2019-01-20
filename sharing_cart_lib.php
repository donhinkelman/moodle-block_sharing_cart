<?php
/**
 *  sharing_cart ライブラリ
 */
class sharing_cart_lib
{
	/**
	 *  アイコン取得
	 */
	public static function get_icon($modname, $modicon = null)
	{
		if (empty($modicon)) {
			if ($modname == 'label')
				return '';
			return '<img src="'.$GLOBALS['CFG']->modpixpath.'/'.$modname.'/icon.gif" alt="" class="icon" />';
		} else {
			return '<img src="'.$GLOBALS['CFG']->pixpath.'/'.$modicon.'" alt="" class="icon" />';
		}
	}
}
