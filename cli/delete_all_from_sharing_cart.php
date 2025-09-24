<?php

const CLI_SCRIPT = true;

require __DIR__ . '/../../../config.php';

global $CFG, $DB;
require_once($CFG->libdir . "/clilib.php");

// Supported options.
$long = ['execute' => false, 'help' => false];
$short = ['e' => 'execute', 'h' => 'help'];

// CLI options.
[$options, $unrecognized] = cli_get_params($long, $short);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = <<<EOT
Deletes all items from sharing cart.

  This script deletes all items from sharing cart for all users.

Options:
  -h, --help    Print out this help.
  -e, --execute Run the deletion
                If not specified only check and report problems to STDERR.

Usage:
  - Only report:    \$ sudo -u www-data /usr/bin/php blocks/sharing_cart/cli/delete_all_from_sharing_cart.php
  - Report and fix: \$ sudo -u www-data /usr/bin/php blocks/sharing_cart/cli/delete_all_from_sharing_cart.php -e
EOT;

    cli_writeln($help);
    die;
}

$is_dry_run = $options['execute'] === false;

cli_heading('Checking amount of items in sharing cart across all users');
$base_factory = \block_sharing_cart\app\factory::make();

$item_count = $base_factory->item()->repository()->get_count();
cli_writeln("Found {$item_count} items in the sharing cart.");

if ($is_dry_run) {
    die();
}

if ($item_count === 0) {
    cli_writeln("Nothing to delete. Aborting...");
    die();
}

cli_heading('Proceeding with deletion of all items in sharing cart across all users');

$failed_deletions = 0;

$records = $DB->get_recordset($base_factory->item()->repository()->get_table(), fields: 'id');
foreach ($records as $record) {
    try {
        cli_writeln("Deleting item with id {$record->id} from sharing cart...");
        $base_factory->item()->repository()->delete_by_id($record->id);
    } catch (\Exception $e) {
        cli_writeln(
            "Failed to delete item with id {$record->id} from sharing cart. Error: {$e->getMessage()} Trace: {$e->getTraceAsString()}"
        );
        $failed_deletions++;
    }
}
$records->close();

cli_writeln("Deletion process completed. {$failed_deletions} items couldn't be deleted.");
