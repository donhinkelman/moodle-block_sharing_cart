<?php
/**
 *  SharingCart_Restore
 */

require_once dirname(__FILE__).'/SharingCart_BackupRestoreBase.php';

require_once $CFG->dirroot.'/backup/restorelib.php';
require_once $CFG->libdir.'/xmlize.php';

class SharingCart_Restore extends SharingCart_BackupRestoreBase
{
	/* implements */ protected function requireCapabilities($course_id)
	{
		$context = get_context_instance(CONTEXT_COURSE, $course_id);
		require_capability('moodle/site:restore', $context);
		require_capability('moodle/course:manageactivities', $context);
	}
	/* implements */ protected function & createPreferences()
	{
		// リストアに必要なデータを保持するオブジェクト
		// Moodle コアがグローバル変数として参照するので変数名は $restore 固定
		// (restore_decode_absolute_links() @ /backup/restorelib.php)
		$GLOBALS['restore'] = new stdClass;
		
		return $GLOBALS['restore'];
	}
	
	protected $zip_dir, $zip_name;
	
	protected $opt_section_status = FALSE;
	protected $opt_modules_status = FALSE;
	
	protected $restored_cmids = array();
	
	public function __construct($course_id, $section_i)
	{
		parent::__construct($course_id, $section_i);
		
		// 初期値設定
		// true|false ではなく 1|0 で指定
		// マジックナンバーはコメント参照 (無いものは未調査)
		$this->prefs->course_id          = $this->course->id;
		$this->prefs->section            = $this->section->id;
		$this->prefs->restoreto          = 1; // 既存コース (2: コースを新規作成)
		$this->prefs->metacourse         = 0;
		$this->prefs->users              = 2; // (0:全て, 1:必要な分だけ, 2:なし)
		$this->prefs->logs               = 0;
		$this->prefs->user_files         = 0;
		$this->prefs->course_files       = 1;
		$this->prefs->site_files         = 1;
		$this->prefs->gradebook_history  = 0;
		$this->prefs->messages           = 0;
		$this->prefs->newdirectoryname   = NULL;
		
		if ($this->course->format == 'project') {
			// project フォーマットの場合はリストア先のセクションディレクトリ名を取得
			if (!is_file($project_lib = $GLOBALS['CFG']->dirroot.'/course/format/project/lib.php'))
				throw new SharingCart_Exception('Project format not installed');
			require_once $project_lib;
			
			$project_title = project_format_get_title($this->course, $this->section->id, $this->section->section);
			if (!$project_title)
				throw new SharingCart_SectionException('Project format section title not found');
			$this->prefs->newdirectoryname = $project_title->directoryname;
		}
		
		$this->prefs->course_startdateoffset = 0;
		$this->prefs->course_shortname       = NULL;
	}
	
	public function setSilent()
	{
		// 途中コアライブラリがHTMLを出力しないようにサイレントモードにする
		define('RESTORE_SILENTLY', TRUE);
		define('RESTORE_SILENTLY_NOFLUSH', TRUE);
		
		$GLOBALS['CFG']->debug = FALSE;
	}
	
	public function beginPreferences()
	{
		$this->debug->trace();
		
		// ZIPを探すディレクトリの初期値にユーザディレクトリをセット
		$this->setZipDir(make_user_directory($GLOBALS['USER']->id, TRUE));
	}
	
	public function setRestoreSectionStatus($bool)
	{
		$this->opt_section_status = $bool;
	}
	public function setRestoreModulesStatus($bool)
	{
		$this->opt_modules_status = $bool;
	}
	
	public function endPreferences()
	{
		$this->debug->setVerticalDepth(1);
		$this->debug->trace($this->prefs);
		$this->debug->setVerticalDepth(0);
	}
	
	public function setZipDir($dir)
	{
		$this->zip_dir = $dir;
	}
	
	public function setZipName($name)
	{
		$this->zip_name = $name;
	}
	
	public function getCourseModuleIds()
	{
		return $this->restored_cmids;
	}
	public function getCourseModuleId()
	{
		if (empty($this->restored_cmids)) {
			return NULL;
		}
		return reset($this->restored_cmids);
	}
	
