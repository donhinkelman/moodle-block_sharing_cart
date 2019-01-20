<?php

function xmldb_block_sharing_cart_upgrade($oldversion = 0) {
    global $CFG, $THEME, $db;

    $result = true;

    if ($oldversion < 2009020300) {
        $result = execute_sql("ALTER TABLE `{$CFG->prefix}sharing_cart`
            ADD `file` VARCHAR(255) NOT NULL DEFAULT '' AFTER `time`");
        if ($result) {
            require_once dirname(__FILE__).'../sharing_cart_table.php';
            if ($shared_items = get_records('sharing_cart')) {
                foreach ($shared_items as $shared_item) {
                    $shared_item->file = sharing_cart_table::gen_zipname($shared_item->time);
                    update_record('sharing_cart', $shared_item);
                }
            }
        }
    }

    if ($oldversion < 2009040600) {
        $result = execute_sql("CREATE TABLE `{$CFG->prefix}sharing_cart_plugins` (
            `id`     INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `plugin` VARCHAR(32)      NOT NULL,
            `user`   INT(10) UNSIGNED NOT NULL,
            `data`   TEXT             NOT NULL
        )");
    }

    if ($oldversion < 2012053000) {
        $table = new XMLDBTable('sharing_cart');
        $fields = array(
            'user' => array('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, '0', 'id'),
            'name' => array('modname', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, '', 'userid'),
            'icon' => array('modicon', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, '', 'modname'),
            'text' => array('modtext', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, '', 'modicon'),
            'time' => array('ctime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, '0', 'modtext'),
            'file' => array('filename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, '', 'ctime'),
            'sort' => array('weight', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, '0', 'tree'),
            );
        foreach ($fields as $oldname => $newinfo) {
            list ($newname, $type, $precision, $unsigned, $notnull, $default, $previous) = $newinfo;
            $field = new XMLDBField($oldname);
            $field->setAttributes($type, $precision, $unsigned, $notnull, null, null, null, $default, $previous);
            $result = $result && rename_field($table, $field, $newname);
        }
        $field = new XMLDBField('modtext');
        $field->setAttributes(XMLDB_TYPE_TEXT, 'medium', null, XMLDB_NOTNULL, null, null, null, '', 'modicon');
        $result = $result && change_field_type($table, $field);

        $table = new XMLDBTable('sharing_cart_plugins');
        $fields = array(
            'user' => array('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, '0', 'id'),
            );
        foreach ($fields as $oldname => $newinfo) {
            list ($newname, $type, $precision, $unsigned, $notnull, $default, $previous) = $newinfo;
            $field = new XMLDBField($oldname);
            $field->setAttributes($type, $precision, $unsigned, $notnull, null, null, null, $default, $previous);
            $result = $result && rename_field($table, $field, $newname);
        }
    }

    return $result;
}
