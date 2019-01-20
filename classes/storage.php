<?php
/**
 *  Sharing Cart - Moodle Storage Manager Wrapper
 *  
 *  @author  VERSION2, Inc.
 *  @version $Id: storage.php 503 2011-07-20 07:11:19Z malu $
 */

namespace sharing_cart;

class storage
{
	/**
	 *  Constructor
	 *  
	 *  @param int $user_id = $USER->id
	 */
	public function __construct($user_id = null)
	{
		$this->storage = \get_file_storage();
		$this->context = \get_context_instance(
			CONTEXT_USER, $user_id ?: $GLOBALS['USER']->id);
	}
	
	/**
	 *  Get a file_storage instance by filename
	 *  
	 *  @param string $filename
	 *  @return file_storage
	 */
	public function get($filename)
	{
		return $this->storage->get_file(
			$this->context->id, 'user', 'backup', 0, '/', $filename);
	}
	
	/**
	 *  Delete a file in the Moodle Storage
	 *  
	 *  @param string $filename
	 *  @return boolean
	 */
	public function delete($filename)
	{
		$file = $this->get($filename);
		return $file && $file->delete();
	}
	
	private $storage, $context;
}
