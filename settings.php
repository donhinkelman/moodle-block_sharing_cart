<?php // $Id: settings.php 934 2013-03-26 00:50:29Z malu $

defined('MOODLE_INTERNAL') || die;

require_once __DIR__.'/lib/settingslib.php';

if ($ADMIN->fulltree) {
    $settings->add(
        new admin_setting_configmulticheckboxmodtypes(
            'block_sharing_cart/userdata_copyable_modtypes',
            get_string('settings:userdata_copyable_modtypes', 'block_sharing_cart'),
            get_string('settings:userdata_copyable_modtypes_desc', 'block_sharing_cart'),
            array('data' => 1, 'forum' => 1, 'glossary' => 1, 'wiki' => 1)
        )
    );
    $settings->add(
        new admin_setting_configmulticheckboxqtypes(
            'block_sharing_cart/workaround_qtypes',
            get_string('settings:workaround_qtypes', 'block_sharing_cart'),
            get_string('settings:workaround_qtypes_desc', 'block_sharing_cart'),
            array()
        )
    );
}
