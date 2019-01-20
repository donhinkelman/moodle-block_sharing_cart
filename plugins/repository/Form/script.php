<?php
/**
 * MoodleQuickForm用JavaScript埋め込みクラス
 */

global $CFG;
require_once $CFG->libdir.'/formslib.php';
require_once $CFG->libdir.'/form/static.php';

class MoodleQuickForm_script extends MoodleQuickForm_static
{
	/**
	 * コンストラクタ
	 */
	public function MoodleQuickForm_script($script = '', $import = '')
	{
		$this->script = $script;
		$this->import = $import;
	}
	
	/**
	 * HTML 生成
	 */
	public function toHtml()
	{
		$html = '';
		if (!empty($this->import)) {
			$html .= '
<script type="text/javascript" src="'.$this->import.'"></script>';
		}
		$html .= '
<script type="text/javascript">
//<![CDATA[
'.$this->script.'
//]]>
</script>
';
		return $html;
	}
	
	protected $script = '';
	protected $import = '';
}
MoodleQuickForm::registerElementType('script', __FILE__, 'MoodleQuickForm_script');

?>