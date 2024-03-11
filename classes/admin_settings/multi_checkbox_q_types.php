<?php

namespace block_sharing_cart\admin_settings;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

global $CFG;
require_once $CFG->dirroot . '/question/engine/bank.php';

class multi_checkbox_q_types extends multi_checkbox_with_icon {
    public function __construct(string $name, string $visiblename, string $description, $defaultsetting = null) {
        global $OUTPUT;

        $choices = [];
        $icons = [];
        $qtypes = \question_bank::get_all_qtypes();

        // some qtypes do not need workaround
        unset($qtypes['missingtype'], $qtypes['random']);

        $qtypenames = array_map(static function(\question_type $qtype) {
            return $qtype->local_name();
        }, $qtypes);
        foreach (\question_bank::sort_qtype_array($qtypenames) as $qtypename => $label) {
            $choices[$qtypename] = $label;
            $icons[$qtypename] = ' ' . $OUTPUT->pix_icon('icon', '', $qtypes[$qtypename]->plugin_name()) . ' ';
        }
        parent::__construct($name, $visiblename, $description, $defaultsetting, $choices, $icons);
    }
}