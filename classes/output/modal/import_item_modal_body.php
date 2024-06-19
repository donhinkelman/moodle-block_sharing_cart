<?php

namespace block_sharing_cart\output\modal;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use block_sharing_cart\app\factory as base_factory;
use block_sharing_cart\app\item\entity;
use block_sharing_cart\output\block\item;

class import_item_modal_body implements \renderable, \core\output\named_templatable
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
        return 'block_sharing_cart/modal/import_item_modal_body';
    }

    public function export_for_template(\renderer_base $output): array
    {
        $sections = array_values(
            $this->base_factory->backup()->handler()->get_backup_item_tree(
                $this->base_factory->item()->repository()->get_stored_file_by_item($this->item)
            )
        );
        foreach ($sections as $section) {
            foreach ($section->activities as $activity) {
                $activity->title = format_string($activity->title);
                $activity->title = strlen($activity->title) > 50 ? substr(
                        $activity->title,
                        0,
                        50
                    ) . '...' : $activity->title;
                $activity->id = $activity->moduleid;
                $activity->type = 'coursemodule';
                $activity->mod_icon = $output->image_url('icon', "mod_{$activity->modulename}");
                $activity->course_modules = [];
            }

            $section->title = format_string($section->title);
            $section->title = strlen($section->title) > 50 ? trim(
                    substr($section->title, 0, 50)
                ) . '...' : $section->title;
            $section->id = $section->sectionid;
            $section->type = 'section';
            $section->mod_icon = null;
            $section->course_modules = array_values($section->activities);
            unset($section->sectionid, $section->activities);
        }

        return [
            'sections' => $sections
        ];
    }
}
