<?php

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

$capabilities = [
        'block/sharing_cart:addinstance' => [
                'captype' => 'write',
                'contextlevel' => CONTEXT_BLOCK,
                'archetypes' => [
                        'editingteacher' => CAP_ALLOW,
                        'manager' => CAP_ALLOW,
                ],
                'clonepermissionsfrom' => 'moodle/site:manageblocks',
        ],
];
