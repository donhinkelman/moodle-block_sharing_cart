<?php

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class block_sharing_cart extends block_base {
    public function init(): void {
        $this->title = get_string('pluginname', 'block_sharing_cart');
    }

    public function applicable_formats(): array {
        return [
                'all' => false,
                'course' => true,
                'site' => true
        ];
    }

    public function has_config(): bool {
        return true;
    }

    public function get_content(): object|string {
        global $OUTPUT, $USER;

        $base_factory = \block_sharing_cart\app\factory::make();

        $this->page->requires->css('/blocks/sharing_cart/style/style.css');

        if ($this->content !== null) {
            return $this->content;
        }

        if (!$this->page->user_is_editing() || !has_capability('moodle/backup:backupactivity', $this->context)) {
            return $this->content = '';
        }

        $template = new \block_sharing_cart\output\block\content($base_factory, $USER->id);

        return $this->content = (object)[
            'text' => $OUTPUT->render($template)
        ];
    }
}
