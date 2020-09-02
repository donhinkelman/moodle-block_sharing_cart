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

namespace block_sharing_cart;

use backup_controller;
use block_sharing_cart\exceptions\no_backup_support_exception;
use restore_controller;
use stdClass;
use base_setting;

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../../../course/lib.php';

/**
 *  Sharing Cart action controller
 */
class controller {
    /** @const int  The maximum length of a backup file name */
    protected const MAX_FILENAME = 20;

    /** @var string	The prefix to add to the file to let the user know this is a Sharing Cart file */
    protected const PREFIX_FILENAME = 'Sharingcart';

	/**
	 *  Constructor
	 *
	 * @throws \coding_exception
	 * @throws \moodle_exception
	 * @throws \require_login_exception
	 */
    public function __construct() {
        \require_login(null, false, null, false, true);
    }

	/**
	 *  Render an item tree
	 *
	 * @param null $userid = $USER->id
	 * @return string HTML
	 * @throws \coding_exception
	 * @throws \dml_exception
	 * @global \moodle_database $DB
	 * @global object $USER
	 */
    public function render_tree($userid = null) {
        global $DB, $USER;

        require_once __DIR__ . '/renderer.php';

        // build an item tree from flat records
        $records = $DB->get_records('block_sharing_cart', array('userid' => $USER->id));

        foreach ($records as $i => $record) {

            // If the file is removed from the private backup area for the user
            if (!empty($record->userid) && !$DB->record_exists('files', [
                'userid' => $record->userid,
                'filename' => $record->filename,
                'filearea' => 'backup'
            ])) {
                // Then remove the file from the sharing cart as well since we don't want to show a deleted file
                $DB->delete_records('block_sharing_cart', [
                    'userid' => $record->userid,
                    'filename' => $record->filename
                ]);
                unset($records[$i]);
                continue;
            }

            $course = $DB->get_record('course', array('id' => $record->course));
            $record->coursefullname = $course ? $course->fullname : '';
        }

        $records = array_values($records);

        $tree = array();
        foreach ($records as $record) {
            $components = explode('/', trim($record->tree, '/'));
            $node_ptr = &$tree;
            do {
                $dir = (string) array_shift($components);
                isset($node_ptr[$dir]) || $node_ptr[$dir] = array();
                $node_ptr = &$node_ptr[$dir];
            } while ($dir !== '');
            $node_ptr[] = $record;
        }

        // sort tree nodes and leaves
        $sort_node = function(array &$node) use (&$sort_node) {
            uksort($node, function($lhs, $rhs) {
                // items follow directory
                if ($lhs === '') {
                    return +1;
                }
                if ($rhs === '') {
                    return -1;
                }
                return strnatcasecmp($lhs, $rhs);
            });
            foreach ($node as $name => &$leaf) {
                if ($name !== '') {
                    $sort_node($leaf);
                } else {
                    usort($leaf, function($lhs, $rhs) {
                        if ($lhs->weight < $rhs->weight) {
                            return -1;
                        }
                        if ($lhs->weight > $rhs->weight) {
                            return +1;
                        }
                        return strnatcasecmp($lhs->modtext, $rhs->modtext);
                    });
                }
            }
        };
        $sort_node($tree);

        return renderer::render_tree($tree);
    }

	/**
	 *  Get whether a module is userdata copyable and the logged-in user has enough capabilities
	 *
	 * @param int $cmid
	 * @return boolean
	 * @throws \coding_exception
	 * @throws \dml_exception
	 */
    public function is_userdata_copyable($cmid) {
        $cm = \get_coursemodule_from_id(null, $cmid, 0, false, MUST_EXIST);
        $modtypes = \get_config('block_sharing_cart', 'userdata_copyable_modtypes');
        $context = \context_module::instance($cm->id);
        return in_array($cm->modname, explode(',', $modtypes))
                && \has_capability('moodle/backup:userinfo', $context)
                && \has_capability('moodle/backup:anonymise', $context)
                && \has_capability('moodle/restore:userinfo', $context);
    }

