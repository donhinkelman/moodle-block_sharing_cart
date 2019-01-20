<?php
/**
 *  SharingCart_BackupRestoreBase
 */

require_once dirname(__FILE__).'/SharingCart_Exceptions.php';
require_once dirname(__FILE__).'/SharingCart_FileSystem.php';
require_once dirname(__FILE__).'/SharingCart_DebugTrace.php';

require_once $CFG->dirroot.'/backup/lib.php';
require_once $CFG->libdir.'/adminlib.php';

abstract class SharingCart_BackupRestoreBase
{
	// 標準のコースファイルエスケープ記号
	const LINK_FILE_ESC = '$@FILEPHP@$';
	
	// 独自リンクエスケープ記号
	const LINK_SITE_ESC = '!~SITE~!';
	const LINK_MISC_ESC = '!~MISC~!';
	
	// 独自バックアップファイルディレクトリ (.zip内)
	const LINK_MISC_DIR = 'misc';
	
	// 抽象メソッド (継承先で実装)
	abstract protected function requireCapabilities($course_id); /* @return void */
	abstract protected function & createPreferences(); /* @return stdClass */
	
	protected $execute_succeeded;
	protected $prefs;
	protected $course;
	protected $section;
	
	protected $debug;
	
	protected function __construct($course_id, $section_i)
	{
		global $CFG;
		
		$this->debug = new SharingCart_DebugTrace();
		$this->debug->trace();
		
		$this->execute_succeeded = FALSE;
		
		require_login($course_id);
		
		// 権限チェック
		$this->requireCapabilities($course_id);
		$this->debug->trace('Capabilities - OK');
		
		// 必要な関数が使用可能かチェック
		backup_required_functions();
		
		// このタイミングで各モジュールのテーブルをアップグレード
		$return_to = $_SERVER['REQUEST_URI'];
		upgrade_backup_db($return_to);
		
		// 設定オブジェクトを生成
		$this->prefs =& $this->createPreferences();
		
		// ユニーク値をセット (Moodleコアはここにtime()が入っているのを期待しているのでそれに従う)
		$this->prefs->backup_unique_code = time();
		
		// コースを取得
		$this->course = get_record('course', 'id', $course_id);
		if (!$this->course)
			throw new SharingCart_CourseException('Invalid course');
		
		// セクションを取得
		$this->section = get_record('course_sections', 'course', $course_id, 'section', $section_i);
		if (!$this->section)
			throw new SharingCart_SectionException('Invalid section');
	}
	
	public function succeeded()
	{
		return $this->execute_succeeded;
	}
	
	public function getCourse()
	{
		return $this->course;
	}
	public function getSection()
	{
		return $this->section;
	}
	
	public function setParam($key, $value)
	{
		$this->prefs->$key = $value;
	}
	public function getParam($key)
	{
		return isset($this->prefs->$key) ? $this->prefs->$key : NULL;
	}
	
	public function getUnique()
	{
		return $this->prefs->backup_unique_code;
	}
	public function getTempDir()
	{
		return $GLOBALS['CFG']->dataroot.'/temp/backup/'.$this->getUnique();
	}
	
	protected function prepareDir()
	{
		$this->debug->trace();
		
		// 一時ディレクトリを作成して中身を空にする
		if (!check_and_create_backup_dir($this->getUnique()) || !clear_backup_dir($this->getUnique()))
			throw new SharingCart_Exception('Backup directory creation failure');
		
		// 残っている古い(=4時間以上前の)バックアップデータを削除
		if (!backup_delete_old_data())
			throw new SharingCart_Exception('Old data deletion failure');
	}
	
	protected function cleanupDir()
	{
		$this->debug->trace();
		
		// クリーンアップ
		if (!clean_temp_data($this->prefs))
			throw new SharingCart_Exception('Cleanup failure');
	}
	
	protected static function silent_call($function /*[, $args...]*/)
	{
		$args = func_get_args(); array_shift($args);
		
		// PHP警告を一時的に抑制
		$prev_error_level = error_reporting(error_reporting() & ~E_NOTICE & ~E_STRICT);
		$retval = call_user_func_array($function, $args);
		error_reporting($prev_error_level);
		
		return $retval;
	}
}

?>