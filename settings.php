<?php

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use block_sharing_cart\admin_settings\multi_checkbox_mod_types;
use block_sharing_cart\admin_settings\multi_checkbox_q_types;

/**
 * @global admin_root $ADMIN
 * @global admin_settingpage $settings
 */
if ($ADMIN->fulltree) {
    $settings->add(
        new admin_setting_configselect(
            'block_sharing_cart/backup_mode',
            get_string('settings:backup_mode', 'block_sharing_cart'),
            get_string('settings:backup_mode_desc', 'block_sharing_cart'),
            'immediate',
            [
                'immediate' => get_string('settings:backup_restore_mode_immediate', 'block_sharing_cart'),
                'async' => get_string('settings:backup_restore_mode_async', 'block_sharing_cart'),
            ]
        )
    );
    $settings->add(
        new admin_setting_configselect(
            'block_sharing_cart/restore_mode',
            get_string('settings:restore_mode', 'block_sharing_cart'),
            get_string('settings:restore_mode_desc', 'block_sharing_cart'),
            'immediate',
            [
                'immediate' => get_string('settings:backup_restore_mode_immediate', 'block_sharing_cart'),
                'async' => get_string('settings:backup_restore_mode_async', 'block_sharing_cart'),
            ]
        )
    );
    $settings->add(
        new multi_checkbox_mod_types(
            'block_sharing_cart/userdata_copyable_modtypes',
            get_string('settings:userdata_copyable_modtypes', 'block_sharing_cart'),
            get_string('settings:userdata_copyable_modtypes_desc', 'block_sharing_cart'),
            ['data' => 1, 'forum' => 1, 'glossary' => 1, 'wiki' => 1]
        )
    );
    $settings->add(
        new multi_checkbox_q_types(
            'block_sharing_cart/workaround_qtypes',
            get_string('settings:workaround_qtypes', 'block_sharing_cart'),
            get_string('settings:workaround_qtypes_desc', 'block_sharing_cart'),
            []
        )
    );
    $settings->add(
        new admin_setting_configselect(
            'block_sharing_cart/add_to_sharing_cart',
            get_string('settings:add_to_sharing_cart', 'block_sharing_cart'),
            get_string('settings:add_to_sharing_cart_desc', 'block_sharing_cart'),
            'click_to_add',
            [
                'drag_and_drop' => get_string('settings:drag_and_drop', 'block_sharing_cart'),
                'click_to_add' => get_string('settings:click_to_add', 'block_sharing_cart')
            ]
        )
    );
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
}