	/**
	 *  Get whether any module in section is userdata copyable and the logged-in user has enough capabilities
	 *
	 * @param int $sectionid
	 * @return boolean
	 * @throws \coding_exception
	 * @throws \dml_exception
	 */
    public function is_userdata_copyable_section($sectionid) {
        GLOBAL $DB;

        $modules = $DB->get_records('course_modules', array('section' => $sectionid), '', 'id');
        foreach ($modules as $module) {
            if ($this->is_userdata_copyable($module->id)) {
                return true;
            }
        }

        return false;
    }

	/**
	 * @param $modtext
	 * @return string
	 */
    protected function get_unique_filename($modtext): string{
	    $cleanname = \clean_filename(strip_tags($modtext));
	    if ($this->get_string_length($cleanname) > self::MAX_FILENAME) {
		    $cleanname = $this->get_sub_string($cleanname, 0, self::MAX_FILENAME) . '_';
	    }
	    $cleanname = mb_strtolower($cleanname, 'UTF-8');
	    return sprintf('%s-%s-%s.mbz', self::PREFIX_FILENAME, $cleanname, microtime(true));
    }

    /**
     *  Backup a module into Sharing Cart
     *
     * @param int $cmid
     * @param boolean $userdata
     * @param int $course
     * @param int $section
     * @return int
     * @throws \moodle_exception
     * @global object $CFG
     * @global \moodle_database $DB
     * @global object $USER
     */
    public function backup($cmid, $userdata, $course, $section = 0) {
        global $CFG, $DB, $USER;

        if (module::has_backup($cmid, $course) === false) {
            throw new no_backup_support_exception('No backup in module',
                    'Module not implementing: https://docs.moodle.org/dev/Backup_API');
        }

        require_once __DIR__ . '/../../../backup/util/includes/backup_includes.php';

        // validate parameters and capabilities
        $cm = \get_coursemodule_from_id(null, $cmid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        \require_capability('moodle/backup:backupactivity', $context);
        if ($userdata) {
            \require_capability('moodle/backup:userinfo', $context);
            \require_capability('moodle/backup:anonymise', $context);
            \require_capability('moodle/restore:userinfo', $context);
        }
        self::validate_sesskey();

        // generate a filename from the module info
        $modtext = $cm->modname == 'label' ? self::get_cm_intro($cm) : $cm->name;

        $filename = $this->get_unique_filename($modtext);

        // backup the module into the predefined area
        //    - user/backup ... if userdata not included
        //    - backup/activity ... if userdata included
        $settings = array(
                'role_assignments' => false,
                'activities' => true,
                'blocks' => false,
                'filters' => false,
                'comments' => false,
                'calendarevents' => false,
                'userscompletion' => false,
                'logs' => false,
                'grade_histories' => false,
        );
        if (\has_capability('moodle/backup:userinfo', $context) &&
                \has_capability('moodle/backup:anonymise', $context) &&
                \has_capability('moodle/restore:userinfo', $context)) {
            // set the userdata flags only if the operator has capability
            $settings += array(
                    'users' => $userdata,
                    'anonymize' => false,
            );
        }
        $controller = new backup_controller(
                \backup::TYPE_1ACTIVITY,
                $cm->id,
                \backup::FORMAT_MOODLE,
                \backup::INTERACTIVE_NO,
                \backup::MODE_GENERAL,
                $USER->id
        );
        $plan = $controller->get_plan();
        foreach ($settings as $name => $value) {
            if ($plan->setting_exists($name)) {
                $current_setting = $plan->get_setting($name);
                // If locked
                if (base_setting::NOT_LOCKED !== $current_setting->get_status()) {
                    continue;
                }
                $current_setting->set_value($value);
            }
        }
        $plan->get_setting('filename')->set_value($filename);

        set_time_limit(0);
        $controller->set_status(\backup::STATUS_AWAITING);
        $controller->execute_plan();

        // move the backup file to user/backup area if it is not in there
        $results = $controller->get_results();
        $file = $results['backup_destination'];
        if ($file->get_component() != storage::COMPONENT ||
                $file->get_filearea() != storage::FILEAREA) {
            $storage = new storage($USER->id);
            $storage->copy_from($file);
            $file->delete();
        }

        $controller->destroy();

        // insert an item record
        $record = new record(array(
                'modname' => $cm->modname,
                'modicon' => self::get_cm_icon($cm),
                'modtext' => $modtext,
                'filename' => $filename,
                'course' => $course,
                'section' => $section
        ));
        return $record->insert();
    }

    /**
     * Backup a section into Sharing Cart
     *
     * @param int $sectionid
     * @param string $sectionname
     * @param bool $userdata
     * @param int $course
     * @throws \moodle_exception
     */
    public function backup_section($sectionid, $sectionname, $userdata, $course) {
        global $DB, $USER;

        $itemids = array();

        try {
            // Save section data
            $section = $DB->get_record('course_sections', array('id' => $sectionid));
            $sharing_cart_section = new stdClass();
            $sharing_cart_section->id = 0;
            $sharing_cart_section->name = get_section_name($section->course, $section->section);
            $sharing_cart_section->summary = $section->summary;
            $sharing_cart_section->summaryformat = $section->summaryformat;
            $sc_section_id = $DB->insert_record('block_sharing_cart_sections', $sharing_cart_section);
            $sc_section_id = $sc_section_id ? $sc_section_id : 0;

            // Save section files
            if ($sc_section_id > 0) {
                $course_context = \context_course::instance($course);
                $user_context = \context_user::instance($USER->id);
                $fs = get_file_storage();

                $files = $fs->get_area_files($course_context->id, 'course', 'section', $sectionid);
                foreach ($files as $file) {
                    if ($file->get_filename() != '.') {
                        $filerecord = array(
                                'contextid' => $user_context->id,
                                'component' => 'user',
                                'filearea' => 'sharing_cart_section',
                                'itemid' => $sc_section_id,
                                'filepath' => $file->get_filepath()
                        );

                        $fs->create_file_from_storedfile($filerecord, $file);
                    }
                }
            }

            // Backup all
            $modulesequence = explode(',', $section->sequence);
            $modulecount = $DB->count_records('course_modules', [
                'section' => $sectionid,
                'deletioninprogress' => 0
            ]);

            if (count($modulesequence) != $modulecount) {
                $modules = $DB->get_records('course_modules', [
                    'section' => $sectionid,
                    'deletioninprogress' => 0
                ]);
            } else {
                $modules = [];
                foreach ($modulesequence as $modid) {
                    $modules[] = $DB->get_record('course_modules', [
                        'id' => $modid,
                        'deletioninprogress' => 0
                    ]);
                }
            }

            // Fixed ISSUE-12 - https://github.com/donhinkelman/moodle-block_sharing_cart/issues/12
            foreach ($modules as $module) {
                if ((isset($module->deletioninprogress)
                        && $module->deletioninprogress) === 1
                        || module::has_backup($module->id) === false) {
                    continue;
                }

                if ($userdata && $this->is_userdata_copyable($module->id)) {
                    $itemids[] = $this->backup($module->id, true, $course, $sc_section_id);
                } else {
                    $itemids[] = $this->backup($module->id, false, $course, $sc_section_id);
                }
            }

            // Check empty folder name
            $foldername = str_replace("/", "-", $sectionname);

            if ($DB->record_exists("block_sharing_cart", array("tree" => $foldername, 'userid' => $USER->id))) {
                // Get other folder that contain increment number
                $folder_like = $DB->sql_like_escape($foldername);
                $params = ['userid' => $USER->id, 'tree' => $folder_like . ' (%)'];
                $folders = $DB->get_fieldset_select(record::TABLE, 'tree', 'userid = :userid AND tree LIKE :tree', $params);

                // Increase folder number
                $folder_number = empty($folders) ? 1 : count($folders) + 1;
                $foldername .= " ({$folder_number})";
            }

            // Move backup files to folder
            foreach ($itemids as $itemid) {
                $this->movedir($itemid, $foldername);
            }
        } catch (\moodle_exception $ex) {
            if ($ex->errorcode == "storedfilenotcreated") {
                foreach ($itemids as $itemid) {
                    $this->delete($itemid);
                }
            }

            throw $ex;
        }
    }

    /**
     * Multibyte safe get_string_length() function, uses mbstring or iconv for UTF-8, falls back to typo3.
     *
     * @param string $text input string
     * @return int number of characters
     */
    private function get_string_length($text) {
        $textlength = 0;
        if (method_exists('textlib', 'strlen')) {
            $textlength = \textlib::strlen($text);
        } else if (method_exists('core_text', 'strlen')) {
            $textlength = \core_text::strlen($text);
        }
        return $textlength;
    }

    /**
     * Multibyte safe get_sub_string() function, uses mbstring or iconv for UTF-8, falls back to typo3.
     *
     * @param string $text string to truncate
     * @param int $start negative value means from end
     * @param int $len maximum length of characters beginning from start
     * @return string portion of string specified by the $start and $len
     */
    private function get_sub_string($text, $start, $length) {
        $result = 0;
        if (method_exists('textlib', 'substr')) {
            $result = \textlib::substr($text, $start, $length);
        } else if (method_exists('core_text', 'substr')) {
            $result = \core_text::substr($text, $start, $length);
        }
        return $result;
    }

    /**
     *  Restore an item into a course section
     *
     * @param int $id
     * @param int $courseid
     * @param int $sectionnumber
     * @throws \moodle_exception
     * @global \moodle_database $DB
     * @global object $USER
     * @global object $CFG
     */
    public function restore($id, $courseid, $sectionnumber) {
        global $CFG, $DB, $USER;

        require_once __DIR__ . '/../../../backup/util/includes/restore_includes.php';
        require_once __DIR__ . '/../backup/util/helper/restore_fix_missings_helper.php';

        // cleanup temporary files when we exit this scope
        $tempfiles = array();
        $scope = new scoped(function() use (&$tempfiles) {
            foreach ($tempfiles as $tempfile) {
                \fulldelete($tempfile);
            }
        });

        // validate parameters and capabilities
        $record = record::from_id($id);
        if ($record->userid != $USER->id) {
            throw new exception('forbidden');
        }
        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $section = $DB->get_record('course_sections',
                array('course' => $course->id, 'section' => $sectionnumber), '*', MUST_EXIST);
        \require_capability('moodle/restore:restorecourse',
                \context_course::instance($course->id)
        );
        self::validate_sesskey();

        // prepare the temporary directory and generate a temporary name
        $tempdir = self::get_tempdir();
        $tempname = restore_controller::get_tempdir_name($course->id, $USER->id);

        // copy the backup archive into the temporary directory
        $storage = new storage();
        $file = $storage->get($record->filename);
        $file->copy_content_to("$tempdir/$tempname.mbz");
        $tempfiles[] = "$tempdir/$tempname.mbz";

        // extract the archive in the temporary directory
        $packer = \get_file_packer('application/vnd.moodle.backup');
        $packer->extract_to_pathname("$tempdir/$tempname.mbz", "$tempdir/$tempname");
        $tempfiles[] = "$tempdir/$tempname";

        // restore a module from the extracted files
        $controller = new restore_controller($tempname, $course->id,
                \backup::INTERACTIVE_NO, \backup::MODE_GENERAL, $USER->id,
                \backup::TARGET_EXISTING_ADDING);
        foreach ($controller->get_plan()->get_tasks() as $task) {
            if ($task->setting_exists('overwrite_conf')) {
                $task->get_setting('overwrite_conf')->set_value(false);
            }
        }
        if (\get_config('block_sharing_cart', 'workaround_qtypes')) {
            \restore_fix_missings_helper::fix_plan($controller->get_plan());
        }
        $controller->set_status(\backup::STATUS_AWAITING);
        $controller->execute_plan();

        // move the restored module to desired section
        foreach ($controller->get_plan()->get_tasks() as $task) {
            if ($task instanceof \restore_activity_task) {
                $cmid = $task->get_moduleid();
                $cm = \get_coursemodule_from_id(null, $cmid, 0, false, MUST_EXIST);
                \moveto_module($cm, $section);
            }
        }
        \rebuild_course_cache($course->id);

        $controller->destroy();
    }

    /**
     * Resotre a directory into a course section
     *
     * @param string $path
     * @param int $courseid
     * @param int $sectionnumber
     * @param int $overwritesectionid
     */
    public function restore_directory($path, $courseid, $sectionnumber, $overwritesectionid) {
        global $DB, $USER;

        $idObjects = $DB->get_records("block_sharing_cart", array("tree" => $path), "weight ASC", "id");

        foreach ($idObjects as $idObject) {
            $this->restore($idObject->id, $courseid, $sectionnumber);
        }

        if ($overwritesectionid > 0) {
            $overwrite_section = $DB->get_record('block_sharing_cart_sections', array('id' => $overwritesectionid));

            $restored_section = $DB->get_record('course_sections', array('course' => $courseid, 'section' => $sectionnumber));
            $original_restored_section = clone($restored_section);

            if ($overwrite_section && $restored_section) {
                $restored_section->name = $overwrite_section->name;
                $restored_section->summary = $overwrite_section->summary;
                $restored_section->summaryformat = $overwrite_section->summaryformat;

                course_update_section($courseid, $original_restored_section, $restored_section);
            }

            // Copy section files
            $user_context = \context_user::instance($USER->id);
            $course_context = \context_course::instance($courseid);
            $fs = get_file_storage();
            $files = $fs->get_area_files($user_context->id, 'user', 'sharing_cart_section', $overwritesectionid);
            foreach ($files as $file) {
                if ($file->get_filename() != '.') {
                    $filerecord = array(
                            'contextid' => $course_context->id,
                            'component' => 'course',
                            'filearea' => 'section',
                            'itemid' => $restored_section->id,
                            'filepath' => $file->get_filepath()
                    );

                    $fs->create_file_from_storedfile($filerecord, $file);
                }
            }
        }
    }

    /**
     *  Move a shared item into a directory
     *
     * @param int $id
     * @param string $path
     * @global object $USER
     */
    public function movedir($id, $path) {
        global $USER;

        $record = record::from_id($id);
        if ($record->userid != $USER->id) {
            throw new exception('forbidden');
        }
        self::validate_sesskey();

        $components = array_filter(explode('/', $path), 'strlen');
        $path = implode('/', $components);
        if (strcmp($record->tree, $path) != 0) {
            $record->tree = $path;
            $record->weight = record::WEIGHT_BOTTOM;
            $record->update();
        }
    }

    /**
     *  Move a shared item to a position of another item
     *
     * @param int $id The record ID to move
     * @param int $to The record ID of the desired position or zero for move to bottom
     * @global \moodle_database $DB
     * @global object $USER
     */
    public function move($id, $to) {
        global $DB, $USER;

        $record = record::from_id($id);
        if ($record->userid != $USER->id) {
            throw new exception('forbidden');
        }
        self::validate_sesskey();

        // get the weight of desired position
        $record->weight = $to != 0
                ? record::from_id($to)->weight
                : record::WEIGHT_BOTTOM;

        // shift existing items under the desired position
        $DB->execute(
                'UPDATE {' . record::TABLE . '} SET weight = weight + 1
			 WHERE userid = ? AND tree = ? AND weight >= ?',
                array($USER->id, $record->tree, $record->weight)
        );

        $record->update();
    }

    /**
     *  Delete a shared item by record ID
     *
     * @param int $id
     * @throws \moodle_exception
     * @global object $USER
     */
    public function delete($id) {
        global $USER;

        $record = record::from_id($id);
        if ($record->userid != $USER->id) {
            throw new exception('forbidden');
        }
        self::validate_sesskey();

        $storage = new storage();
        $storage->delete($record->filename);

        $record->delete();
    }

    /**
     * Delete a directory
     *
     * @param $path
     * @throws \dml_exception
     */
    public function delete_directory($path) {
        global $DB, $USER;

        if ($path[0] == '/') {
            $path = substr($path, 1);
        }

        $idObjects = $DB->get_records('block_sharing_cart', array('tree' => $path, 'userid' => $USER->id), '', 'id');

        foreach ($idObjects as $idObject) {
            $this->delete($idObject->id);
        }

        $this->delete_unused_sections();

        // Delete unused file
        $fs = get_file_storage();
        $user_context = \context_user::instance($USER->id);
        $files = $fs->get_area_files($user_context->id, 'user', 'sharing_cart_section');
        foreach ($files as $file) {
            $sectionid = $file->get_itemid();
            if (!$DB->record_exists('block_sharing_cart_sections', array('id' => $sectionid))) {
                $file->delete();
            }
        }
    }

    /**
    * Delete sections without activities since they are not used anymore
    *
    * @param int $course_id
    *
    * @return void
    * @throws \dml_exception
    */
    public function delete_unused_sections(int $course_id = 0) : void {

        global $DB;

        $sql_params = [];

        $sql = /** @lang mysql */'
        SELECT DISTINCT s.id
        FROM {block_sharing_cart_sections} s
        LEFT JOIN {block_sharing_cart} sc ON s.id = sc.section
        ';

        if (!empty($course_id)) {
            $sql .= 'WHERE sc.course = :course_id';
            $sql_params['course_id'] = $course_id;
        }

        $sections = $DB->get_records_sql($sql, $sql_params);

        foreach ($sections as $section) {
            if ((int)$DB->count_records('block_sharing_cart', ['section' => $section->id]) === 0) {
                $DB->delete_records('block_sharing_cart_sections', ['id' => $section->id]);
            }
        }
    }

    /**
     * Get sections in specified path
     *
     * @param string $path
     * @return array
     */
    public function get_path_sections($path) {
        global $DB;

        $section_ids = array();
        $items = $DB->get_records('block_sharing_cart', array('tree' => $path));
        foreach ($items as $item) {
            if ($item->section) {
                $section_ids[] = $item->section;
            }
        }

        $section_ids = array_unique($section_ids);
        $sections = $DB->get_records_list('block_sharing_cart_sections', 'id', $section_ids);
        return $sections;
    }

    /**
     *  Get the path to the temporary directory for backup
     *
     * @return string
     * @throws exception
     * @global object $CFG
     */
    public static function get_tempdir() {
        global $CFG;
        $tempdir = $CFG->tempdir . '/backup';
        if (!\check_dir_exists($tempdir, true, true)) {
            throw new exception('unexpectederror');
        }
        return $tempdir;
    }

    /**
     *  Check if the given session key is valid
     *
     * @param string $sesskey = \required_param('sesskey', PARAM_RAW)
     * @throws exception
     */
    public static function validate_sesskey($sesskey = null) {
        try {
            if (\confirm_sesskey($sesskey)) {
                return;
            }
        } catch (\moodle_exception $ex) {
            unset($ex);
        }
        throw new exception('invalidoperation');
    }

    /**
     *  Get the intro HTML of the course module
     *
     * @param object $cm
     * @return string
     * @global \moodle_database $DB
     */
    public static function get_cm_intro($cm) {
        global $DB;
        if (!property_exists($cm, 'extra')) {
            $mod = $DB->get_record_sql(
                    'SELECT m.id, m.name, m.intro, m.introformat
					FROM {' . $cm->modname . '} m, {course_modules} cm
					WHERE m.id = cm.instance AND cm.id = :cmid',
                    array('cmid' => $cm->id)
            );
            $cm->extra = \format_module_intro($cm->modname, $mod, $cm->id, false);
        }
        return $cm->extra;
    }

    /**
     *  Get the icon for the course module
     *
     * @param object $cm
     * @return string
     * @global object $CFG
     */
    public static function get_cm_icon($cm) {
        global $CFG;
        if (file_exists("$CFG->dirroot/mod/$cm->modname/lib.php")) {
            include_once "$CFG->dirroot/mod/$cm->modname/lib.php";
            if (function_exists("{$cm->modname}_get_coursemodule_info")) {
                $info = call_user_func("{$cm->modname}_get_coursemodule_info", $cm);
                if (!empty($info->icon) && empty($info->iconcomponent)) {
                    return $info->icon;
                }
                // TODO: add a field for iconcomponent to block_sharing_cart table?
            }
        }
        return '';
    }

    /**
     * @param $cmid
     * @param $courseid
     * @return array
     * @throws \moodle_exception
     */
    public function ensure_backup_in_module($cmid, $courseid) {
        return json_encode(array(
                'http_response' => 200,
                'message' => '',
                'data' => array(
                        'has_backup_routine' => module::has_backup($cmid, $courseid)
                ),
        ));
    }
}
