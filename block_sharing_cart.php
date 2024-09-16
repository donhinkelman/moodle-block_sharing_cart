<?php

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

class block_sharing_cart extends block_base
{
    public function init(): void
    {
        $this->title = get_string('pluginname', 'block_sharing_cart');
    }

    public function applicable_formats(): array
    {
        return [
            'all' => false,
            'course' => true,
            'site' => true
        ];
    }

    public function has_config(): bool
    {
        return true;
    }

    public function get_content(): object|string
    {
        global $OUTPUT, $USER, $COURSE;

        $base_factory = \block_sharing_cart\app\factory::make();

        if ($this->page->user_is_editing()) {
            $this->page->requires->css('/blocks/sharing_cart/style/style.css');
            $this->page->requires->strings_for_js([
                'copy_item',
                'confirm_copy_item',
                'confirm_copy_item_form_text',
                'into_section',
                'delete_item',
                'delete_items',
                'confirm_delete_item',
                'confirm_delete_items',
                'backup_without_user_data',
                'backup',
                'backup_item',
                'into_sharing_cart',
                'copy_user_data',
                'anonymize_user_data',
                'no_items',
                'run_now',
                'atleast_one_course_module_must_be_included',
                'no_course_modules_in_section',
                'no_course_modules_in_section_description',
                'select_all',
                'deselect_all',
            ], 'block_sharing_cart');
            $this->page->requires->strings_for_js([
                'import',
                'delete',
                'cancel',
            ], 'core');
        }

        if ($this->content !== null) {
            return $this->content;
        }

        if (!$this->page->user_is_editing() || !has_capability(
                'moodle/backup:backupactivity',
                \context_course::instance($COURSE->id)
            )) {
            return $this->content = '';
        }

        $template = new \block_sharing_cart\output\block\content($base_factory, $USER->id, $COURSE->id);

        return $this->content = (object)[
            'text' => $OUTPUT->render($template)
        ];
    }
}
