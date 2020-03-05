<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *  Sharing Cart
 *
 * @package    block_sharing_cart
 * @copyright  2017 (C) VERSION2, INC.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 *  Sharing Cart upgrade
 *
 * @global moodle_database $DB
 */
function xmldb_block_sharing_cart_upgrade($oldversion = 0) {
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

    if ($oldversion < 2017071111) {
        $table = new xmldb_table('block_sharing_cart');

        $field = new xmldb_field('course', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0);
        $key = new xmldb_key('course', XMLDB_KEY_FOREIGN, array('course'), 'course', array('id'));

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            $dbman->add_key($table, $key);
        }

        upgrade_block_savepoint(true, 2017071111, 'sharing_cart');
    }

    if ($oldversion < 2017121200) {
        $table = new xmldb_table('block_sharing_cart');
        $field = new xmldb_field('section', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('block_sharing_cart_sections');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, null, '', 'id');
            $table->add_field('summary', XMLDB_TYPE_TEXT, null, null, null, null, '', 'name');
            $table->add_field('summaryformat', XMLDB_TYPE_INTEGER, 2, null, XMLDB_NOTNULL, null, 0, 'summary');

            $table->add_key('id', XMLDB_KEY_PRIMARY, array('id'));
            $dbman->create_table($table);
        }

        upgrade_block_savepoint(true, 2017121200, 'sharing_cart');
    }

    return true;
}
