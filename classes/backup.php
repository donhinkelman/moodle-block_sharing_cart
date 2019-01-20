<?php
/**
 *  Sharing Cart - Backup Implementation
 *  
 *  @author  VERSION2, Inc.
 *  @version $Id: backup.php 619 2012-03-16 09:50:26Z malu $
 */

namespace sharing_cart;

require_once __DIR__.'/../../../backup/util/includes/backup_includes.php';

require_once __DIR__.'/utils.php';

class backup
{
	/**
	 *  Constructor
	 *  
	 *  @param int $course_id
	 *  @param int $cm_id
	 */
	public function __construct($course_id, $cm_id)
	{
		$this->course = $GLOBALS['DB']->get_record('course',
			array('id' => $course_id), '*', MUST_EXIST);
		$this->cm = \get_coursemodule_from_id(null,
			$cm_id, $this->course->id, false, MUST_EXIST);
		
		\require_login($this->course, false, $this->cm);
		\require_capability('moodle/backup:backupactivity',
			\get_context_instance(CONTEXT_MODULE, $this->cm->id)
			);
	}
	
	/**
	 *  Get the module name
	 *  
	 *  @return string
	 */
	public function get_mod_name()
	{
		return $this->cm->modname;
	}
	
	/**
	 *  Get the module content
	 *  
	 *  @return string
	 */
	public function get_mod_text()
	{
		if ($this->cm->modname == 'label') {
			if (!property_exists($this->cm, 'extra')) {
				$mod = $GLOBALS['DB']->get_record_sql(
					'SELECT m.id, m.name, m.intro, m.introformat
						FROM {'.$this->cm->modname.'} m, {course_modules} cm
						WHERE m.id = cm.instance AND cm.id = :cmid',
					array('cmid' => $this->cm->id)
					);
				$this->cm->extra = format_module_intro(
					$this->cm->modname, $mod, $this->cm->id, false);
			}
			return $this->cm->extra ?: $this->cm->name;
		}
		return $this->cm->name;
	}
	
	/**
	 *  Save the module as the specified filename
	 *  
	 *  @param string $filename
	 */
	public function save_as($filename)
	{
		$this->filename = $filename;
		
		// Non-Interactive mode doesn't work fine for now...
		// We start with Interactive mode and impersonate the UI settings.
		$this->controller = new \backup_controller(
			\backup::TYPE_1ACTIVITY,
			$this->cm->id,
			\backup::FORMAT_MOODLE,
			\backup::INTERACTIVE_YES,
			\backup::MODE_GENERAL,
			$GLOBALS['USER']->id
			);
		
		// Supress outputs
		ob_start();
		{
			$this->execute();
		}
		ob_end_clean();
	}
	
	private function execute()
	{
		$post_key = "activity_{$this->cm->modname}_{$this->cm->id}";
		
		// Initial stage
		$this->process(\backup_ui::STAGE_INITIAL,
			array(
				'_qf__backup_initial_form' => 1,
				'setting_root_activities'  => 1,
			)
		);
		
		// Schema stage
		$this->process(\backup_ui::STAGE_SCHEMA,
			array(
				'_qf__backup_schema_form'      => 1,
				"setting_{$post_key}_userinfo" => 0,
				"setting_{$post_key}_included" => 1,
			)
		);
		
		// Confirmation stage
		$this->process(\backup_ui::STAGE_CONFIRMATION,
			array(
				'_qf__backup_confirmation_form' => 1,
				'setting_root_users'            => 0,
				'setting_root_anonymize'        => 0,
				'setting_root_role_assignments' => 0,
				'setting_root_user_files'       => 0,
				'setting_root_activities'       => 1,
				'setting_root_blocks'           => 0,
				'setting_root_filters'          => 0,
				'setting_root_comments'         => 0,
				'setting_root_userscompletion'  => 0,
				'setting_root_logs'             => 0,
				'setting_root_grade_histories'  => 0,
				"setting_{$post_key}_included"  => 1,
				"setting_{$post_key}_userinfo"  => 0,
				'setting_root_filename'         => $this->filename,
			)
		);
	}
	
	private function process($stage, array $params)
	{
		$common = array(
			'id'           => $this->course->id,
			'cm'           => $this->cm->id,
			'backup'       => $this->controller->get_backupid(),
			'sesskey'      => $GLOBALS['USER']->sesskey,
			'submitbutton' => '[[Next]]',
		);
		$params += array('stage' => $stage) + $common;
		
		// execute a stage with impersonated parameters
		utils\post_update($params);
		{
			$ui = new \backup_ui($this->controller);
			$ui->process();
			if ($ui->get_stage() == \backup_ui::STAGE_FINAL)
				$ui->execute();
		}
		utils\post_remove($params);
	}
	
	private $course, $cm, $controller, $filename;
}
