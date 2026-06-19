<?php

function block_sharing_cart_after_file_deleted(object $file): void
{
    $base_factory = \block_sharing_cart\app\factory::make();

    if ($item = $base_factory->item()->repository()->get_by_file_id($file->id)) {
        $base_factory->item()->repository()->delete_by_id($item->get_id());
    }
}

function block_sharing_cart_output_fragment_item($args)
{
    global $OUTPUT, $USER;

    $item_id = clean_param($args['item_id'], PARAM_INT);

    $base_factory = \block_sharing_cart\app\factory::make();
    $item = $base_factory->item()->repository()->get_by_id($item_id);
    if (!$item) {
        return '';
    }

    if ($item->get_user_id() !== (int)$USER->id) {
        return '';
    }

    $template = new \block_sharing_cart\output\block\item($base_factory, $item);

    return fix_utf8($OUTPUT->render($template));
}

function block_sharing_cart_output_fragment_item_restore_form($args)
{
    global $OUTPUT, $USER;

    $item_id = clean_param($args['item_id'], PARAM_INT);

    $base_factory = \block_sharing_cart\app\factory::make();
    $item = $base_factory->item()->repository()->get_by_id($item_id);
    if (!$item) {
        return '';
    }

    if ($item->get_user_id() !== (int)$USER->id) {
        return '';
    }

    if ($item->is_module()) {
        return get_string(
            'confirm_copy_item',
            'block_sharing_cart'
        );
    }

    $template = new \block_sharing_cart\output\modal\import_item_modal_body($base_factory, $item);

    return fix_utf8($OUTPUT->render($template));
}

function block_sharing_cart_output_fragment_item_queue($args)
{
    global $OUTPUT;

    $base_factory = \block_sharing_cart\app\factory::make();
    $template = new \block_sharing_cart\output\block\queue\items($base_factory);

    return fix_utf8($OUTPUT->render($template));
}

/**
 * Plugin file handler to allow sharing cart backups to be downloaded.
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
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
    context $context,
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
