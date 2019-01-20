<?php
/**
 *  Sharing Cart - Exceptions
 *  
 *  @author  VERSION2, Inc.
 *  @version $Id: exception.php 503 2011-07-20 07:11:19Z malu $
 */

namespace sharing_cart;

class exception extends \moodle_exception
{
	/**
	 *  Constructor
	 *  
	 *  @param string $errcode  The error string ID withour prefix "err:"
	 *  @param mixed  $a        (Optional) Additional parameter
	 */
	public function __construct($errcode, $a = null)
	{
		parent::__construct("err:$errcode", 'block_sharing_cart', '', $a);
	}
}
