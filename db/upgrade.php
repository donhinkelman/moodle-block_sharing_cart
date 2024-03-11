<?php

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

function xmldb_block_sharing_cart_upgrade($oldversion = 0): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 12345) {
        upgrade_block_savepoint(true, 12345, 'sharing_cart');
    }

    return true;
}
