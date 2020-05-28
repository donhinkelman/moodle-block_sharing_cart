<?php

namespace block_sharing_cart;

/**
 * Class logger
 * @package block_sharing_cart
 */
class mysql_logger{

	/**
	 * @var bool
	 */
	protected static $checked = false;

	/**
	 * @var null
	 */
	protected $table;

	/**
	 * logger constructor.
	 * @param string $table
	 * @throws \ddl_exception
	 */
	public function __construct($table = 'block_sharing_cart_log'){

		$this->table = $table;

		$tables = $this->get_db()->get_tables(true);
		if(self::$checked === false){
			$tables = $this->get_db()->get_tables(false);
			self::$checked = true;
		}

		// Table not exists - create
		if($table !== null && in_array($table, $tables, true) === false){
			$manager = $this->get_db()->get_manager();

			$xmldb_table = new \xmldb_table($table);

			$xmldb_table->add_field('id', XMLDB_TYPE_INTEGER, 11, true, XMLDB_NOTNULL, true);
			$xmldb_table->add_key('id', XMLDB_KEY_PRIMARY, ['id']);

			$xmldb_table->add_field('message', XMLDB_TYPE_TEXT);
			$xmldb_table->add_field('exception_as_json', XMLDB_TYPE_TEXT, null, null, null, false, null);

			$xmldb_table->add_field('timecreated', XMLDB_TYPE_INTEGER, 11);
			$xmldb_table->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

			$manager->create_table($xmldb_table);
		}
	}

	/**
	 * @return \moodle_database
	 */
	protected function get_db(): \moodle_database{
		global $DB;
		return $DB;
	}

	/**
	 * @param $message
	 * @param null $exception
	 * @return bool
	 * @throws \dml_exception
	 */
	public function log($message, $exception = null): bool{
		$data = (object)[];
		$data->message = $message;
		$data->timecreated = time();

		if($exception !== null){
			$data->exception_as_json = json_encode($exception);
		}

		return $this->get_db()->insert_record($this->table, $data, false);
	}
}