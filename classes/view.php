<?php
/**
 *  Sharing Cart - View Utilities
 *  
 *  @author  VERSION2, Inc.
 *  @version $Id: view.php 536 2011-11-13 14:19:55Z malu $
 */

namespace sharing_cart\view;

function spacer($width, $height)
{
	return $GLOBALS['OUTPUT']->spacer(array('height' => $height, 'width' => $width));
}

function icon(/*record*/ $item)
{
	return $item->modicon
		? '<img src="' . $GLOBALS['OUTPUT']->pix_url($item->modicon) . '" alt="" />'
		: ($item->modname != 'label'
			? '<img src="' . $GLOBALS['OUTPUT']->pix_url('icon', $item->modname) . '" alt="" />'
			: ''
		);
}
