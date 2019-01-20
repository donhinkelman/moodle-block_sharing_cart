<?php
/**
 *  Sharing Cart - Block
 *  
 *  @author  VERSION2, Inc.
 *  @version $Id: block_sharing_cart.php 540 2011-11-18 05:56:24Z malu $
 */

require_once __DIR__.'/classes/record.php';
require_once __DIR__.'/classes/tree.php';
require_once __DIR__.'/classes/view.php';

class sharing_cart_renderer implements sharing_cart\tree_renderer
{
	public function __construct()
	{
		$this->dir = array();
		
		$this->texts = (object)array(
			'movedir' => get_string('movedir', 'block_sharing_cart'),
			'move'    => get_string('move'),
			'delete'  => get_string('delete'),
			'restore' => get_string('restore', 'block_sharing_cart'),
		);
		$this->icons = (object)array(
			'open'    => $GLOBALS['OUTPUT']->pix_url('i/open'),
			'movedir' => $GLOBALS['OUTPUT']->pix_url('t/right'),
			'move'    => $GLOBALS['OUTPUT']->pix_url('t/move'),
			'delete'  => $GLOBALS['OUTPUT']->pix_url('t/delete'),
			'restore' => $GLOBALS['OUTPUT']->pix_url('i/restore'),
		);
	}
	public function open($name)
	{
		$depth = count($this->dir);
		array_push($this->dir, $name);
		return '
		<li class="r0 sharing_cart-dir">
			<div title="/' . htmlspecialchars(implode('/', $this->dir)) . '">
				<div class="column c0">' .
					sharing_cart\view\spacer(10 * $depth, 10) .
					'<img src="' . $this->icons->open . '" class="sharing_cart-dir" alt="" />
				</div>
				<div class="column c1">' . htmlspecialchars($name) . '</div>
			</div>
			<ul class="list">';
	}
	public function write($item)
	{
		$depth = count($this->dir);
		return '
				<li class="r0 sharing_cart-item" id="sharing_cart-item-' . $item->id . '">
					<div class="icon column c0">' .
						sharing_cart\view\spacer(10 * $depth, 10) .
						sharing_cart\view\icon($item) .
					'</div>
					<div class="column c1">' . $item->modtext . '</div>
					<span class="commands">' .
						$this->command('movedir') . $this->command('move') .
						$this->command('delete') . $this->command('restore') .
					'</span>
				</li>';
	}
	public function close()
	{
		array_pop($this->dir);
		return '
			</ul>
		</li>';
	}
	private function command($action)
	{
		return '<a title="' . $this->texts->{$action} . '" href="javascript:void(0);"'
		     . ' class="sharing_cart-action-' . $action . '">'
		     . '<img src="' . $this->icons->{$action} . '" class="iconsmall" alt="" />'
		     . '</a>';
	}
	private $dir, $texts, $icons;
}

class block_sharing_cart extends block_base
{
	public function init()
	{
		$this->title   = get_string('pluginname', __CLASS__);
		$this->version = 2011111101;
	}
	
	public function applicable_formats() { return array('all' => true); }
	
	public function has_config() { return false; }
	
	public function get_content()
	{
		global $DB, $CFG, $USER, $COURSE, $OUTPUT;
		
		if ($this->content !== null)
			return $this->content;
		
		if (empty($this->instance) || empty($USER->id))
			return $this->content = '';
		
		$context = get_context_instance(CONTEXT_COURSE, $this->page->course->id);
		if (!$this->page->user_is_editing())
			return $this->content = '';
		
		$tree = new sharing_cart\tree(
			$DB->get_records(sharing_cart\record::TABLE,
				array('userid' => $USER->id))
			);
		$html = '<ul class="list">' . $tree->render(new sharing_cart_renderer()) . '</ul>';
		
		return $this->content = (object)array(
			'text'   => $this->get_script() . $html,
			'footer' => '<div id="sharing_cart-header">' . $this->get_header() . '</div>'
		);
	}
	
	private function get_header()
	{
		$base = $GLOBALS['CFG']->wwwroot . '/blocks/sharing_cart';
		return '<a class="icon" title="' . get_string('bulkdelete', __CLASS__) . '"'
		     . ' href="' . $base . '/bulkdelete.php?course=' . $this->page->course->id . '">'
		     . '<img src="' . $base . '/pix/bulkdelete.gif" alt="" />'
		     . '</a>'
		     . $GLOBALS['OUTPUT']->help_icon('sharing_cart', __CLASS__);
	}
	
	private function get_script()
	{
		$import = $GLOBALS['CFG']->wwwroot . '/blocks/sharing_cart/sharing_cart.js';
		$params = json_encode(array(
			'str' => array(
				'notarget'       => get_string('notarget', __CLASS__),
				'copyhere'       => get_string('copyhere', __CLASS__),
				'movehere'       => get_string('movehere'),
				'edit'           => get_string('edit'),
				'cancel'         => get_string('cancel'),
				'backup'         => get_string('backup', __CLASS__),
				'clipboard'      => get_string('clipboard', __CLASS__),
				'confirm_backup' => get_string('confirm_backup', __CLASS__),
				'confirm_delete' => get_string('confirm_delete', __CLASS__),
			),
			'wwwroot'  => $GLOBALS['CFG']->wwwroot,
			'pix_url'  => strtr($GLOBALS['OUTPUT']->pix_url('movehere'), array('movehere' => '%s')),
			'instance' => (int)$this->instance->id,
			'course'   => (int)$this->page->course->id,
		));
		return <<<SCRIPT
		<script type="text/javascript" src="$import"></script>
		<script type="text/javascript">
		//<![CDATA[
		new sharing_cart($params);
		//]]>
		</script>
SCRIPT;
	}
}
