<?php

namespace block_sharing_cart\admin_settings;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class multi_checkbox_mod_types extends multi_checkbox_with_icon {
    public function __construct($name, $visiblename, $description, $defaultsetting = null) {
        global $DB, $OUTPUT;

        $choices = [];
        $icons = [];

        foreach ($DB->get_records('modules', [], 'name ASC') as $module) {
            $choices[$module->name] = get_string('modulename', $module->name);
            $icons[$module->name] = ' ' . $OUTPUT->pix_icon('icon', '', $module->name, ['class' => 'icon']);
        }

        parent::__construct($name, $visiblename, $description, $defaultsetting, $choices, $icons);
    }
}