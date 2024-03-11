<?php

namespace block_sharing_cart\output\block;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

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

    public function export_items_for_template(): array {
        $all_items = $this->base_factory->items()->repository()->get_by_user_id($this->user_id);
        $all_items->map(function (object $item) {
            $item->is_root = $item->parent_item_id === null;
            $item->icon = $this->get_item_icon($item);
        });

        $root_items = $all_items->filter(static function (object $item) {
            return $item->is_root;
        });

        $get_children = static function (object $item) use (&$get_children, $all_items) {
            $children = $all_items->filter(static function (object $child_item) use ($item) {
                return $child_item->parent_item_id === $item->id;
            });
            foreach ($children as $child) {
                $child->children = $get_children($child);
            }
            return $children;
        };

        foreach ($root_items as $root_item) {
            $root_item->children = $get_children($root_item);
        }

        return $root_items->to_array(true);
    }

    public function export_for_template(\renderer_base $output): array {
        return [
            'items' => $this->export_items_for_template()
        ];
    }

    private function get_item_icon(object $item): string {
        global $OUTPUT;

        if ($item->type === 'course') {
            return $OUTPUT->image_url('icon', 'course');
        }

        if ($item->type === 'section') {
            return $OUTPUT->image_url('icon', 'section');
        }

        return $OUTPUT->image_url('icon', $item->type);
    }
}