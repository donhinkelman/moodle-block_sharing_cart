<?php // $Id: access.php 941 2013-03-28 10:37:21Z malu $

defined('MOODLE_INTERNAL') || die;

$capabilities = array(
    'block/sharing_cart:addinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ),
        'clonepermissionsfrom' => 'moodle/site:manageblocks',
    ),
);
