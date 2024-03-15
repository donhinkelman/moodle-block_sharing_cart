<?php

function block_sharing_cart_after_file_deleted($file): void
{
    // TODO: Implement block_sharing_cart_after_file_deleted
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

    return $OUTPUT->render($template);
}