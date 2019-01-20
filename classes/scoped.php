<?php
/**
 *  Sharing Cart
 *  
 *  @author  VERSION2, Inc.
 *  @version $Id: scoped.php 783 2012-09-11 06:48:57Z malu $
 */
namespace sharing_cart;

/**
 *  Scoped closure
 */
class scoped
{
	/** @var callable */
	private $callback;
	
	/**
	 *  Constructor
	 *  
	 *  @param callable $callback
	 */
	public function __construct(/*callable*/ $callback)
	{
		$this->callback = $callback;
	}
	
	/**
	 *  Destructor
	 */
	public function __destruct()
	{
		call_user_func($this->callback);
	}
}
