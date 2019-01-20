<?php

require_once '../../config.php';
require_once './sharing_cart_table.php';
require_once './sharing_cart_lib.php';

//error_reporting(E_ALL);

require_login();

$course_id = required_param('course', PARAM_INT);
$return_to = $CFG->wwwroot.'/course/view.php?id='.$course_id;

// 続行可能な通知メッセージがあれば直接リダイレクトせずにそれを表示
$notifications = array();

if (is_array($delete = optional_param('delete'))) {
	// 削除実行
	
	// SQLインジェクション対策のためintvalでフィルタリング
	$delete_ids = array_map('intval', array_keys($delete))
		or print_error('err_shared_id', 'block_sharing_cart', $return_to);
	
	$items = get_records_select('sharing_cart',
	                            'userid = '.$USER->id.' AND '.
	                            'id IN ('.implode(',', $delete_ids).')')
		or print_error('err_shared_id', 'block_sharing_cart', $return_to);
	
	$user_dir = make_user_directory($USER->id, true);
	
	// ファイル削除に成功したIDのみをDB削除に渡す
	$delete_ids = array();
	foreach ($items as $id => $item) {
		if (@unlink($user_dir.'/'.$item->filename)) {
			$delete_ids[] = $id;
		} else {
			$notifications[] = get_string('err_delete', 'block_sharing_cart');
		}
	}
	delete_records_select('sharing_cart', 'id IN ('.implode(',', $delete_ids).')');
	
	sharing_cart_table::renumber($USER->id);
	
	
	if (count($notifications)) {
		notice(implode('<br />', $notifications), $return_to);
	} else {
		redirect($return_to);
	}
	exit;
}

$title = get_string('bulkdelete', 'block_sharing_cart');

$navlinks = array();
if ($course_id != SITEID) {
	$navlinks[] = array(
		'name' => get_field('course', 'shortname', 'id', $course_id),
		'link' => $CFG->wwwroot.'/course/view.php?id='.$course_id,
		'type' => 'title'
	);
}
$navlinks[] = array(
	'name' => $title,
	'link' => '',
	'type' => 'title'
);
print_header_simple($title, '', build_navigation($navlinks));
{
	print_heading($title);
	
	echo '
	<div style="width:100%; text-align:center;">';
	if ($items = get_records('sharing_cart', 'userid', $USER->id, 'tree,weight,modtext')) {
		echo '
		<script type="text/javascript">
		//<![CDATA[
			function get_checks()
			{
				var els = document.forms["form"].elements;
				var ret = new Array();
				for (var i = 0; i < els.length; i++) {
					var el = els[i];
					if (el.type == "checkbox" && el.name.match(/^delete\b/)) {
						ret.push(el);
					}
				}
				return ret;
			}
			function check_all(check)
			{
				var checks = get_checks();
				for (var i = 0; i < checks.length; i++) {
					checks[i].checked = check.checked;
				}
				document.forms["form"].elements["delete_checked"].disabled = !check.checked;
			}
			function confirm_delete_selected()
			{
				return confirm("', htmlspecialchars(
					get_string('confirm_delete_selected', 'block_sharing_cart')
				), '");
			}
			function check()
			{
				var delete_checked = document.forms["form"].elements["delete_checked"];
				var checks = get_checks();
				for (var i = 0; i < checks.length; i++) {
					if (checks[i].checked) {
						delete_checked.disabled = false;
						return;
					}
				}
				delete_checked.disabled = true;
			}
		//]]>
		</script>
		<form action="', $CFG->wwwroot.'/blocks/sharing_cart/bulkdelete.php"
		 method="post" id="form" onsubmit="return confirm_delete_selected();">
		<div style="display:none;">
			<input type="hidden" name="course" value="', $course_id, '" />
		</div>
		<div><label style="cursor:default;">
			<input type="checkbox" checked="checked" onclick="check_all(this);"
			 style="height:16px; vertical-align:middle;" />
			<span>', get_string('checkall'), '</span>
		</label></div>';
		
		$i = 0;
		echo '
		<ul style="list-style-type:none; float:left;">';
		foreach ($items as $id => $item) {
			echo '
			<li style="clear:left;">
				<input type="checkbox" name="delete['.$id.']" checked="checked" onclick="check();"
				 style="float:left; height:16px;" id="delete_'.$id.'" />
				<div style="float:left;">', sharing_cart_lib::get_icon($item->modname, $item->modicon), '</div>
				<div style="float:left;">
					<label for="delete_'.$id.'">', htmlspecialchars($item->modtext), '</label>
				</div>
			</li>';
			if (++$i % 10 == 0) {
				echo '
		</ul>
		<ul style="list-style-type:none; float:left;">';
			}
		}
		echo '
		</ul>';
		
		echo '
		<div style="clear:both;"><!-- clear floating --></div>
		<div>
			<input type="button" onclick="history.back();" value="', get_string('cancel'), '" />
			<input type="submit" name="delete_checked" value="', get_string('deleteselected'), '" />
		</div>
		</form>';
	} else {
		echo '
		<div>
			<input type="button" onclick="history.back();" value="', get_string('back'), '" />
		</div>';
	}
	echo '
	</div>';
}
print_footer();
