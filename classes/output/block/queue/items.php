<?php

namespace block_sharing_cart\output\block\queue;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use block_sharing_cart\app\factory as base_factory;

class items implements \renderable, \core\output\named_templatable
{
    private base_factory $base_factory;

    public function __construct(base_factory $base_factory)
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
                'is_running' => $record->timestarted !== null,
                'is_failed' => $record->faildelay > 0
            ];
        }

        return [
            'queue_items' => array_values($queue_items)
        ];
    }
}