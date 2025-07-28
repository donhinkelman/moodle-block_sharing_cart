<?php

namespace block_sharing_cart\output\block;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use block_sharing_cart\app\collection;
use block_sharing_cart\app\factory;
use block_sharing_cart\app\factory as base_factory;
use block_sharing_cart\app\item\entity;

class item implements \renderable, \core\output\named_templatable
{
    private base_factory $base_factory;
    private entity $item;

    public function __construct(base_factory $base_factory, entity $item)
    {
        $this->base_factory = $base_factory;
        $this->item = $item;
    }

    public function get_template_name(\renderer_base $renderer): string
    {
        return 'block_sharing_cart/block/item';
    }

    public static function export_item_for_template(entity $item, array $backup_tasks): object
    {
        global $USER, $PAGE;

        $allow_to_run_now = has_capability('block/sharing_cart:manual_run_task', \core\context\system::instance(), $USER);

        $base_factory = base_factory::make();
        $db = $base_factory->moodle()->db();

        $backup_task = $backup_tasks[$item->get_id()] ?? null;
        $is_running = $backup_task && $backup_task->timestarted !== null;
        $is_failed = $backup_task && $backup_task->faildelay > 0;
        $has_waited_5_seconds = $backup_task && time() - $backup_task->timecreated > 5;

        if ($is_failed || ($backup_task === null && $item->get_status() !== entity::STATUS_BACKEDUP)) {
            $item->set_status(entity::STATUS_BACKUP_FAILED);
            $base_factory->item()->repository()->update($item);
        }

        $item_context = (object)$item->to_array();

        $item_context->is_root = $item->get_parent_item_id() === null;

        $item_context->is_section = $item->is_section();

        $item_context->is_module = $item->is_module();
        $item_context->mod_icon = self::get_mod_icon($item);
        $item_context->can_copy_to_course = has_capability('moodle/restore:restoreactivity', $PAGE->context, $USER);

        $item_context->show_run_now = $allow_to_run_now && !$is_running && !$is_failed && $has_waited_5_seconds;
        $item_context->task_id = $item_context->show_run_now ? $backup_task->id : null;
        $item_context->has_file_id = $item->get_file_id() !== null || factory::make()->item()->repository(
            )->get_parent_item_recursively_by_item($item)->get_file_id() !== null;
        $item_context->status_finished = $item->get_status() === entity::STATUS_BACKEDUP;
        $item_context->status_awaiting = $item->get_status() === entity::STATUS_AWAITING_BACKUP;
        $item_context->status_failed = $item->get_status() === entity::STATUS_BACKUP_FAILED;
        $item_context->is_current_version = $item->get_version() === entity::CURRENT_BACKUP_VERSION;

        $item_context->module_is_disabled_on_site = $item->is_module() === true && $db->get_record('modules', [
                'name' => str_replace('mod_', '', $item->get_type()),
                'visible' => false
            ]);

        return $item_context;
    }

    public static function get_item_children(object $item_context, collection $all_item_contexts): collection
    {
        $children = $all_item_contexts->filter(static function (object $child_item) use ($item_context) {
            return $child_item->parent_item_id === $item_context->id;
        });
        $children->map(function (object $child) use ($all_item_contexts) {
            $child->children = self::get_item_children($child, $all_item_contexts);
        });

        return $children;
    }

    public static function get_mod_icon(entity $item): ?string
    {
        global $OUTPUT;

        if (!$item->is_module()) {
            return null;
        }

        return $OUTPUT->image_url('icon', $item->get_type());
    }

    public function export_for_template(\renderer_base $output): array
    {
        global $USER, $DB;

        $backup_tasks = $DB->get_records('task_adhoc', [
            'userid' => $USER->id,
            'classname' => "\\block_sharing_cart\\task\\asynchronous_backup_task",
        ]);
        array_walk($backup_tasks, static function (object $task) {
            $task->item_id = json_decode($task->customdata)?->item?->id;
            unset($task->customdata);
        });
        $backup_tasks = array_combine(
            array_column($backup_tasks, 'item_id'),
            $backup_tasks
        );

        $all_item_contexts = $this->base_factory->item()->repository()->get_recursively_by_parent_id(
            $this->item->get_id()
        )->map(
            function (entity $item) use ($backup_tasks) {
                return $this->export_item_for_template($item, $backup_tasks);
            }
        );

        /**
         * @var object $root_item_context
         */
        $root_item_context = $all_item_contexts->filter(function (object $item_context) {
            return $item_context->id === $this->item->get_id();
        })->first();

        $root_item_context->children = self::get_item_children($root_item_context, $all_item_contexts);

        return (array)$root_item_context;
    }
}
