<?php
/**
 * リポジトリプラグイン設定フォーム
 *
 * @author VERSION2 Inc.
 * @version $Id: Settings.php 430 2009-12-03 09:29:46Z malu $
 * @package repository
 */

require_once dirname(__FILE__).'/../SharingCart_Repository.php';

require_once $CFG->libdir.'/formslib.php';

class SharingCart_Repository_Form_Settings extends moodleform
{
	protected static function getStrings()
	{
		static $str = NULL;
		if (empty($str)) {
			$str           = new stdClass;
			$str->required = get_string('required');
			$str->username = get_string('username');
			$str->password = get_string('password');
			$str->update   = get_string('update');
			$str->sitename = get_string('fullsitename');
			$str->disabled = SharingCart_Repository::getString('disabled');
			$str->url      = SharingCart_Repository::getString('repo_url');
			$str->instance = SharingCart_Repository::getString('instance');
		}
		return $str;
	}
	
	protected $course_id;
	protected $config_id, $info, $updated, $message;
	
	public function __construct($course_id, $config_id, $info, $message = NULL)
	{
		$this->course_id = $course_id;
		$this->config_id = $config_id;
		$this->info      = $info;
		$this->message   = $message;
		
		parent::__construct();
		
		// 複数のフォームを配置するためにフォームIDをユニークにする
		$this->_form->_formName = $this->_formname .= "_$config_id";
	}
	
	public function definition()
	{
		$form =& $this->_form;
		
		$str = self::getStrings();
		
		// MoodleQuickFormの要素は複数フォームに対応していないので
		// 各要素にサフィクスを加えてユニークなIDにする
		$i = $this->config_id;
		
		$form->addElement('hidden', 'course', $this->course_id);
		$form->addElement('hidden', "update[$i]", $this->config_id);
		
		if (!isset($this->info->sitename)) {
			$this->info->sitename = '';
		}
		$form->addElement('header', '', $this->info->sitename);
		
		if ($this->message) {
			$form->addElement('static', NULL, '',
				'<strong>'.htmlspecialchars($this->message).'</strong>');
		}
		
		$form->addElement('checkbox', "disabled$i", $str->disabled);
		$form->setDefault("disabled$i", empty($this->info->enabled));
		
		if (!empty($this->info->instance) and $instance = intval($this->info->instance)) {
			if (!preg_match('@/course/view\.php\?id=\d+$@', $this->info->url)) {
				// RepositoryコースのインスタンスIDを含んだフルURLを生成
				$this->info->url = rtrim($this->info->url, '/').'/course/view.php?id='.$instance;
			}
		}
		$form->addElement('text', "url$i", $str->url, array('size' => 50));
		$form->setDefault("url$i", $this->info->url);
		$form->disabledIf("url$i", "disabled$i", 'checked');
		
		$form->addElement('text', "username$i", $str->username);
		$form->setDefault("username$i", $this->info->username);
		$form->disabledIf("username$i", "disabled$i", 'checked');
		
		$form->addElement('password', "password$i", $str->password);
		$form->disabledIf("password$i", "disabled$i", 'checked');
		
		$form->addElement('submit', "submit$i", $str->update);
		
		require_once dirname(__FILE__).'/script.php';
		$form->addElement('script', self::getScriptHeader()."
			sharing_cart_repository_add_url_desc($i);");
	}
	
	private static function getScriptHeader()
	{
		if (self::$script_header_defined) {
			return '';
		}
		self::$script_header_defined = TRUE;
		return '
			function sharing_cart_repository_add_url_desc(i)
			{
				var item = document.getElementById("id_url" + i).parentNode;
				var desc = document.createElement("div");
				desc.className = "dimmed_text";
				desc.appendChild(document.createTextNode("'.addslashes(
					SharingCart_Repository::getString('repo_url_desc')
				).'"));
				item.appendChild(desc);
			}';
	}
	private static $script_header_defined = FALSE;
}

?>