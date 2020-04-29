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
 *  Sharing Cart block
 *
 * @package    block_sharing_cart
 * @copyright  2017 (C) VERSION2, INC.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_sharing_cart\controller;
use block_sharing_cart\section;

defined('MOODLE_INTERNAL') || die();

/**
 * PTODO / TODO: See list below
 * - Namespaces are wrong (sharing_cart => block_sharing_cart) for autoload
 * - JS must be AMD modules
 * - Remove require statements and use autoload
 * - Create new branches and implement mustache / renderers (some already exists)
 * - Fix version variable in contructor
 * - Move all exceptions to classes/exceptions and create descriptive classes
 * - Implement Core/Str to load language strings
 *
 * Class block_sharing_cart
 */
class block_sharing_cart extends block_base {
    public function init() {
        $this->title = get_string('pluginname', __CLASS__);
    }

    public function applicable_formats() {
        return array(
                'all' => false,
                'course' => true,
                'site' => true
        );
    }

    public function instance_can_be_docked() {
        return false; // AJAX won't work with Dock
    }

    public function has_config() {
        return true;
    }

	/**
	 *  Get the block content
	 *
	 * @return object|string
	 *
	 * @throws coding_exception
	 * @global object $USER
	 */
    public function get_content() {
        global $USER, $COURSE, $PAGE;

        $section_id = optional_param('section', 0, PARAM_INT);

        $context = context_course::instance($this->page->course->id);

        if ($this->content !== null) {
            return $this->content;
        }

        if (!$this->page->user_is_editing() || !has_capability('moodle/backup:backupactivity', $context)) {
            return $this->content = '';
        }

        $controller = new controller();
        $html = $controller->render_tree($USER->id);

        // Fetching all sections for current course.
        $sectionsHandler = new section();
        $sections = $sectionsHandler->all($COURSE->id);

        /* Place the <noscript> tag to give out an error message if JavaScript is not enabled in the browser.
         * Adding bootstrap classes to show colored info in bootstrap based themes. */
        $noscript = html_writer::tag('noscript',
                html_writer::tag('div', get_string('requirejs', __CLASS__), array('class' => 'error alert alert-danger'))
        );
        $html = $noscript . $html;

        $this->page->requires->css('/blocks/sharing_cart/styles.css');
        if ($this->is_special_version()) {
            $this->page->requires->css('/blocks/sharing_cart/custom.css');
        }
        $this->page->requires->jquery();
        $this->page->requires->js('/blocks/sharing_cart/script.js');
        $this->page->requires->strings_for_js(
                array('yes', 'no', 'ok', 'cancel', 'error', 'edit', 'move', 'delete', 'movehere'),
                'moodle'
        );
        $this->page->requires->strings_for_js(
                array('copyhere', 'notarget', 'backup', 'restore', 'movedir', 'clipboard',
                        'confirm_backup', 'confirm_backup_section', 'confirm_userdata',
                        'confirm_userdata', 'confirm_delete', 'clicktomove', 'folder_string',
                        'activity_string', 'delete_folder', 'modal_checkbox',
                        'modal_confirm_backup', 'modal_confirm_delete'
                ),
                __CLASS__
        );

        $footer = '';
        $page_format = $PAGE->course->format;

        // Check if page format is not site. (Location)
        if ($page_format !== 'site'){
            // Creating with sections that are not empty.
            $sections_dropdown = '';
            foreach ($sections as $section) {
                $sectionname = $section->name;
                if ($section->sequence !== '') {
                    if (!$section->name) {
                        $sectionname = get_string('sectionname', "format_$COURSE->format") . ' ' . $section->section;
                    }
                    $sections_dropdown .= "
                    <option data-section-id='$section->id' data-section-number='$section->section' data-course-id='$section->course' data-section-name='$sectionname'>
                        $sectionname
                    </option>
                ";
                }
            }

            $footer = "
		    <form id=\"copy-section-form\" data-in-section=\"" . ($section_id ? 1 : 0) . "\">
		        <select class='custom-select section-dropdown'>
		            $sections_dropdown
		        </select>
		        <a href='javascript:void(0)' class='copy_section' title='get_string('sectionname', \"format_$COURSE->format\") . ' ' . $section->section)'>
		            <input id='copy' type='button' class='btn btn-primary' value='" . get_string('copy_section', __CLASS__) . "'>
		        </a>
            </form>
		";
        }
        $footer .= '
                    <div style="display:none;">
                    <div class="header-commands">' . $this->get_header() . '</div>
                    </div>
                ';
        return $this->content = (object) array('text' => $html, 'footer' => $footer);
    }

    /**
     *  Get the block header
     *
     * @return string
     */
    private function get_header() {
        // link to bulkdelete
        $alt = get_string('bulkdelete', __CLASS__);
        $url = new moodle_url('/blocks/sharing_cart/bulkdelete.php', array('course' => $this->page->course->id));

        return $this->get_bulk_delete($alt, $url) . $this->get_help_icon();
    }

    /**
     *  Get bulk delete
     *
     * @param string $alt
     * @param moodle_url $url
     * @return string
     */
    private function get_bulk_delete($alt, $url) {
        $bulkdelete = '
		        <a class="editing_bulkdelete" title="' . s($alt) . '" href="' . s($url) . '">
		        <i class="bulk-icon icon fa fa-times-circle" alt="' . s($alt) . '" /></i>
		        </a>
		        ';

        return $bulkdelete;
    }

    /**
     *  Get help icon
     *
     * @return string
     */
    private function get_help_icon() {
        global $OUTPUT;
        $helpicon = $OUTPUT->help_icon('sharing_cart', __CLASS__);
        $helpicon = str_replace('class="', 'class="help-icon ', $helpicon);
        return $helpicon;
    }

    /**
     *  Check Moodle 3.2 or later
     *
     * @return boolean
     */
    private function is_special_version() {
        return moodle_major_version() >= 3.2;
    }

    /**
     *  Get the block content for no-AJAX
     *
     * @return string
     * @global core_renderer $OUTPUT
     */
    private function get_content_noajax() {
        global $OUTPUT;

        $html = '<div class="error">' . get_string('requireajax', __CLASS__) . '</div>';
        if (has_capability('moodle/site:config', context_system::instance())) {
            $url = new moodle_url('/admin/settings.php?section=ajax');
            $link = '<a href="' . s($url) . '">' . get_string('ajaxuse') . '</a>';
            $html .= '<div>' . $OUTPUT->rarrow() . ' ' . $link . '</div>';
        }
        return $html;
    }
}
