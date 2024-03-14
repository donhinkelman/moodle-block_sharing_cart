<?php

namespace block_sharing_cart\output\block;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

use block_sharing_cart\app\collection;
use block_sharing_cart\app\factory as base_factory;

class content implements \renderable, \core\output\named_templatable {
    private base_factory $base_factory;
    private int $user_id;

    public function __construct(base_factory $base_factory, int $user_id) {
        $this->base_factory = $base_factory;
        $this->user_id = $user_id;
    }

    public function get_template_name(\renderer_base $renderer): string {
        return 'block_sharing_cart/block/content';
    }

    private function export_item_for_template(object $item): object {
        $item->is_root = $item->parent_item_id === null;

        $item->is_course = $item->type === 'course';
        $item->is_section = $item->type === 'section';

        $item->is_module = !$item->is_course && !$item->is_section;
        $item->mod_icon = $item->is_module ? $this->get_mod_icon($item) : null;

        return $item;
    }

    private function export_items_for_template(): array {
        $all_items = $this->base_factory->item()->repository()->get_by_user_id($this->user_id);
        $all_items->map(fn($item) => $this->export_item_for_template($item));

        $root_items = $all_items->filter(static function (object $item) {
            return $item->is_root;
        });
        $root_items->map(function (object $root_item) use ($all_items) {
            $root_item->children = $this->get_item_children($root_item, $all_items);
        });

        return $root_items->to_array(true);
    }

    private function get_item_children(object $item, collection $all_items): collection {
        $children = $all_items->filter(static function (object $child_item) use ($item) {
            return $child_item->parent_item_id === $item->id;
        });
        $children->map(function (object $child) use ($all_items) {
            $child->children = $this->get_item_children($child, $all_items);
        });

        return $children;
    }

    private function get_mod_icon(object $item): string {
        global $OUTPUT;

        return $OUTPUT->image_url('icon', $item->type);
    }

    public function export_for_template(\renderer_base $output): array {
        return [
            'items' => $this->export_items_for_template()
        ];
    }
}