<?php

namespace block_sharing_cart\output\block\queue;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd


use block_sharing_cart\app\factory as base_factory;
use core\context\system;

class items implements \renderable, \core\output\named_templatable
{
    private base_factory $base_factory;

    public function __construct(
        base_factory $base_factory
    )
    {
        $this->base_factory = $base_factory;
    }

    public function get_template_name(\renderer_base $renderer): string
    {
        return 'block_sharing_cart/block/queue/items';
    }

    public function export_for_template(\renderer_base $output): array
    {
        global $USER, $DB, $OUTPUT, $COURSE;

        $queue_items = [];

        $records = $DB->get_records('task_adhoc', [
            'userid' => $USER->id,
            'classname' => "\\block_sharing_cart\\task\\asynchronous_restore_task"
        ]);
        foreach ($records as $record) {
            $custom_data = json_decode($record->customdata);

            $backup_settings = $custom_data->backup_settings ?? null;

            $item = $custom_data->item ?? null;
            $course_id = $custom_data->course_id ?? null;

            if ($course_id !== (int)$COURSE->id) {
                continue;
            }

            $is_running = $record->timestarted !== null;
            $is_failed = $record->faildelay > 0;
            $has_waited_5_seconds = time() - $record->timecreated > 5;

            $queue_items[] = [
                'id' => $record->id,
                'name' => strlen($item->name) > 50 ? substr($item->name, 0, 50) . '...' : $item->name,
                'is_section' => $item->type === \block_sharing_cart\app\item\entity::TYPE_SECTION,
                'is_module' => $item->type !== \block_sharing_cart\app\item\entity::TYPE_SECTION,
                'mod_icon' => $item->type !== \block_sharing_cart\app\item\entity::TYPE_SECTION ? $OUTPUT->image_url(
                    'icon',
                    $item->type
                ) : null,
                'to_section_id' => $backup_settings->move_to_section_id ?? null,
                'is_running' => $is_running,
                'is_failed' => $is_failed,
                'show_run_now' => $this->allow_to_run_now() && !$is_running && !$is_failed && $has_waited_5_seconds,
            ];
        }

        return [
            'queue_items' => array_values($queue_items)
        ];
    }

    private function allow_to_run_now(): bool
    {
        global $USER;

        return has_capability(
            'block/sharing_cart:manual_run_task',
            \core\context\system::instance(),
            $USER
        );
    }
}
