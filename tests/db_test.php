<?php

defined('MOODLE_INTERNAL') || die();

/**
 * DO NOT RUN THIS IN Production environment!
 * Test sharing cart database upgrade
 * Purpose of test to see if upgrade script is working correctly
 * @package block_sharing_cart\tests
 */
class block_sharing_cart_db_testcase extends advanced_testcase {
    private $junk_tables = [];

    /**
     * This method is called before each test.
     */
    protected function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Test upgrade script before version 2011111100
     * This test try to simulate the upgrade script that rename sharing cart columns
     * @see xmldb_block_sharing_cart_upgrade() below the code: if ($oldversion < 2011111100)...
     * @test
     */
    public function change_sharing_cart_table_column_names() {
        $table = new xmldb_table($this->random_name());
        $fields = [
            'id' => new xmldb_field('id', XMLDB_TYPE_INTEGER, 10, true, XMLDB_NOTNULL, true),
            'userid' => new xmldb_field('user', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false, 0),
            'modname' => new xmldb_field('name', XMLDB_TYPE_CHAR, 32, null, XMLDB_NOTNULL, false),
            'modicon' => new xmldb_field('icon', XMLDB_TYPE_CHAR, 32, null, XMLDB_NOTNULL, false),
            'modtext' => new xmldb_field('text', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, false),
            'ctime' => new xmldb_field('time', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false, 0),
            'filename' => new xmldb_field('file', XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, false),
            'tree' => new xmldb_field('tree', XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, false),
            'weight' => new xmldb_field('sort', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false, 0)
        ];

        $table = $this->create_table($table, $fields);
        $old_column_names = $this->get_column_names($table);

        // Asserting current column names
        foreach ($fields as $field) {
            $this->assertContains($field->getName(), $old_column_names);
        }

        $dbman = self::db()->get_manager();

        // Change column names
        foreach ($fields as $name => $field) {
            // Skip column that contain the same name
            if ($field->getName() === $name) {
                continue;
            }
            $dbman->rename_field($table, $field, $name);
        }

        // Asserting new column names
        $new_column_names = $this->get_column_names($table);
        foreach ($fields as $name => $field) {
            $this->assertContains($name, $new_column_names);
        }

        $this->drop_junk_tables();
    }

    /**
     * Test upgrade script before version 2017121200
     * This test try to simulate the upgrade script that contain bug when upgrading the plugin the column that have NOT NULL should not have default value as empty string
     * @see xmldb_block_sharing_cart_upgrade() below the code: if ($oldversion < 2017121200)...
     * @test
     */
    public function create_sharing_cart_section_table() {
        $this->setAdminUser();

        // New script
        $working_table = new xmldb_table($this->random_name());
        $fields = [
            'id' => new xmldb_field('id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null),
            'name' => new xmldb_field('name', XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, null, null, 'id'),
            'summary' => new xmldb_field('summary', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name'),
            'summaryformat' => new xmldb_field('summaryformat', XMLDB_TYPE_INTEGER, 2, null, XMLDB_NOTNULL, false, 0, 'summary'),
        ];
        $working_table = $this->create_table($working_table, $fields);
        $this->assertEmpty($working_table->getAllErrors());

        $this->drop_junk_tables();
    }

    /**
     * Test upgrade script before version 2020072700
     * @throws ddl_exception
     * @throws ddl_field_missing_exception
     * @throws ddl_table_missing_exception|coding_exception
     *@see xmldb_block_sharing_cart_upgrade() below the code: if ($oldversion < 2020072700)...
     * @test
     */
    public function change_default_value_for_section_table() {
        $dbman = self::db()->get_manager();
        $table = $this->create_section_table();

        $field_name = new xmldb_field('name', XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL);
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

        $this->drop_junk_tables();
    }

    /**
     * Create sharing cart sections similar to install.xml
     * @param string|null $table_name
     * @return xmldb_table
     * @throws ddl_exception|coding_exception
     */
    private function create_section_table(&$table_name = null) {
        if (empty($table_name)) {
            $table_name = $this->random_name();
        }

        // Initiate table
        $table = new xmldb_table($table_name);

        // Set table fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, 255, null, null, null, '', 'id');
        $table->add_field('summary', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name');
        $table->add_field('summaryformat', XMLDB_TYPE_INTEGER, 2, null, XMLDB_NOTNULL, null, 0, 'summary');

        // Set index
        $table->add_key('id', XMLDB_KEY_PRIMARY, array('id'));

        return $this->create_table($table);
    }

    /**
     * @param xmldb_table $table
     * @param array $fields
     * @return xmldb_table
     * @throws coding_exception
     * @throws ddl_exception
     */
    private function create_table(xmldb_table $table, array $fields = []) {
        foreach ($fields as $field) {
            if (!$field instanceof xmldb_field) {
                continue;
            }
            $table->addField($field);

            $field_name = $field->getName();
            if ($field_name === 'id') {
                $table->add_key(
                    $field_name,
                    XMLDB_KEY_PRIMARY,
                    [$field_name]
                );
            }
        }

        $dbman = self::db()->get_manager();
        $dbman->create_table($table);
        $this->junk_tables[$table->getName()] = $table;
        return $table;
    }

    /**
     * Drop junk tables that created by $this->create_table(...) method
     * @param mixed ...$tables if empty, it will drop all junk tables
     * @throws ddl_exception
     * @throws ddl_table_missing_exception
     */
    private function drop_junk_tables(...$tables) {
        if (empty($tables)) {
            $tables = $this->junk_tables;
        }

        $exist_tables = [];
        foreach ($tables as $name => $table) {
            if (isset($this->junk_tables[$name])) {
                $exist_tables[] = $table;
            }
        }

        if (!empty($exist_tables)) {
            $this->drop_tables(...$exist_tables);
        }
    }

    /**
     * @param array $tables
     * @throws ddl_exception
     * @throws ddl_table_missing_exception
     */
    private function drop_tables(...$tables) {
        $dbman = self::db()->get_manager();

        foreach ($tables as $table) {
            if (is_string($table)) {
                $table = new xmldb_table($table);
            }
            if (!$table instanceof xmldb_table) {
                continue;
            }

            $dbman->drop_table($table);
        }
    }

    /**
     * @param int $length
     * @return string
     * @throws Exception
     */
    private function random_name($length = 16) {
        $chars = 'abcdefghijklmnopqrstuvwxyz';
        $position = mt_rand(0, strlen($chars) - 1);
        $first_letter = substr($chars, $position, 1);

        return $first_letter . substr(
            bin2hex(random_bytes($length)),
            0,
            $length - 1
        );
    }

    /**
     * @param xmldb_table $table
     * @return string[]
     */
    private function get_column_names(xmldb_table $table) {
        $columns = self::db()->get_columns($table->getName());

        if (empty($columns)) {
            return [];
        }

        return array_map(function($column) {
            return $column->name;
        }, $columns);
    }

    /**
     * Get moodle database
     * @return moodle_database
     */
    private static function db() {
        global $DB;
        return $DB;
    }
}
