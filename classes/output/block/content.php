<?php

namespace block_sharing_cart\output\block;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use block_sharing_cart\app\factory as base_factory;
use block_sharing_cart\app\item\entity;

class content implements \renderable, \core\output\named_templatable
{
    private base_factory $base_factory;
    private int $user_id;

    public function __construct(base_factory $base_factory, int $user_id)
    {
        $this->base_factory = $base_factory;
        $this->user_id = $user_id;
    }

    public function get_template_name(\renderer_base $renderer): string
    {
        return 'block_sharing_cart/block/content';
    }

    private function export_items_for_template(): array
    {
        global $USER, $DB;

        $not_running_backup_tasks = $DB->get_records('task_adhoc', [
            'userid' => $USER->id,
            'classname' => "\\block_sharing_cart\\task\\asynchronous_backup_task",
            'timestarted' => null
        ], fields: "id, JSON_EXTRACT(customdata, '$.item.id') as item_id");
        $not_running_backup_tasks = array_combine(
            array_column($not_running_backup_tasks, 'item_id'),
            $not_running_backup_tasks
        );

        $all_item_contexts = $this->base_factory->item()->repository()->get_by_user_id($this->user_id)->map(
            static function (entity $item) use ($not_running_backup_tasks) {
                return item::export_item_for_template($item, $not_running_backup_tasks);
            }
        );

        $root_item_contexts = $all_item_contexts->filter(static function (object $item_context) {
            return $item_context->is_root;
        });

        $root_item_contexts = $root_item_contexts->map(function (object $root_item_context) use ($all_item_contexts) {
            $root_item_context->children = item::get_item_children($root_item_context, $all_item_contexts);
            return $root_item_context;
        });

        return $root_item_contexts->to_array(true);
    }

    public function export_for_template(\renderer_base $output): array
    {
        return [
            'items' => $this->export_items_for_template()
        ];
    }
}