	public function execute()
	{
		$this->debug->trace();
		
		$this->prepareDir();
		
		$this->unzip();
		
		$temp_dir = $this->getTempDir().'/';
		
		// moodle.xml 内を検索し、独自のリンクエスケープをデコードして書き戻す
		$xml = file_get_contents($temp_dir.'moodle.xml');
		$xml = $this->decodeLinks($xml);
		file_put_contents($temp_dir.'moodle.xml', $xml);
		
		$this->parseXml();
		
		// ファイルのリストア
		$this->restoreFiles();
		
		$this->cleanupDir();
		
		// コースキャッシュ再構築
		rebuild_course_cache($this->course->id);
		
		// リストアは出力を完全に抑制できるので、HTTPヘッダの送出状態で成功かどうか判断
		if (!headers_sent()) {
			$this->execute_succeeded = TRUE;
		}
	}
	
	protected function unzip()
	{
		$this->debug->trace();
		
		// 一時ディレクトリにZIPを展開
		$temp_dir = $this->getTempDir().'/';
		$temp_zip = $temp_dir.$this->zip_name;
		
		if (!backup_copy_file($this->zip_dir.'/'.$this->zip_name, $temp_zip))
			throw new SharingCart_Exception('Unzip failure - backup_copy_file("'.$this->zip_dir.'/'.$this->zip_name.'", "'.$temp_zip.'")');
		if (!restore_unzip($temp_zip))
			throw new SharingCart_Exception('Unzip failure');
		
		// blackboard の変換
		global $CFG;
		require_once $CFG->dirroot.'/backup/bb/restore_bb.php';
		if (!blackboard_convert($temp_dir))
			throw new SharingCart_Exception('Blackboard conversion failure');
	}
	
	protected function parseXml()
	{
		$this->debug->trace();
		
		global $CFG;
		
		// moodle.xml チェック
		$xml_file = $this->getTempDir().'/moodle.xml';
		if (!restore_check_moodle_file($xml_file))
			throw new SharingCart_XmlException('Invalid moodle.xml');
		
		// XML をオブジェクトに読込
		$xml       = new stdClass;
		$xml->info = restore_read_xml_info($xml_file);
		if (!$xml->info)
			throw new SharingCart_XmlException('XML reading failure');
		
		// バージョンチェック
		if ($CFG->version < $xml->info->backup_moodle_version)
			throw new SharingCart_XmlException('Version mismatch');
		
		// 必要な変数をリストア設定オブジェクトにコピー
		$property_map = array(
			'backup_moodle_version'
				=> 'backup_version',
			'original_wwwroot'
				=> 'original_wwwroot',
			'original_siteidentifier'
				=> 'original_siteidentifier',
		);
		foreach ($property_map as $name => $prop) {
			$this->setParam($prop, $xml->info->$name);
		}
		
		// コースヘッダ
		$xml->course_header = restore_read_xml_course_header($xml_file);
		if (!$xml->course_header)
			throw new SharingCart_XmlException('Course header reading failure');
		
		// 問題バンク
		$this->restoreQuestions();
		
		// モジュール
		$this->prefs->mods = array();
		if (!empty($xml->info->mods)) {
			foreach ($xml->info->mods as $name => $mod) {
				$this->prefs->mods[$name]            = new stdClass;
				$this->prefs->mods[$name]->restore   = ($mod->backup   == 'true');
				$this->prefs->mods[$name]->userinfo  = ($mod->userinfo == 'true');
				$this->prefs->mods[$name]->instances = array();
				
				// モジュール個別のリストアライブラリをインクルード
				if ($this->prefs->mods[$name]->restore) {
					$mod_restorelib = "$CFG->dirroot/mod/$name/restorelib.php";
					if (is_file($mod_restorelib)) {
						require_once $mod_restorelib;
					}
				}
				
				// モジュールインスタンス
				if (!empty($mod->instances)) {
					foreach ($mod->instances as $inst) {
						$this->prefs->mods[$name]->instances[$inst->id]           = new stdClass;
						$this->prefs->mods[$name]->instances[$inst->id]->restore  = ($inst->backup   == 'true');
						$this->prefs->mods[$name]->instances[$inst->id]->userinfo = ($inst->userinfo == 'true');
					}
				}
			}
		}
		
		// モジュールをリストア
		if (!restore_create_modules($this->prefs, $xml_file))
			throw new SharingCart_XmlException('Modules creation failure');
		if (!restore_check_instances($this->prefs))
			throw new SharingCart_XmlException('Modules check failure');
		
		// リンクの張り直し (Moodle 標準)
		if (!restore_decode_content_links($this->prefs))
			throw new SharingCart_XmlException('Links decode failure');
		
		// セクションをリストア
		$this->restoreSection();
		
		// Grade category のリストア (course_module 作成後に呼び出す)
		if (!restore_create_gradebook($this->prefs, $xml_file))
			throw new SharingCart_XmlException('Gradebook creation failure');
	}
	
