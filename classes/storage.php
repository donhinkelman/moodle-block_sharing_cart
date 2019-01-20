<?php
/**
 *  Sharing Cart
 *  
 *  @author  VERSION2, Inc.
 *  @version $Id: storage.php 778 2012-09-07 08:41:56Z malu $
 */
namespace sharing_cart;

/**
 *  Sharing Cart file storage manager
 */
class storage
{
	const COMPONENT = 'user';
	const FILEAREA  = 'backup';
	const ITEMID    = 0;
	const FILEPATH  = '/';
	
	/** @var \file_storage */
	private $storage;
	/** @var \context */
	private $context;
	
	/**
	 *  Constructor
	 *  
	 *  @param int $userid = $USER->id
	 */
	public function __construct($userid = null)
	{
		$this->storage = \get_file_storage();
		$this->context = \context_user::instance($userid ?: $GLOBALS['USER']->id);
	}
	
	/**
	 *  Copy a stored file into storage
	 *  
	 *  @param \stored_file $file
	 */
	public function copy_from(\stored_file $file)
	{
		$filerecord = (object)array(
			'contextid' => $this->context->id,
			'component' => self::COMPONENT,
			'filearea'  => self::FILEAREA,
			'itemid'    => self::ITEMID,
			'filepath'  => self::FILEPATH,
			);
		$this->storage->create_file_from_storedfile($filerecord, $file);
	}
	
	/**
	 *  Get a stored_file instance by filename
	 *  
	 *  @param string $filename
	 *  @return \stored_file
	 */
	public function get($filename)
	{
		return $this->storage->get_file($this->context->id,
			self::COMPONENT, self::FILEAREA, self::ITEMID, self::FILEPATH,
			$filename);
	}
	
	/**
	 *  Delete a file in the storage by filename
	 *  
	 *  @param string $filename
	 *  @return boolean
	 */
	public function delete($filename)
	{
		$file = $this->get($filename);
		return $file && $file->delete();
	}
}
