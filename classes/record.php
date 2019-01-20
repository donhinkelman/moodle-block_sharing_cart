<?php
/**
 *  Sharing Cart - Record Manager
 *  
 *  @author  VERSION2, Inc.
 *  @version $Id: record.php 718 2012-05-08 01:35:06Z malu $
 */

namespace sharing_cart;

require_once __DIR__.'/exception.php';

class record
{
	const TABLE = 'block_sharing_cart';
	
	public $id       = null;
	public $userid   = null;
	public $modname  = null;
	public $modicon  = '';
	public $modtext  = null;
	public $ctime    = null;
	public $filename = null;
	public $tree     = '';
	public $weight   = 0;
	
	/**
	 *  Constructor
	 *  
	 *  @param mixed $record = empty
	 */
	public function __construct($record = array())
	{
		foreach ((array)$record as $field => $value)
			$this->{$field} = $value;
		
		// default values
		$this->userid or $this->userid = $GLOBALS['USER']->id;
		$this->ctime or $this->ctime = time();
	}
	
	/**
	 *  Create record instance from record ID
	 *  
	 *  @param int $id
	 *  @return record
	 */
	public static function from_id($id)
	{
		$record = $GLOBALS['DB']->get_record(self::TABLE, array('id' => $id));
		if (!$record)
			throw new exception('record_id');
		return new self($record);
	}
	
	/**
	 *  Insert record
	 */
	public function insert()
	{
		$this->id = $GLOBALS['DB']->insert_record(self::TABLE, $this);
		if (!$this->id)
			throw new exception('record');
		$this->renumber($this->userid);
	}
	/**
	 *  Update record
	 */
	public function update()
	{
		if (!$GLOBALS['DB']->update_record(self::TABLE, $this))
			throw new exception('record');
		$this->renumber($this->userid);
	}
	/**
	 *  Delete record
	 */
	public function delete()
	{
		if (!$GLOBALS['DB']->delete_records(self::TABLE,
			array('id' => $this->id)))
		{
			throw new exception('record');
		}
		$this->renumber($this->userid);
	}
	
	/**
	 *  Renumber all items sequentially
	 *  
	 *  @param int $user_id = $USER->id
	 */
	public static function renumber($user_id = null)
	{
		if ($items = $GLOBALS['DB']->get_records(self::TABLE,
			array('userid' => $user_id ?: $GLOBALS['USER']->id)))
		{
			$tree = array();
			foreach ($items as $it) {
				if (!isset($tree[$it->tree]))
					$tree[$it->tree] = array();
				$tree[$it->tree][] = $it;
			}
			foreach ($tree as $items) {
				usort($items, function ($lhs, $rhs)
				{
					// keep their order if already weighted
					if ($lhs->weight < $rhs->weight) return -1;
					if ($lhs->weight > $rhs->weight) return +1;
					// order by modtext otherwise
					return strnatcasecmp($lhs->modtext, $rhs->modtext);
				});
				foreach ($items as $i => $it) {
					if (!$GLOBALS['DB']->set_field(self::TABLE,
						'weight', 1 + $i, array('id' => $it->id)))
					{
					    throw new exception('record');
					}
				}
			}
		}
	}
}