	protected function restoreQuestions()
	{
		$this->debug->trace();
		
		global $CFG;
		require_once $CFG->dirroot.'/question/restorelib.php';
		
		// Moodle 1.9.4 ～ 1.9.5 において、以下の問題形式はバックアップ/リストア機能の実装が不十分
		// ・穴埋め問題
		// ・組み合わせ問題
		// ・ランダム記述組み合わせ問題
		
		// オーバーライド動作確認済みなのは 1.9.5 (2007101550) までだが、
		// 全てをチェックすることは事実上不可能なので、1.9.x は一律オーバーライドする
		if (2007101540 <= $CFG->version &&
		                  $CFG->version <= 2007101599)
		{
			require_once dirname(__FILE__).'/qtype/SharingCart_qtype_cloze.php';
			require_once dirname(__FILE__).'/qtype/SharingCart_qtype_match.php';
			require_once dirname(__FILE__).'/qtype/SharingCart_qtype_randomsamatch.php';
		}
		
		$xml_file = $this->getTempDir().'/moodle.xml';
		if (!restore_create_questions($this->prefs, $xml_file))
			throw new SharingCart_XmlException('Create questions failure');
	}
	
	protected function restoreSection()
	{
		$this->debug->trace();
		
		// コースモジュールIDマップ ("モジュール名:インスタンスID" => コースモジュールID)
		$inst2cmid_map = array();
		
		// モジュールをコースに登録
		foreach ($this->prefs->mods as $name => $mod) {
			$module = get_record('modules', 'name', $name);
			
			foreach ($mod->instances as $old_id => $inst) {
				$backup_id = get_record('backup_ids', 'backup_code', $this->getUnique(),
					'table_name', $name, 'old_id', $old_id);
				
				$course_module                   = new stdClass;
				$course_module->course           = $this->course->id;
				$course_module->module           = $module->id;
				$course_module->instance         = $backup_id->new_id;
				$course_module->section          = $this->section->id;
				$course_module->added            = $this->getUnique();
				$course_module->score            = 0;
				$course_module->indent           = 0;
				$course_module->visible          = 1;
				$course_module->groupmode        = 0;
				$course_module->groupingid       = 0;
				$course_module->groupmembersonly = 0;
				
				$course_module->id = insert_record('course_modules', $course_module);
				if (!$course_module->id)
					throw new SharingCart_ModuleException('Course module record insertion failure');
				
				// コースモジュールIDマップに追加
				$inst2cmid_map["$name:$old_id"] = $course_module->id;
			}
		}
		
		if ($info = restore_read_xml_sections($this->getTempDir().'/moodle.xml')) {
			// 旧バージョンの共有ライブラリによるバックアップには
			// セクション情報が格納されていないので存在をチェック
			if (!empty($info->sections) and $section = reset($info->sections)) {
				if (!empty($section->mods)) {
					// バックアップ時の並び順にコースモジュールIDを並べ直す
					$ordered_cmids = array();
					foreach ($section->mods as $mod) {
						if (empty($inst2cmid_map["$mod->type:$mod->instance"]))
							throw new SharingCart_XmlException('Section modules mismatch');
						$ordered_cmids[] = $inst2cmid_map["$mod->type:$mod->instance"];
						
						// コースモジュールの状態(非表示、インデントなど)を復元
						if ($this->opt_modules_status) {
							// 全て (セクションリストア時)
							$fields = array(
								'idnumber', 'visible', 'groupmode', 'score', 'indent', 'groupmembersonly',
							);
						} else {
							// Common Module Settings のみ
							$fields = array(
								'idnumber', 'visible', 'groupmode',
							);
						}
						$course_module = get_record('course_modules', 'id', end($ordered_cmids));
						foreach ($fields as $field) {
							$course_module->$field = $mod->$field;
						}
						update_record('course_modules', $course_module);
					}
					if (count($ordered_cmids) != count($inst2cmid_map))
						throw new SharingCart_XmlException('Section modules mismatch');
					$inst2cmid_map = $ordered_cmids;
				}
				if ($this->opt_section_status) {
					// セクションの状態(非表示、サマリなど)を復元
					$fields = array(
						'summary', 'visible',
					);
					foreach ($fields as $field) {
						$this->section->$field = addslashes($section->$field);
					}
					update_record('course_sections', $this->section);
				}
			}
		}
		
		// array_merge で連結するために連想配列から単純配列に変換
		$cmids = array_values($inst2cmid_map);
		
		// セクションシーケンスを更新
		$section_sequence = explode(',', $this->section->sequence);
		$section_sequence = array_merge($section_sequence, $cmids);
		$section_sequence = array_filter($section_sequence);
		$this->section->sequence = implode(',', $section_sequence);
		// NOTE: update_record() する場合は summary を addslashes() でエスケープする必要あり
		if (!set_field('course_sections', 'sequence', $this->section->sequence, 'id', $this->section->id))
			throw new SharingCart_SectionException('Course section module sequence update failure');
		
		// リストア済みのコースモジュールIDリストを保持
		$this->restored_cmids = $cmids;
	}
	
