<?php

namespace block_sharing_cart\admin_settings;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use block_sharing_cart\app\factory as base_factory;

class multi_checkbox_mod_types extends multi_checkbox_with_icon
{
    public function __construct(string $name, string $visiblename, string $description, $defaultsetting = null)
    {
        $base_factory = base_factory::make();
        $db = $base_factory->moodle()->db();
        $output = $base_factory->moodle()->output();

        $choices = [];
        $icons = [];

        foreach ($db->get_records('modules', [], 'name ASC') as $module) {
            $choices[$module->name] = get_string('modulename', $module->name);
            $icons[$module->name] = ' ' . $output->pix_icon('icon', '', $module->name, ['class' => 'icon']);
        }

        parent::__construct($name, $visiblename, $description, $defaultsetting, $choices, $icons);
    }
}