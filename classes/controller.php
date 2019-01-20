<?php
/**
 *  Sharing Cart
 *  
 *  @author  VERSION2, Inc.
 *  @version $Id: controller.php 882 2012-11-01 05:06:21Z malu $
 */
namespace sharing_cart;

require_once __DIR__.'/storage.php';
require_once __DIR__.'/record.php';
require_once __DIR__.'/scoped.php';

/**
 *  Sharing Cart action controller
 */
class controller
{
	/** @const int  The maximum length of a backup file name */
	const MAX_FILENAME = 20;
	
	/**
	 *  Constructor
	 *  
	 *  @throws \require_login_exception
	 */
	public function __construct()
	{
		\require_login(null, false, null, false, true);
	}
	
	
	/**
	 *  Render an item tree
	 *  
	 *  @global \moodle_database $DB
	 *  @global object $USER
	 *  @param int $userid = $USER->id
	 *  @return string HTML
	 */
	public function render_tree($userid = null)
	{
		global $DB, $USER;

		require_once __DIR__.'/renderer.php';
		
		// build an item tree from flat records
		$records = $DB->get_records(record::TABLE,
			array('userid' => $userid ?: $USER->id)
			);
		$tree = array();
		foreach ($records as $record) {
			$components = explode('/', trim($record->tree, '/'));
			$node_ptr = &$tree;
			do {
				$dir = (string)array_shift($components);
				isset($node_ptr[$dir]) or $node_ptr[$dir] = array();
				$node_ptr = &$node_ptr[$dir];
			} while ($dir !== '');
			$node_ptr[] = $record;
		}
		
		// sort tree nodes and leaves
		$sort_node = function (array &$node) use (&$sort_node)
		{
			uksort($node, function ($lhs, $rhs)
			{
				// items follow directory
				if ($lhs === '') return +1;
				if ($rhs === '') return -1;
				return strnatcasecmp($lhs, $rhs);
			});
			foreach ($node as $name => &$leaf) {
				if ($name !== '') {
					$sort_node($leaf);
				} else {
					usort($leaf, function ($lhs, $rhs)
					{
						if ($lhs->weight < $rhs->weight) return -1;
						if ($lhs->weight > $rhs->weight) return +1;
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
	 *  @param int $cmid
	 *  @return boolean
	 */
	public function is_userdata_copyable($cmid)
	{
		$cm = \get_coursemodule_from_id(null, $cmid, 0, false, MUST_EXIST);
		$modtypes = \get_config('block_sharing_cart', 'userdata_copyable_modtypes');
		$context = \context_module::instance($cm->id);
		return in_array($cm->modname, explode(',', $modtypes))
			&& \has_capability('moodle/backup:userinfo', $context)
			&& \has_capability('moodle/backup:anonymise', $context)
			&& \has_capability('moodle/restore:userinfo', $context);
	}
	
	/**
	 *  Backup a module into Sharing Cart
	 *  
	 *  @global object $CFG
	 *  @global \moodle_database $DB
	 *  @global object $USER
	 *  @param int     $cmid
	 *  @param boolean $userdata
	 *  @throws \moodle_exception
	 */
	public function backup($cmid, $userdata)
	{
		global $CFG, $DB, $USER;
		
		require_once __DIR__.'/../../../backup/util/includes/backup_includes.php';
		
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
		$cleanname = \clean_filename(strip_tags($modtext));
		if ($this->get_string_length($cleanname) > self::MAX_FILENAME)
			$cleanname = $this->get_sub_string($cleanname, 0, self::MAX_FILENAME) . '_';
		$filename = sprintf('%s-%s.mbz', $cleanname, date('Ymd-His'));
		
		// backup the module into the predefined area
		//    - user/backup ... if userdata not included
		//    - backup/activity ... if userdata included
		$settings = array(
			'role_assignments' => false,
			'activities'       => true,
			'blocks'           => false,
			'filters'          => false,
			'comments'         => false,
			'calendarevents'   => false,
			'userscompletion'  => false,
			'logs'             => false,
			'grade_histories'  => false,
			);
		if (\has_capability('moodle/backup:userinfo', $context) &&
			\has_capability('moodle/backup:anonymise', $context) &&
			\has_capability('moodle/restore:userinfo', $context))
		{
			// set the userdata flags only if the operator has capability
			$settings += array(
				'users'     => $userdata,
				'anonymize' => false,
				);
		}
		$controller = new \backup_controller(
			\backup::TYPE_1ACTIVITY,
			$cm->id,
			\backup::FORMAT_MOODLE,
			\backup::INTERACTIVE_NO,
			\backup::MODE_GENERAL,
			$USER->id
			);
		$plan = $controller->get_plan();
		foreach ($settings as $name => $value) {
			if ($plan->setting_exists($name))
				$plan->get_setting($name)->set_value($value);
		}
		$plan->get_setting('filename')->set_value($filename);
		
		set_time_limit(0);
		$controller->set_status(\backup::STATUS_AWAITING);
		$controller->execute_plan();
		
		// move the backup file to user/backup area if it is not in there
		$results = $controller->get_results();
		$file = $results['backup_destination'];
		if ($file->get_component() != storage::COMPONENT ||
			$file->get_filearea()  != storage::FILEAREA)
		{
			$storage = new storage($USER->id);
			$storage->copy_from($file);
			$file->delete();
		}
		
		$controller->destroy();
		
		// insert an item record
		$record = new record(array(
			'modname'  => $cm->modname,
			'modicon'  => self::get_cm_icon($cm),
			'modtext'  => $modtext,
			'filename' => $filename,
		));
		$record->insert();
	}
	
	/**
	 * Multibyte safe get_string_length() function, uses mbstring or iconv for UTF-8, falls back to typo3.
	 *
	 * @param string $text input string
	 * @return int number of characters
	 */
	private function get_string_length($text)
	{
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
    private function get_sub_string($text, $start, $length)
	{
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
	 *  @global object $CFG
	 *  @global \moodle_database $DB
	 *  @global object $USER
	 *  @param int $id
	 *  @param int $courseid
	 *  @param int $sectionnumber
	 *  @throws \moodle_exception
	 */
	public function restore($id, $courseid, $sectionnumber)
	{
		global $CFG, $DB, $USER;
		
		require_once __DIR__.'/../../../backup/util/includes/restore_includes.php';
		require_once __DIR__.'/../backup/util/helper/restore_fix_missings_helper.php';
		
		// cleanup temporary files when we exit this scope
		$tempfiles = array();
		$scope = new scoped(function () use (&$tempfiles)
		{
			foreach ($tempfiles as $tempfile)
				\fulldelete($tempfile);
		});
		
		// validate parameters and capabilities
		$record = record::from_id($id);
		if ($record->userid != $USER->id)
			throw new exception('forbidden');
		$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
		$section = $DB->get_record('course_sections',
			array('course' => $course->id, 'section' => $sectionnumber), '*', MUST_EXIST);
		\require_capability('moodle/restore:restorecourse',
			\context_course::instance($course->id)
			);
		self::validate_sesskey();
		
		// prepare the temporary directory and generate a temporary name
		$tempdir = self::get_tempdir();
		$tempname = \restore_controller::get_tempdir_name($course->id, $USER->id);
		
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
		$controller = new \restore_controller($tempname, $course->id,
			\backup::INTERACTIVE_NO, \backup::MODE_GENERAL, $USER->id,
			\backup::TARGET_EXISTING_ADDING);
		foreach ($controller->get_plan()->get_tasks() as $task) {
			if ($task->setting_exists('overwrite_conf'))
				$task->get_setting('overwrite_conf')->set_value(false);
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
	 *  Move a shared item into a directory
	 *  
	 *  @global object $USER
	 *  @param int $id
	 *  @param string $path
	 */
	public function movedir($id, $path)
	{
		global $USER;

		$record = record::from_id($id);
		if ($record->userid != $USER->id)
			throw new exception('forbidden');
		self::validate_sesskey();
		
		$components = array_filter(explode('/', $path), 'strlen');
		$path = implode('/', $components);
		if (strcmp($record->tree, $path) != 0) {
			$record->tree   = $path;
			$record->weight = record::WEIGHT_BOTTOM;
			$record->update();
		}
	}
	
	/**
	 *  Move a shared item to a position of another item
	 *  
	 *  @global \moodle_database $DB
	 *  @global object $USER
	 *  @param int $id  The record ID to move
	 *  @param int $to  The record ID of the desired position or zero for move to bottom
	 */
	public function move($id, $to)
	{
		global $DB, $USER;

		$record = record::from_id($id);
		if ($record->userid != $USER->id)
			throw new exception('forbidden');
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
	 *  @global object $USER
	 *  @param int $id
	 *  @throws \moodle_exception
	 */
	public function delete($id)
	{
		global $USER;

		$record = record::from_id($id);
		if ($record->userid != $USER->id)
			throw new exception('forbidden');
		self::validate_sesskey();
		
		$storage = new storage();
		$storage->delete($record->filename);
		
		$record->delete();
	}
	
	/**
	 *  Get the path to the temporary directory for backup
	 *  
	 *  @global object $CFG
	 *  @return string
	 *  @throws exception
	 */
	public static function get_tempdir()
	{
		global $CFG;
		$tempdir = $CFG->tempdir . '/backup';
		if (!\check_dir_exists($tempdir, true, true))
			throw new exception('unexpectederror');
		return $tempdir;
	}
	
	/**
	 *  Check if the given session key is valid
	 *  
	 *  @param string $sesskey = \required_param('sesskey', PARAM_RAW)
	 *  @throws exception
	 */
	public static function validate_sesskey($sesskey = null)
	{
		try {
			if (\confirm_sesskey($sesskey))
				return;
		} catch (\moodle_exception $ex) {
			unset($ex);
		}
		throw new exception('invalidoperation');
	}
	
	/**
	 *  Get the intro HTML of the course module
	 *  
	 *  @global \moodle_database $DB
	 *  @param object $cm
	 *  @return string
	 */
	public static function get_cm_intro($cm)
	{
		global $DB;
		if (!property_exists($cm, 'extra')) {
			$mod = $DB->get_record_sql(
				'SELECT m.id, m.name, m.intro, m.introformat
					FROM {'.$cm->modname.'} m, {course_modules} cm
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
	 *  @global object $CFG
	 *  @param object $cm
	 *  @return string
	 */
	public static function get_cm_icon($cm)
	{
		global $CFG;
		if (file_exists("$CFG->dirroot/mod/$cm->modname/lib.php")) {
			include_once"$CFG->dirroot/mod/$cm->modname/lib.php";
			if (function_exists("{$cm->modname}_get_coursemodule_info")) {
				$info = call_user_func("{$cm->modname}_get_coursemodule_info", $cm);
				if (!empty($info->icon) && empty($info->iconcomponent))
					return $info->icon;
				// TODO: add a field for iconcomponent to block_sharing_cart table?
			}
		}
		return '';
	}
}
