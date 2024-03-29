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
 * @copyright  2023 (c) Don Hinkelman, moxis and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_sharing_cart\controller;
use block_sharing_cart\repositories\task_repository;
use block_sharing_cart\section;
use block_sharing_cart\module;

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
    public function init(): void {
        $this->title = get_string('pluginname', __CLASS__);
    }

    public function applicable_formats(): array {
        return array(
                'all' => false,
                'course' => true,
                'site' => true
        );
    }

    public function has_config(): bool {
        return true;
    }

	/**
	 *  Get the block content
	 *
	 * @return object|string
	 *
	 * @throws coding_exception
	 * @throws dml_exception|moodle_exception
	 * @global object $USER
	 */
    public function get_content() {
        global $USER, $COURSE, $PAGE;

        $section_id = optional_param('sectionid', null, PARAM_INT);
        $section_section = optional_param('section', null, PARAM_INT);

        $context = context_course::instance($this->page->course->id);

        if ($this->content !== null) {
            return $this->content;
        }

        if (!$this->page->user_is_editing() || !has_capability('moodle/backup:backupactivity', $context)) {
            return $this->content = '';
        }

        $controller = new controller();
        $html = $controller->render_tree($USER->id);

        $controller->delete_unused_sections($this->page->course->id);

        // Fetching all sections for current course.
        $sectionsHandler = new section();
        $sections = $sectionsHandler->all($COURSE->id);

        $moduleHandler = new module();
        $activities = $moduleHandler->get_all_from_course($COURSE->id);

        /* Place the <noscript> tag to give out an error message if JavaScript is not enabled in the browser.
         * Adding bootstrap classes to show colored info in bootstrap based themes. */
        $noscript = html_writer::tag('noscript',
            html_writer::tag(
                'div',
                get_string('requirejs', __CLASS__),
                ['class' => 'error alert alert-danger']
            )
        );
        $html = $noscript . $html;

        $this->page->requires->css('/blocks/sharing_cart/styles.css');
        if ($this->is_special_version()) {
            $this->page->requires->css('/blocks/sharing_cart/custom.css');
        }
        $this->page->requires->jquery();
        $this->page->requires->js_call_amd(
            'block_sharing_cart/script',
            'init',
            [
                'add_method' => get_config('block_sharing_cart', 'add_to_sharing_cart')
            ]
        );
        $this->page->requires->strings_for_js(
            ['yes', 'no', 'ok', 'cancel', 'error', 'edit', 'move', 'delete', 'movehere'],
            'moodle'
        );
        $this->page->requires->strings_for_js(
            [
                'copyhere',
                'notarget',
                'backup',
                'restore',
                'movedir',
                'clipboard',
                'confirm_backup',
                'confirm_backup_section',
                'confirm_userdata',
                'confirm_delete',
                'clicktomove',
                'folder_string',
                'activity_string',
                'delete_folder',
                'modal_checkbox',
                'modal_checkbox_anonymize',
                'modal_confirm_backup',
                'modal_confirm_delete',
                'backup_heavy_load_warning_message',
                'drop_here',
            ],
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

            $footer .= $this->insert_copy_section_in_footer($section_section, $sections_dropdown);

            if (!has_capability('moodle/course:manageactivities', $context)) {
                $activities_dropdown = '';
                /** @var cm_info $activity */
                foreach ($activities as $activity) {
                    if (!$this->is_activity_in_section($section_id, $section_section, $activity)) {
                        continue;
                    }

                    if ($this->is_activity_deletion_in_progress($activity)) {
                        continue;
                    }

                    $activityname = $activity->get_name();
                        $activities_dropdown .= "
                            <option data-activity-id='$activity->id' data-activity-name='$activityname'>
                                $activityname
                            </option>
                        ";
                }

                if ($activities_dropdown !== '') {
                    $footer .= $this->insert_copy_activity_in_footer($activities_dropdown);
                }
            }
        }
        $footer .= '
                    <div style="display:none;">
                    <div class="header-commands">' . $this->get_header() . '</div>
                    </div>
                ';

        $this->notify_restore_in_progress();

        return $this->content = (object) array('text' => $html, 'footer' => $footer);
    }

    private function is_activity_in_section(?int $section_id, ?int $section, cm_info $activity): bool {
        $activity_section = $activity->get_section_info()->section;
        $activity_id = $activity->get_section_info()->id;

        if ($section === null && $section_id === null) {
            return true;
        }

        return $this->is_activity_section_set($section, $activity_section)
            || $this->is_activity_section_id_set($section_id, $activity_id);
    }

    private function is_activity_section_set(?int $section, int $activity_section): bool {
        return $section === $activity_section;
    }

    private function is_activity_section_id_set(?int $section_id, int $activity_section_id): bool {
        return $section_id == $activity_section_id;
    }

    private function is_activity_deletion_in_progress(cm_info $activity): bool {
        return $activity->deletioninprogress == 1;
    }

    private function insert_copy_section_in_footer(?int $section_id, string $sections_dropdown): string {
        if (!get_config('block_sharing_cart', 'show_copy_section_in_block')) {
            return "";
        }

        return "
            <form id=\"copy-section-form\" data-in-section=\"" . ($section_id ? 1 : 0) . "\">
                <select class='custom-select section-dropdown'>
                    $sections_dropdown
                </select>
                <a href='javascript:void(0)' class='copy_section' title='" . get_string('copy_section_title', __CLASS__) . "'>
                    <input id='copy' type='button' class='btn btn-primary' value='" . get_string('copy_section', __CLASS__) . "'>
                </a>
            </form>
        ";
    }

    private function insert_copy_activity_in_footer(string $activities): string {
        if (!get_config('block_sharing_cart', 'show_copy_activity_in_block')) {
            return "";
        }

        return "
            <form class='mt-3' id=\"copy-activity-form\"'>
            <p>" . get_string('copy_activity_reason', __CLASS__) . "</p>
                <select class='custom-select activity-dropdown'>
                    $activities
                </select>
                <a href='javascript:void(0)' class='copy_activity' title='" . get_string('copy_activity_title', __CLASS__) . "'>
                    <input id='copy' type='button' class='btn btn-primary' value='" . get_string('copy_activity', __CLASS__) . "'>
                </a>
            </form>
        ";
    }

    /**
     *  Get the block header
     *
     * @return string
     * @throws coding_exception
     * @throws moodle_exception
     */
    private function get_header(): string {
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
    private function get_bulk_delete(string $alt, moodle_url $url): string {
        return '
		        <a class="editing_bulkdelete" title="' . s($alt) . '" href="' . s($url) . '">
		        <i class="bulk-icon icon fa fa-times-circle" alt="' . s($alt) . '" /></i>
		        </a>
		        ';
    }

    private function notify_restore_in_progress(): void
    {
        global $COURSE;
        if (empty($COURSE->id) && $COURSE->id < 2) {
            return;
        }

        task_repository::create()->notify_restore_in_progress_by_course_id($COURSE->id);
    }

    /**
     *  Get help icon
     *
     * @return string
     */
    private function get_help_icon(): string {
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
    private function is_special_version(): bool {
        return version_compare(moodle_major_version(), '3.2') === 1;
    }
}