	/**
	 * 独自のリンクエスケープを書き戻し
	 */
	protected function decodeLinks($xml)
	{
		global $CFG;
		
		$file_php = $CFG->slasharguments ? 'file.php' : 'file.php?file=';
		
		// サイトファイル
		$xml = preg_replace(
			'/'.preg_quote(parent::LINK_SITE_ESC, '/').'/',
			$CFG->wwwroot.'/'.$file_php.'/'.SITEID,
			$xml
		);
		
		if ($this->prefs->newdirectoryname) {
			// リストア先セクションディレクトリを書き換え＆一時ディレクトリ上のファイルを移動
			$xml = preg_replace_callback(
				'@(&quot;'.preg_quote(parent::LINK_FILE_ESC, '@').'/)(.+?)(&quot;)@',
				array($this, 'decodeLinks_clbk'),
				$xml
			);
			$xml = preg_replace_callback(
				'@(<REFERENCE>)(.+?)(</REFERENCE>)@',
				array($this, 'decodeLinks_clbk'),
				$xml
			);
		}
		
		// 自身以外のコースファイル
		$xml = preg_replace_callback(
			'@(&quot;)'.preg_quote(parent::LINK_MISC_ESC, '@').'/(\d+)/(.+?)(&quot;)@',
			array($this, 'decodeLinks_clbk_misc'),
			$xml
		);
		
		return $xml;
	}
	protected function decodeLinks_clbk($m)
	{
		list ($all, $prefix, $source, $suffix) = $m;
		
		// 絶対パスが指定されている場合は変換しない
		if (preg_match('@^\w+?\://@', $source)) {
			return $all;
		}
		
		$target = $this->prefs->newdirectoryname.'/'.$source;
		
		$root = $this->getTempDir().'/course_files/';
		
		// 一時ディレクトリ内でコースファイルをセクションディレクトリに移動
		SharingCart_FileSystem::move(
			$root.$source,
			$root.$target,
			SharingCart_FileSystem::OVERWRITE
		);
		
		return $prefix . $target . $suffix;
	}
	protected function decodeLinks_clbk_misc($m)
	{
		global $CFG;
		
		list ($all, $prefix, $course_id, $source, $suffix) = $m;
		
		$file_php = $CFG->slasharguments ? 'file.php' : 'file.php?file=';
		
		if (file_exists($CFG->dataroot.'/'.$course_id.'/'.$source)) {
			// 参照先のコースファイルが存在する場合は、そのままリンクを維持
			return $CFG->wwwroot.'/'.$file_php.'/'.$course_id.'/'.$source;
		}
		
		// 参照先が存在しない場合は自身のコースファイルへコピーし、リンク書き換え
		SharingCart_FileSystem::copy(
			$this->getTempDir().'/'.parent::LINK_MISC_DIR.'/'.$course_id.'/'.$source,
			$this->getTempDir().'/course_files/'.$source,
			SharingCart_FileSystem::NEWERONLY
		);
		return $CFG->wwwroot.'/'.$file_php.'/'.$this->course->id.'/'.$source;
	}
	
