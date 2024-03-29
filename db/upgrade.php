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

        $field = new xmldb_field('user', XMLDB_TYPE_INTEGER, 10, true, XMLDB_NOTNULL, null, null);
        $dbman->rename_field($table, $field, 'userid');

        $field = new xmldb_field('name', XMLDB_TYPE_CHAR, 32, null, XMLDB_NOTNULL, null, null);
        $dbman->rename_field($table, $field, 'modname');

        $field = new xmldb_field('icon', XMLDB_TYPE_CHAR, 32, null, XMLDB_NOTNULL, null, null);
        $dbman->rename_field($table, $field, 'modicon');

        $field = new xmldb_field('text', XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, null, null);
        $dbman->rename_field($table, $field, 'modtext');
        $field = new xmldb_field('modtext', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $dbman->change_field_type($table, $field);

        $field = new xmldb_field('time', XMLDB_TYPE_INTEGER, 10, true, XMLDB_NOTNULL, null, null);
        $dbman->rename_field($table, $field, 'ctime');

        $field = new xmldb_field('file', XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, null, null);
        $dbman->rename_field($table, $field, 'filename');

        $field = new xmldb_field('sort', XMLDB_TYPE_INTEGER, 10, true, XMLDB_NOTNULL, null, null);
        $dbman->rename_field($table, $field, 'weight');
    }

    if ($oldversion < 2011111101) {
        $table = new xmldb_table('sharing_cart_plugins');

        $field = new xmldb_field('user', XMLDB_TYPE_INTEGER, 10, true, XMLDB_NOTNULL, null, null);
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
            $table->add_field('id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('name', XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, null, null, 'id');
            $table->add_field('summary', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name');
            $table->add_field('summaryformat', XMLDB_TYPE_INTEGER, 2, null, XMLDB_NOTNULL, false, 0, 'summary');

            $table->add_key('id', XMLDB_KEY_PRIMARY, array('id'));
            $dbman->create_table($table);
        }

        upgrade_block_savepoint(true, 2017121200, 'sharing_cart');
    }

    // Fix default value incompatible with moodle database manager
    if ($oldversion < 2020073001) {
        $table = new xmldb_table('block_sharing_cart_sections');

        if ($dbman->table_exists($table)) {
            $field_name = new xmldb_field('name', XMLDB_TYPE_CHAR, 255);
            $field_summary = new xmldb_field('summary', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name');
            $field_summaryformat = new xmldb_field('summaryformat', XMLDB_TYPE_INTEGER, 2, null, XMLDB_NOTNULL, false, 0, 'summary');

            if ($dbman->field_exists($table, $field_name)) {
                $dbman->change_field_default($table, $field_name);
            }
            if ($dbman->field_exists($table, $field_summary)) {
                $dbman->change_field_default($table, $field_summary);
            }
            if ($dbman->field_exists($table, $field_summaryformat)) {
                $dbman->change_field_default($table, $field_summaryformat);
            }

            upgrade_block_savepoint(true, 2020073001, 'sharing_cart');
        }
    }

    if ($oldversion < 2020112001) {
        $table = new xmldb_table('block_sharing_cart_sections');

        if ($dbman->table_exists($table)) {
            $field_availability = new xmldb_field('availability', XMLDB_TYPE_TEXT, null, null, false, false, null, 'summaryformat');
            if (!$dbman->field_exists($table, $field_availability)) {
                $dbman->add_field($table, $field_availability);
            }
        }

        upgrade_block_savepoint(true, 2020112001, 'sharing_cart');
    }

    if ($oldversion < 2022111100) {

        // Remove redundant table, if it was created.
        $table = new xmldb_table('block_sharing_cart_log');

        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Fix potential mismatch with name field nullability from previous upgrade step.
        $table = new xmldb_table('block_sharing_cart_sections');
        $field = new xmldb_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'id');
        $dbman->change_field_notnull($table, $field);

        upgrade_block_savepoint(true, 2022111100, 'sharing_cart');
    }

    if ($oldversion < 2024011800) {

        // Remove records that have no owner.
        // (This could have been the leftover from the version before privacy api was introduced.)
        $sql = "SELECT i.id FROM {block_sharing_cart} i
                LEFT JOIN {user} u ON u.id = i.userid
                WHERE u.id IS NULL OR u.deleted = :deleted";
        $deleted_sharing_cart_ids = $DB->get_fieldset_sql($sql, ['deleted' => 1]);

        if (!empty($deleted_sharing_cart_ids)) {
            $DB->delete_records_list('block_sharing_cart', 'id', $deleted_sharing_cart_ids);
        }

        // Begin upgrade block_sharing_cart table.
        $table = new xmldb_table('block_sharing_cart');
        $field = new xmldb_field(
            'fileid',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            XMLDB_NOTNULL,
            null,
            0
        );

        // Add file id field to sharing cart table - for accelerating backup file selection.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add indexes to sharing cart table
        $index = new xmldb_index('weight', XMLDB_INDEX_NOTUNIQUE, ['weight']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new xmldb_index('tree', XMLDB_INDEX_NOTUNIQUE, ['tree']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new xmldb_index('section', XMLDB_INDEX_NOTUNIQUE, ['section']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new xmldb_index('fileid', XMLDB_INDEX_NOTUNIQUE, ['fileid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Mapping sharing cart records with user backup files.
        // This is for accelerating backup file selection and to eliminate uncertainty of the selection.
        $storage = get_file_storage();
        $sharing_cart_records = $DB->get_recordset('block_sharing_cart', [
            'fileid' => 0
        ], '', 'id, userid, filename');
        $user_backup_files = [];
        $deleted_sharing_cart_files = [];

        foreach ($sharing_cart_records as $record) {
            if (!isset($user_backup_files[$record->userid])) {
                try {
                    $context = context_user::instance($record->userid);
                    $files = $storage->get_area_files(
                        $context->id,
                        \block_sharing_cart\storage::COMPONENT,
                        \block_sharing_cart\storage::FILEAREA,
                        false,
                        'id',
                        false
                    );
                    foreach ($files as $file) {
                        $user_backup_files[$record->userid][$file->get_filename()] = $file->get_id();
                    }
                }
                catch (moodle_exception $exception) {
                    if ($exception->errorcode === 'invaliduser') {
                        $deleted_sharing_cart_files[] = $record->id;
                        continue;
                    }
                    throw $exception;
                }
            }

            if (isset($user_backup_files[$record->userid][$record->filename])) {
                $record->fileid = $user_backup_files[$record->userid][$record->filename];
                $DB->update_record('block_sharing_cart', $record);
            }
            else {
                $deleted_sharing_cart_files[] = $record->id;
            }
        }
        $sharing_cart_records->close();

        // Remove sharing cart records that are not mapped with user backup files.
        if (!empty($deleted_sharing_cart_files)) {
            $DB->delete_records_list('block_sharing_cart', 'id', $deleted_sharing_cart_files);
        }

        upgrade_block_savepoint(true, 2024011800, 'sharing_cart');
    }

    return true;
}
