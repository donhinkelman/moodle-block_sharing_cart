<?php // $Id: upgrade.php 905 2012-12-05 05:36:52Z malu $/

defined('MOODLE_INTERNAL') || die;

/**
 *  Sharing Cart upgrade
 *  
 *  @global moodle_database $DB
 */
function xmldb_block_sharing_cart_upgrade($oldversion = 0)
{
	global $DB;
	
	$dbman = $DB->get_manager();
	
	if ($oldversion < 2011111100) {
		$table = new xmldb_table('sharing_cart');
		
		$field = new xmldb_field('user', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
		$dbman->rename_field($table, $field, 'userid');
		
		$field = new xmldb_field('name', XMLDB_TYPE_CHAR, 32, null, XMLDB_NOTNULL, null, null);
		$dbman->rename_field($table, $field, 'modname');
		
		$field = new xmldb_field('icon', XMLDB_TYPE_CHAR, 32, null, XMLDB_NOTNULL, null, null);
		$dbman->rename_field($table, $field, 'modicon');
		
		$field = new xmldb_field('text', XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, null, null);
		$dbman->rename_field($table, $field, 'modtext');
		$field = new xmldb_field('modtext', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
		$dbman->change_field_type($table, $field);
		
		$field = new xmldb_field('time', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
		$dbman->rename_field($table, $field, 'ctime');
		
		$field = new xmldb_field('file', XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, null, null);
		$dbman->rename_field($table, $field, 'filename');
		
		$field = new xmldb_field('sort', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
		$dbman->rename_field($table, $field, 'weight');
	}
	
	if ($oldversion < 2011111101) {
		$table = new xmldb_table('sharing_cart_plugins');
		
		$field = new xmldb_field('user', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
		$dbman->rename_field($table, $field, 'userid');
	}
	
	if ($oldversion < 2012050800) {
		$table = new xmldb_table('sharing_cart');
		$dbman->rename_table($table, 'block_sharing_cart');
		
		$table = new xmldb_table('sharing_cart_plugins');
		$dbman->rename_table($table, 'block_sharing_cart_plugins');
	}
	
	if ($oldversion < 2016032900) {
		// Define key userid (foreign) to be added to block_sharing_cart.
		$table = new xmldb_table('block_sharing_cart');
		$key = new xmldb_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
		
		// Launch add key userid.
		$dbman->add_key($table, $key);
	
		// Sharing_cart savepoint reached.
		upgrade_block_savepoint(true, 2016032900, 'sharing_cart');
	}
	
	return true;
}
