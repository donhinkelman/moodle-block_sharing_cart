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
 *  @package    block_sharing_cart
 *  @copyright  2017 (C) VERSION2, INC.
 *  @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
