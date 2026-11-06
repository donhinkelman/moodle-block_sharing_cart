<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Callbacks and fragment outputs for block_sharing_cart.
 *
 * @package    block_sharing_cart
 * @copyright  moxis
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Hook called after a file is deleted.
 *
 * @param object $file
 */
function block_sharing_cart_after_file_deleted(object $file): void {
    $basefactory = \block_sharing_cart\app\factory::make();

    if ($item = $basefactory->item()->repository()->get_by_file_id($file->id)) {
        $basefactory->item()->repository()->delete_by_id($item->get_id());
    }
}

/**
 * block_sharing_cart_output_fragment_item
 *
 * @param mixed $args
 * @package block_sharing_cart
 */
function block_sharing_cart_output_fragment_item($args) {
    global $OUTPUT, $USER;

    $itemid = clean_param($args['itemid'], PARAM_INT);

    $basefactory = \block_sharing_cart\app\factory::make();
    $item = $basefactory->item()->repository()->get_by_id($itemid);
    if (!$item) {
        return '';
    }

    if ($item->get_user_id() !== (int)$USER->id) {
        return '';
    }

    $template = new \block_sharing_cart\output\block\item($basefactory, $item);

    return fix_utf8($OUTPUT->render($template));
}

/**
 * block_sharing_cart_output_fragment_item_restore_form
 *
 * @param mixed $args
 * @package block_sharing_cart
 */
function block_sharing_cart_output_fragment_item_restore_form($args) {
    global $OUTPUT, $USER;

    $itemid = clean_param($args['itemid'], PARAM_INT);

    // Id of the section. being targeted for an import.
    $clipboardtargetid = clean_param($args['clipboardtargetid'], PARAM_INT);

    $basefactory = \block_sharing_cart\app\factory::make();
    $item = $basefactory->item()->repository()->get_by_id($itemid);
    if (!$item) {
        return '';
    }

    if ($item->get_user_id() !== (int)$USER->id) {
        return '';
    }

    if ($item->is_module() && !$item->is_subsection()) {
        return get_string(
            'confirm_copy_item',
            'block_sharing_cart'
        );
    }

    // Whether the item is inserted as a new section (no merge, so nothing to replace).
    $asnewsection = (bool)clean_param($args['asnewsection'] ?? 0, PARAM_BOOL);

    $template = new \block_sharing_cart\output\modal\import_item_modal_body(
        $basefactory,
        $item,
        $clipboardtargetid,
        $asnewsection
    );

    return fix_utf8($OUTPUT->render($template));
}

/**
 * block_sharing_cart_output_fragment_item_queue
 *
 * @param mixed $args
 * @package block_sharing_cart
 */
function block_sharing_cart_output_fragment_item_queue($args) {
    global $OUTPUT;

    $basefactory = \block_sharing_cart\app\factory::make();
    $template = new \block_sharing_cart\output\block\queue\items($basefactory);

    return fix_utf8($OUTPUT->render($template));
}

/**
 * Plugin file handler to allow sharing cart backups to be downloaded.
 *
 * @param object $course the course object
 * @param object $cm the course module object
 * @param context $context the newmodule's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return void|false
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 * @throws require_login_exception
 * @package block_sharing_cart
 */
function block_sharing_cart_pluginfile(
    $course,
    $cm,
    $context,
    $filearea,
    $args,
    $forcedownload,
    array $options = []
) {
    require_login($course, false, $cm);
    if (!has_all_capabilities(['moodle/backup:backupactivity', 'moodle/restore:restoreactivity'], $context)) {
        return false;
    }

    if ($filearea !== 'backup') {
        return false;
    }

    $factory = \block_sharing_cart\app\factory::make();
    $itemid = array_shift($args);

    $item = $factory->item()->repository()->get_by_id((int)$itemid);
    if (!$item) {
        return false;
    }

    $file = $factory->item()->repository()->get_stored_file_by_item($item);
    if ($file) {
        send_stored_file($file, 0, 0, $forcedownload, $options);
        return true;
    }

    send_file_not_found();
}
