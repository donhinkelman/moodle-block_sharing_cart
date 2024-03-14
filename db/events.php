<?php

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

$observers = [
    [
        'eventname' => \core\event\user_deleted::class,
        'callback' => '\block_sharing_cart\observers::user_deleted', // TODO: Implement user_deleted callback
    ],
];
