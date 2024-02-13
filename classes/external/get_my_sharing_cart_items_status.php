<?php

namespace block_sharing_cart\external;


use block_sharing_cart\repositories\backup_repository;
use external_api;
use external_description;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

global $CFG;
require_once $CFG->libdir . '/externallib.php';

class get_my_sharing_cart_items_status extends external_api
{
    public static function execute_parameters(): external_description
    {
        return new external_function_parameters([
            'sharing_cart_ids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Sharing Cart ID'),
                'Sharing Cart IDs'
            ),
        ]);
    }

    public static function execute_returns(): external_description
    {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Sharing Cart ID'),
                'is_ready' => new external_value(PARAM_INT, 'Is Ready')
            ]),
            'Backup Status'
        );
    }

    public static function execute(array $sharing_cart_ids): array
    {
        require_login(
            null,
            false,
            null,
            true,
            true
        );

        self::validate_parameters(self::execute_parameters(), [
            'sharing_cart_ids' => $sharing_cart_ids
        ]);

        return backup_repository::create()
            ->get_my_backup_status($sharing_cart_ids);
    }
}
