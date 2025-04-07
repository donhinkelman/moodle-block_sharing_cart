<?php

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

function xmldb_block_sharing_cart_upgrade($oldversion = 0): bool
{
    global $DB;

    $dbman = $DB->get_manager();

    $base_factory = \block_sharing_cart\app\factory::make();

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
        $key = new xmldb_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Launch add key userid.
        $dbman->add_key($table, $key);

        // Sharing_cart savepoint reached.
        upgrade_block_savepoint(true, 2016032900, 'sharing_cart');
    }

    if ($oldversion < 2017071111) {
        $table = new xmldb_table('block_sharing_cart');

        $field = new xmldb_field('course', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0);
        $key = new xmldb_key('course', XMLDB_KEY_FOREIGN, ['course'], 'course', ['id']);

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

            $table->add_key('id', XMLDB_KEY_PRIMARY, ['id']);
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
            $field_summaryformat = new xmldb_field(
                'summaryformat', XMLDB_TYPE_INTEGER, 2, null, XMLDB_NOTNULL, false, 0, 'summary'
            );

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
            $field_availability = new xmldb_field(
                'availability', XMLDB_TYPE_TEXT, null, null, false, false, null, 'summaryformat'
            );
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
            'fileid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0
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
                        'user',
                        'backup',
                        false,
                        'id',
                        false
                    );
                    foreach ($files as $file) {
                        $user_backup_files[$record->userid][$file->get_filename()] = $file->get_id();
                    }
                } catch (moodle_exception $exception) {
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
            } else {
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

    if ($oldversion < 2024072900) {
        /**
         * Create block_sharing_cart_items table.
         */
        $xmldb_table = new xmldb_table('block_sharing_cart_items');

        $xmldb_table->add_field('id', XMLDB_TYPE_INTEGER, '10', true, true, true);
        $xmldb_table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', true, true);
        $xmldb_table->add_field('file_id', XMLDB_TYPE_INTEGER, '10', true, false);
        $xmldb_table->add_field('parent_item_id', XMLDB_TYPE_INTEGER, '10', true, false);
        $xmldb_table->add_field('old_instance_id', XMLDB_TYPE_INTEGER, '10', true, true);
        $xmldb_table->add_field('type', XMLDB_TYPE_CHAR, '255', notnull: true);
        $xmldb_table->add_field('name', XMLDB_TYPE_CHAR, '255', notnull: true);
        $xmldb_table->add_field('status', XMLDB_TYPE_INTEGER, '10', true, true);
        $xmldb_table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', notnull: true);
        $xmldb_table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', notnull: true);

        $xmldb_table->add_key('id', XMLDB_KEY_PRIMARY, ['id']);

        $xmldb_table->add_index('user_id', XMLDB_INDEX_NOTUNIQUE, ['user_id']);
        $xmldb_table->add_index('file_id', XMLDB_INDEX_UNIQUE, ['file_id']);
        $xmldb_table->add_index('parent_item_id', XMLDB_INDEX_NOTUNIQUE, ['parent_item_id']);
        $xmldb_table->add_index('type', XMLDB_INDEX_NOTUNIQUE, ['type']);
        $xmldb_table->add_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);

        if (!$dbman->table_exists($xmldb_table)) {
            $dbman->create_table($xmldb_table);
        }

        /**
         * Migrate data from block_sharing_cart_sections & block_sharing_cart to block_sharing_cart_items.
         */

        /**
         * @var \file_storage $fs
         */
        $fs = get_file_storage();

        if ($dbman->table_exists(new \xmldb_table('block_sharing_cart_sections')) && $dbman->table_exists(
                new \xmldb_table('block_sharing_cart')
            )) {
            $old_section_records = $DB->get_recordset('block_sharing_cart_sections');
            foreach ($old_section_records as $old_section_record) {
                $time = time();

                $old_activity_records = $DB->get_recordset('block_sharing_cart', [
                    'section' => $old_section_record->id
                ]);

                $old_activity_records_by_user_id = [];
                $user_ids = [];
                foreach ($old_activity_records as $old_activity_record) {
                    $old_activity_records_by_user_id[(int)$old_activity_record->userid][] = $old_activity_record;
                    $user_ids[(int)$old_activity_record->userid] = (int)$old_activity_record->userid;
                }
                $old_activity_records->close();
                unset($old_activity_records);

                foreach ($user_ids as $user_id) {
                    try {
                        $old_activity_records = $old_activity_records_by_user_id[$user_id] ?? [];
                        if (empty($old_activity_records)) {
                            continue;
                        }

                        $new_section_record = (object)[
                            'user_id' => $user_id,
                            'file_id' => null,
                            'parent_item_id' => null,
                            'old_instance_id' => 0,
                            'type' => \block_sharing_cart\app\item\entity::TYPE_SECTION,
                            'name' => $old_section_record->name,
                            'status' => \block_sharing_cart\app\item\entity::STATUS_BACKEDUP,
                            'timecreated' => $time,
                            'timemodified' => $time,
                        ];
                        $section_item_id = $DB->insert_record('block_sharing_cart_items', $new_section_record);

                        foreach ($old_activity_records as $old_activity_record) {
                            try {
                                if ($old_activity_record->fileid === 0) {
                                    continue;
                                }

                                $backup_file = $fs->get_file_by_id($old_activity_record->fileid);
                                if ($backup_file === false) {
                                    continue;
                                }

                                $new_activity_record = (object)[
                                    'user_id' => $user_id,
                                    'file_id' => null,
                                    'parent_item_id' => $section_item_id,
                                    'old_instance_id' => 0,
                                    'type' => "mod_{$old_activity_record->modname}",
                                    'name' => strip_tags($old_activity_record->modtext ?? 'Unknown'),
                                    'status' => \block_sharing_cart\app\item\entity::STATUS_BACKUP_FAILED,
                                    'timecreated' => $time,
                                    'timemodified' => $time,
                                ];
                                $new_activity_record->id = $DB->insert_record(
                                    'block_sharing_cart_items',
                                    $new_activity_record
                                );

                                $new_file = $fs->create_file_from_storedfile([
                                    'contextid' => \core\context\user::instance($user_id)->id,
                                    'component' => 'block_sharing_cart',
                                    'filearea' => 'backup',
                                    'itemid' => $new_activity_record->id,
                                    'filepath' => '/',
                                    'filename' => $backup_file->get_filename(),
                                ], $backup_file);

                                $new_activity_record->file_id = $new_file->get_id();
                                $new_activity_record->status = \block_sharing_cart\app\item\entity::STATUS_BACKEDUP;

                                $DB->update_record('block_sharing_cart_items', $new_activity_record);
                            } catch (\Exception) {
                                if (isset($new_activity_record->id)) {
                                    $DB->update_record(
                                        'block_sharing_cart_items',
                                        (object)[
                                            'id' => $new_activity_record->id,
                                            'status' => \block_sharing_cart\app\item\entity::STATUS_BACKUP_FAILED
                                        ]
                                    );
                                }
                            }
                        }
                    } catch (\Exception) {
                    }
                }
            }
            $old_section_records->close();

            $old_activity_records = $DB->get_recordset('block_sharing_cart', [
                'section' => 0
            ]);
            foreach ($old_activity_records as $old_activity_record) {
                try {
                    if ($old_activity_record->fileid === 0) {
                        continue;
                    }

                    $backup_file = $fs->get_file_by_id($old_activity_record->fileid);
                    if ($backup_file === false) {
                        continue;
                    }

                    if (file_exists($fs->get_file_system()->get_remote_path_from_storedfile($backup_file)) === false) {
                        continue;
                    }

                    $time = time();

                    $new_activity_record = (object)[
                        'user_id' => $old_activity_record->userid,
                        'file_id' => null,
                        'parent_item_id' => null,
                        'old_instance_id' => 0,
                        'type' => "mod_{$old_activity_record->modname}",
                        'name' => strip_tags($old_activity_record->modtext ?? 'Unknown'),
                        'status' => \block_sharing_cart\app\item\entity::STATUS_BACKUP_FAILED,
                        'timecreated' => $time,
                        'timemodified' => $time,
                    ];
                    $new_activity_record->id = $DB->insert_record(
                        'block_sharing_cart_items',
                        $new_activity_record
                    );

                    $new_file = $fs->create_file_from_storedfile([
                        'contextid' => \core\context\user::instance($old_activity_record->userid)->id,
                        'component' => 'block_sharing_cart',
                        'filearea' => 'backup',
                        'itemid' => $new_activity_record->id,
                        'filepath' => '/',
                        'filename' => $backup_file->get_filename(),
                    ], $backup_file);

                    $new_activity_record->file_id = $new_file->get_id();
                    $new_activity_record->status = \block_sharing_cart\app\item\entity::STATUS_BACKEDUP;

                    $DB->update_record('block_sharing_cart_items', $new_activity_record);
                } catch (\Exception) {
                    if (isset($new_activity_record->id)) {
                        $DB->update_record(
                            'block_sharing_cart_items',
                            (object)[
                                'id' => $new_activity_record->id,
                                'status' => \block_sharing_cart\app\item\entity::STATUS_BACKUP_FAILED
                            ]
                        );
                    }
                }
            }
            $old_activity_records->close();
        }

        $table = new xmldb_table('block_sharing_cart');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        $table = new xmldb_table('block_sharing_cart_sections');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        $table = new xmldb_table('block_sharing_cart_plugins');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        upgrade_block_savepoint(true, 2024072900, 'sharing_cart');
    }

    if ($oldversion < 2024072900) {
        $xmldb_table = new xmldb_table('block_sharing_cart_items');

        if (!$dbman->field_exists($xmldb_table, 'sortorder')) {
            $dbman->add_field(
                $xmldb_table,
                new xmldb_field(
                    'sortorder', XMLDB_TYPE_INTEGER, '10', notnull: false
                )
            );
        }

        upgrade_block_savepoint(true, 2024072900, 'sharing_cart');
    }

    if ($oldversion < 2024101800) {
        $xmldb_table = new xmldb_table('block_sharing_cart_items');

        if (!$dbman->field_exists($xmldb_table, 'original_course_fullname')) {
            $dbman->add_field(
                $xmldb_table,
                new xmldb_field(
                    'original_course_fullname', XMLDB_TYPE_CHAR, 255, notnull: false
                )
            );
        }

        $item_record_set = $DB->get_recordset('block_sharing_cart_items', [
            'status' => \block_sharing_cart\app\item\entity::STATUS_BACKEDUP,
            'parent_item_id' => null
        ]);
        foreach ($item_record_set as $item) {
            try {
                /**
                 * @var \file_storage $fs
                 */
                $fs = get_file_storage();
                $file = $fs->get_file_by_id($item->file_id);
                if (!$file) {
                    continue;
                }

                $course_info = $base_factory->backup()->handler()->get_backup_course_info($file);
                $item->original_course_fullname = $course_info['fullname'] ?? null;

                $DB->update_record(
                    'block_sharing_cart_items',
                    $item
                );
            } catch (\Exception) {
            }
        }
        $item_record_set->close();

        upgrade_block_savepoint(true, 2024101800, 'sharing_cart');
    }

    if ($oldversion < 2024111302) {
        $xmldb_table = new xmldb_table('block_sharing_cart_items');
        if ($dbman->table_exists($xmldb_table)) {
            $xmldb_index = new xmldb_index('file_id');
            if ($dbman->index_exists($xmldb_table, $xmldb_index)) {
                $dbman->drop_index(
                    $xmldb_table,
                    $xmldb_index
                );
            }

            $xmldb_index = new xmldb_index('user_id');
            if ($dbman->index_exists($xmldb_table, $xmldb_index)) {
                $dbman->drop_index(
                    $xmldb_table,
                    $xmldb_index
                );
            }

            $xmldb_index = new xmldb_index('user_id', XMLDB_INDEX_NOTUNIQUE, ['user_id']);
            if (!$dbman->index_exists($xmldb_table, $xmldb_index)) {
                $dbman->add_index(
                    $xmldb_table,
                    $xmldb_index
                );
            }

            $xmldb_index = new xmldb_index('file_id', XMLDB_INDEX_UNIQUE, ['file_id']);
            if (!$dbman->index_exists($xmldb_table, $xmldb_index)) {
                $dbman->add_index(
                    $xmldb_table,
                    $xmldb_index
                );
            }
        }

        upgrade_block_savepoint(true, 2024111302, 'sharing_cart');
    }

    if ($oldversion < 2025040700) {
        $xmldb_table = new xmldb_table('block_sharing_cart_items');
        if (!$dbman->field_exists($xmldb_table, 'version')) {
            $dbman->add_field(
                $xmldb_table,
                new xmldb_field(
                    'version', XMLDB_TYPE_INTEGER, '10',true, XMLDB_NOTNULL,
                    null, 0, 'original_course_fullname'
                )
            );
        }

        $item_records = $DB->get_recordset('block_sharing_cart_items');
        foreach ($item_records as $item_record) {
            if ($item_record->version !== null)
            {
                continue;
            }

            $version = $item_record->old_instance_id === '0' ? 1 : 2 ;

            $DB->update_record(
                'block_sharing_cart_items',
                (object)[
                    'id' => $item_record->id,
                    'version' => $version
                ]
            );
        }

        // Removes the default of field version of table block_sharing_cart_items
        $dbman->change_field_default(
            $xmldb_table,
            new xmldb_field(
                'version', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL,
                null, null, 'original_course_fullname'
            )
        );

        $xmldb_index = new xmldb_index('version', XMLDB_INDEX_NOTUNIQUE, ['version']);
        if (!$dbman->index_exists($xmldb_table, $xmldb_index)) {
            $dbman->add_index($xmldb_table, $xmldb_index);
        }

        // Sharing_cart savepoint reached.
        upgrade_block_savepoint(true, 2025040700, 'sharing_cart');
    }

    return true;
}
