<?php
/**
 *  Sharing Cart - Restore Implementation
 *  
 *  @author  VERSION2, Inc.
 *  @version $Id: restore.php 785 2012-09-11 09:01:38Z malu $
 */

namespace sharing_cart;

require_once __DIR__.'/../../../backup/util/includes/restore_includes.php';
require_once __DIR__.'/../../../backup/util/ui/renderer.php';

require_once __DIR__.'/exception.php';
require_once __DIR__.'/storage.php';
require_once __DIR__.'/utils.php';

class restore
{
	/**
	 *  Constructor
	 *  
	 *  @param int $course_id  The target course ID
	 *  @param int $section_i  The target section number
	 */
	public function __construct($course_id, $section_i)
	{
		$this->tempfiles = array();
		
		$this->course = $GLOBALS['DB']->get_record('course',
			array('id' => $course_id), '*', MUST_EXIST);
		$this->section = $GLOBALS['DB']->get_record('course_sections',
			array('course' => $course_id, 'section' => $section_i), '*', MUST_EXIST);
		$this->context = \get_context_instance(CONTEXT_COURSE, $this->course->id);
		
		\require_login($this->course, false);
		\require_capability('moodle/restore:restorecourse', $this->context);
	}
	
	/**
	 *  Destructor
	 */
	public function __destruct()
	{
		$tempdir = self::get_tempdir();
		foreach ($this->tempfiles as $tempfile)
			\fulldelete($tempdir . '/' . $tempfile);
	}
	
	/**
	 *  Restore item by filename
	 *  
	 *  @param string $filename  The Moodle storage filename
	 */
	public function restore_file($filename)
	{
		// some Moodle UI functions require $PAGE->set_url()...
		$GLOBALS['PAGE']->set_url('/blocks/sharing_cart/restore.php', array(
			'id'      => required_param('id', PARAM_INT),
			'course'  => required_param('course', PARAM_INT),
			'section' => required_param('section', PARAM_INT),
			'return'  => urldecode(required_param('return', PARAM_TEXT)),
			));
		
		// copy the backup archive to the temporary directory
		$filename = $this->copy_archive_to_tempdir($filename);
		$this->tempfiles[] = $filename;
		
		// extract the archive in the temporary directory
		$ui = $this->process(\restore_ui::STAGE_CONFIRM, array(
			'filename' => $filename,
		));
		// HACK: get the extracted directory name
		$renderer = new stage_confirm_hook_renderer();
		$ui->display($renderer);
		$filepath = $renderer->filepath;
		$this->tempfiles[] = $filepath;
		
		// prepare the restore settings UI and get the restore unique ID
		$ui = $this->process(\restore_ui::STAGE_SETTINGS, array(
			'filepath' => $filepath,
			'target'   => \backup::TARGET_CURRENT_ADDING,
			'targetid' => $this->course->id,
		));
		$restore_id = $ui->get_restoreid();
		
		// get the restore settings from UI and generate fake parameters
		$prefix = \base_setting_ui::NAME_PREFIX;
		$params = array('_qf__restore_review_form' => 1);
		foreach ($ui->get_tasks() as $task) {
			foreach ($task->get_settings() as $setting) {
				// set 1 if it is activity_xyz_123_included or root_activities, 0 otherwise
				$params[$prefix . $setting->get_ui()->get_name()] = (int)(
					$setting instanceof \restore_activity_generic_setting &&
					preg_match('/_included$/', $setting->get_name()) ||
					$setting->get_name() == 'activities'
				);
			}
		}
		
		// execute restore
		$ui = $this->process(\restore_ui::STAGE_REVIEW, $params, $restore_id);
		$cmids = array_map(
			function ($task) { return $task->get_moduleid(); },
			array_filter(
				$ui->get_controller()->get_plan()->get_tasks(),
				function ($task) { return $task instanceof \restore_activity_task; }
			)
		);
		
		// move course module section
		list ($sql, $params) = $GLOBALS['DB']->get_in_or_equal($cmids);
		$cms = $GLOBALS['DB']->get_records_select('course_modules', "id $sql", $params);
		foreach ($cms as $cm)
			\moveto_module($cm, $this->section);
		\rebuild_course_cache($this->course->id);
	}
	
	private function copy_archive_to_tempdir($filename)
	{
		// @see /backup/restorefile.php
		
		$tempname = \restore_controller::get_tempdir_name(
			$this->course->id, $GLOBALS['USER']->id);
		
		$storage = new storage();
		$file = $storage->get($filename);
		$file->copy_content_to(self::get_tempdir() . '/' . $tempname);
		
		return $tempname;
	}
	
	private function process($stage, array $params, $restore_id = false)
	{
		$common = array(
			'contextid'    => $this->context->id,
			'sesskey'      => $GLOBALS['USER']->sesskey,
			'submitbutton' => '[[Next]]',
		);
		$params += array('stage' => $stage) + $common;
		
		// execute a stage with fake parameters
		utils\post_update($params);
		{
			$ui = $this->get_restore_ui($stage, $restore_id);
			$ui->process();
			if (!$ui->is_independent()) {
				if ($ui->get_stage() == \restore_ui::STAGE_PROCESS && !$ui->requires_substage())
					$ui->execute();
				else
					$ui->save_controller();
			}
		}
		utils\post_remove($params);
		
		return $ui;
	}
	
	private function get_restore_ui($stage, $restore_id = false)
	{
		// @see /backup/restore.php
		
		if ($stage & \restore_ui::STAGE_CONFIRM + \restore_ui::STAGE_DESTINATION)
			return \restore_ui::engage_independent_stage($stage, $this->context->id);
		
		$controller = \restore_ui::load_controller($restore_id);
		if (!$controller) {
			$ui = \restore_ui::engage_independent_stage($stage >> 1, $this->context->id);
			if ($ui->process()) {
				$controller = new \restore_controller($ui->get_filepath(), $ui->get_course_id(),
					\backup::INTERACTIVE_YES, \backup::MODE_GENERAL, $GLOBALS['USER']->id,
					$ui->get_target());
			}
		}
		if ($controller) {
			if ($controller->get_status() == \backup::STATUS_REQUIRE_CONV)
				$controller->convert();
			return new \restore_ui($controller, array('contextid' => $this->context->id));
		}
		
		assert('!empty($ui)');
		return $ui;
	}
	
	private static function get_tempdir()
	{
		$tempdir = $GLOBALS['CFG']->dataroot . '/temp/backup';
		if (!\check_dir_exists($tempdir, true, true))
			throw new exception('tempdir');
		return $tempdir;
	}
	
	private $course, $section, $context, $tempfiles;
}

class stage_confirm_hook_renderer extends \core_backup_renderer
{
	public function __construct()
	{
		/* Do Nothing */
	}
	public function backup_details($details, $url)
	{
		$this->filepath = $url->param('filepath');
	}
	public $filepath;
}