	/**
	 * ファイルをリストア
	 */
	protected function restoreFiles()
	{
		$this->debug->trace();
		
		$this->excludeUnnecessaryDuplications();
		
		// サイトファイル
		SharingCart_FileSystem::copy(
			$this->getTempDir().'/site_files',
			$GLOBALS['CFG']->dataroot.'/'.SITEID,
			SharingCart_FileSystem::RECURSIVE
		);
		
		// コースファイル
		SharingCart_FileSystem::copy(
			$this->getTempDir().'/course_files',
			$GLOBALS['CFG']->dataroot.'/'.$this->course->id,
			SharingCart_FileSystem::RECURSIVE
		);
	}
	protected function excludeUnnecessaryDuplications()
	{
		$this->debug->trace();
		
		global $CFG;
		
		if ($this->prefs->original_wwwroot != $CFG->wwwroot) {
			// サイトが異なる場合は同一のID値でリストアされる可能性があるのでファイルを除外しない
			return;
		}
		
		// 問題が新規作成されなかった場合、project フォーマットのリストア先セクションディレクトリに
		// メディアファイルをコピーすると参照されることなく冗長となってしまうので、ここで除外する
		
		if ($section_name = $this->prefs->newdirectoryname) {
			// 複製されなかった問題
			$no_dup_course_questions = get_records_sql("
				SELECT q.*
					FROM {$CFG->prefix}question            q
					   , {$CFG->prefix}backup_ids          bk
					   , {$CFG->prefix}question_categories qc
					WHERE bk.backup_code = ".$this->getUnique()."
					  AND bk.table_name  = 'question'
					  AND bk.new_id      = bk.old_id
					  AND q.id           = bk.new_id
					  AND q.category     = qc.id
					  AND qc.contextid  != ".SYSCONTEXTID."
			");
			if ($no_dup_course_questions) {
				$no_dup_course_question_ids = array_keys($no_dup_course_questions);
				
				$xml = file_get_contents($this->getTempDir().'/moodle.xml');
				
				// ネストした内側にはマッチさせずに <QUESTION> ノードで分割
				$question_tag_split_regex = '@
					<QUESTION>(
						(?:
							(?> (?: (?! </?QUESTION>). )* )
							|
							(?R)
						)*
					)</QUESTION>
				@xs';
				$components = preg_split($question_tag_split_regex, $xml, -1, PREG_SPLIT_DELIM_CAPTURE);
				
				$file_ref_regexes = array(
					'@&quot;'.preg_quote(parent::LINK_FILE_ESC, '@').'/'.preg_quote($section_name, '@').'/(.+?)&quot;@',
					'@<REFERENCE>'.preg_quote($section_name, '@').'/(.+?)</REFERENCE>@',
				);
				
				// コピー不要なファイルを列挙
				$exclude_flags = array();
				foreach ($components as $i => $s) {
					if ($i & 1) {
						// 奇数インデックス：マッチ
						if (preg_match('@^\s*<ID>(?:'.implode('|', $no_dup_course_question_ids).')</ID>@', $s)) {
							// 複製されなかった問題 → 除外フラグを立てる
							self::findLinksAndSetFlags($exclude_flags, $file_ref_regexes, $s, TRUE);
						} else {
							// 複製された問題       → 除外フラグを消す
							self::findLinksAndSetFlags($exclude_flags, $file_ref_regexes, $s, FALSE);
						}
					} else {
						// 偶数インデックス：非マッチ
						// (QUESTION以外のファイル) → 除外フラグを消す
						self::findLinksAndSetFlags($exclude_flags, $file_ref_regexes, $s, FALSE);
					}
				}
				$exclude_files = array_keys(array_filter($exclude_flags));
				
				// コピー不要なファイルを一時ディレクトリから削除
				$root = $this->getTempDir().'/course_files/'.$section_name.'/';
				foreach ($exclude_files as $file) {
					SharingCart_FileSystem::remove($root.$file);
				}
				SharingCart_FileSystem::rmdir($root);
			}
		}
	}
	protected static function findLinksAndSetFlags(&$flags, $regexes, $s, $set)
	{
		foreach ($regexes as $re) {
			if (preg_match_all($re, $s, $m)) {
				foreach ($m[1] as $file) {
					$flags[$file] = $set && !isset($flags[$file]);
				}
			}
		}
	}
}

?>