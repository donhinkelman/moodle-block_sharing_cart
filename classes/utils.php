<?php
/**
 *  Sharing Cart - Miscellaneous Utilities
 *  
 *  @author  VERSION2, Inc.
 *  @version $Id: utils.php 503 2011-07-20 07:11:19Z malu $
 */

namespace sharing_cart\utils;

function post_update(array $post)
{
	foreach ($post as $key => $value) {
		$_REQUEST[$key] = $_POST[$key] = $value;
	}
}

function post_remove(array $post)
{
	foreach ($post as $key => $value) {
		unset($_REQUEST[$key]);
		unset($_POST[$key]);
	}
}

function js_urlencode($s)
{
	return implode('', array_map(
		function ($c)
		{
			return $c < 0x80 ? chr($c) : sprintf('%%u%04X', $c);
		},
		unpack('n*', mb_convert_encoding($s, 'UTF-16BE', 'UTF-8'))
	));
}

function js_urldecode($s)
{
	return preg_replace_callback(
		'#%u([0-9A-F]{2})([0-9A-F]{2})#i',
		function ($m)
		{
			$x = pack('CC', hexdec($m[1]), hexdec($m[2]));
			return mb_convert_encoding($x, 'UTF-8', 'UTF-16BE');
		},
		$s
	);
}
