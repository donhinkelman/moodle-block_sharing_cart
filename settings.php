<?php

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

/**
 * @global admin_root $ADMIN
 * @global admin_settingpage $settings
 */
if ($ADMIN->fulltree) {
    // TODO: Implement the following??
    /*
    $settings->add(
        new \block_sharing_cart\admin_settings\multi_checkbox_q_types(
            'block_sharing_cart/workaround_qtypes',
            get_string('settings:workaround_qtypes', 'block_sharing_cart'),
            get_string('settings:workaround_qtypes_desc', 'block_sharing_cart'),
            []
        )
    );
    */

    // TODO: Implement the following??
    /*
    $settings->add(
        new admin_setting_configcheckbox(
            'block_sharing_cart/show_copy_section_in_block',
            get_string('settings:show_copy_section_in_block', 'block_sharing_cart'),
            get_string('settings:show_copy_section_in_block_desc', 'block_sharing_cart'),
            1,
        )
    );
    $settings->add(
        new admin_setting_configcheckbox(
            'block_sharing_cart/show_copy_activity_in_block',
            get_string('settings:show_copy_activity_in_block', 'block_sharing_cart'),
            get_string('settings:show_copy_activity_in_block_desc', 'block_sharing_cart'),
            1,
        )
    );
    */
}
