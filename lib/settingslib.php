<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *  Sharing Cart
 *
 * @package    block_sharing_cart
 * @copyright  2017 (C) VERSION2, INC.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once __DIR__ . '/../../../question/engine/bank.php';

/**
 * Multiple checkboxes with icons for each label
 */
class admin_setting_configmulticheckboxwithicon extends admin_setting_configmulticheckbox {
    /** @var array Array of icons value=>icon */
    protected $icons;

    /**
     * Constructor: uses parent::__construct
     *
     * @param string $name unique ascii name, either 'mysetting' for settings that in config, or 'myplugin/mysetting' for ones in
     *         config_plugins.
     * @param string $visiblename localised
     * @param string $description long localised info
     * @param array $defaultsetting array of selected
     * @param array $choices array of $value=>$label for each checkbox
     * @param array $icons array of $value=>$icon for each checkbox
     */
    public function __construct($name, $visiblename, $description, $defaultsetting, array $choices, array $icons) {
        $this->icons = $icons;
        parent::__construct($name, $visiblename, $description, $defaultsetting, $choices);
    }

    /**
     * Returns XHTML field(s) as required by choices
     *
     * Relies on data being an array should data ever be another valid vartype with
     * acceptable value this may cause a warning/error
     * if (!is_array($data)) would fix the problem
     *
     * @param array $data An array of checked values
     * @param string $query
     * @return string XHTML field
     * @todo Add vartype handling to ensure $data is an array
     *
     */
    public function output_html($data, $query = '') {
        if (!$this->load_choices() or empty($this->choices)) {
            return '';
        }
        $default = $this->get_defaultsetting();
        if (is_null($default)) {
            $default = array();
        }
        if (is_null($data)) {
            $data = array();
        }
        $options = array();
        $defaults = array();
        foreach ($this->choices as $key => $description) {
            if (!empty($data[$key])) {
                $checked = 'checked="checked"';
            } else {
                $checked = '';
            }
            if (!empty($default[$key])) {
                $defaults[] = $description;
            }

            //            $options[] = '<input type="checkbox" id="'.$this->get_id().'_'.$key.'" name="'.$this->get_full_name().'['.$key.']" value="1" '.$checked.' />'
            //                .'<label for="'.$this->get_id().'_'.$key.'">'.highlightfast($query, $description).'</label>';
            $options[] = '<input type="checkbox" id="' . $this->get_id() . '_' . $key . '" name="' . $this->get_full_name() . '[' .
                    $key . ']" value="1" ' . $checked . ' />'
                    . '<label for="' . $this->get_id() . '_' . $key . '">' . $this->icons[$key] .
                    highlightfast($query, $description) . '</label>';
        }

        if (is_null($default)) {
            $defaultinfo = null;
        } else if (!empty($defaults)) {
            $defaultinfo = implode(', ', $defaults);
        } else {
            $defaultinfo = get_string('none');
        }

        $return = '<div class="form-multicheckbox">';
        $return .= '<input type="hidden" name="' . $this->get_full_name() .
                '[xxxxx]" value="1" />'; // something must be submitted even if nothing selected
        if ($options) {
            $return .= '<ul>';
            foreach ($options as $option) {
                $return .= '<li>' . $option . '</li>';
            }
            $return .= '</ul>';
        }
        $return .= '</div>';

        return format_admin_setting($this, $this->visiblename, $return, $this->description, false, '', $defaultinfo, $query);
    }
}

/**
 * Multiple checkboxes for module types
 */
class admin_setting_configmulticheckboxmodtypes extends admin_setting_configmulticheckboxwithicon {
    /**
     * @param string $name
     * @param string $visiblename
     * @param string $description
     * @param array $defaultsetting
     * @global moodle_database $DB
     * @global core_renderer $OUTPUT
     */
    public function __construct($name, $visiblename, $description, $defaultsetting = null) {
        global $DB, $OUTPUT;
        $choices = array();
        $icons = array();
        foreach ($DB->get_records('modules', array(), 'name ASC') as $module) {
            $choices[$module->name] = get_string('modulename', $module->name);
            $icons[$module->name] = ' ' . $OUTPUT->pix_icon('icon', '', $module->name, array('class' => 'icon'));
        }
        parent::__construct($name, $visiblename, $description, $defaultsetting, $choices, $icons);
    }
}

/**
 * Multiple checkboxes for question types
 */
class admin_setting_configmulticheckboxqtypes extends admin_setting_configmulticheckboxwithicon {
    /**
     * @param string $name
     * @param string $visiblename
     * @param string $description
     * @param array $defaultsetting
     * @global core_renderer $OUTPUT
     */
    public function __construct($name, $visiblename, $description, $defaultsetting = null) {
        global $OUTPUT;
        $choices = array();
        $icons = array();
        $qtypes = question_bank::get_all_qtypes();
        // some qtypes do not need workaround
        unset($qtypes['missingtype']);
        unset($qtypes['random']);
        // question_bank::sort_qtype_array() expects array(name => local_name)
        $qtypenames = array_map(function($qtype) {
            return $qtype->local_name();
        }, $qtypes);
        foreach (question_bank::sort_qtype_array($qtypenames) as $qtypename => $label) {
            $choices[$qtypename] = $label;
            $icons[$qtypename] = ' ' . $OUTPUT->pix_icon('icon', '', $qtypes[$qtypename]->plugin_name()) . ' ';
        }
        parent::__construct($name, $visiblename, $description, $defaultsetting, $choices, $icons);
    }
}
