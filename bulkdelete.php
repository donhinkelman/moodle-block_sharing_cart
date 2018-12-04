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
 *  Sharing Cart - Bulk Delete Operation
 *
 *  @package    block_sharing_cart
 *  @copyright  2017 (C) VERSION2, INC.
 *  @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once '../../config.php';

require_once __DIR__.'/classes/storage.php';
require_once __DIR__.'/classes/record.php';
require_once __DIR__.'/classes/renderer.php';

if (false) {
    $DB     = new mysqli_native_moodle_database;
    $CFG    = new stdClass;
    $USER   = new stdClass;
    $PAGE   = new moodle_page;
    $OUTPUT = new core_renderer;
}

$courseid = required_param('course', PARAM_INT);
$returnurl = new moodle_url('/course/view.php', array('id' => $courseid));

require_login($courseid);

$delete_param = function_exists('optional_param_array')
	? optional_param_array('delete', null, PARAM_RAW)
	: optional_param('delete', null, PARAM_RAW);
if (is_array($delete_param)) try {
	
	confirm_sesskey();
	set_time_limit(0);
	
	$delete_ids = array_map('intval', array_keys($delete_param));
	
	list ($sql, $params) = $DB->get_in_or_equal($delete_ids);
	$records = $DB->get_records_select(sharing_cart\record::TABLE, "userid = $USER->id AND id $sql", $params);
	if (!$records)
		throw new sharing_cart\exception('recordnotfound');
	
	$storage = new sharing_cart\storage();
	
	$deleted_ids = array();
	foreach ($records as $record) {
		$storage->delete($record->filename);
		$deleted_ids[] = $record->id;
	}
	
	list ($sql, $params) = $DB->get_in_or_equal($deleted_ids);
	$DB->delete_records_select(sharing_cart\record::TABLE, "id $sql", $params);
	
	sharing_cart\record::renumber($USER->id);
	
	redirect($returnurl);
} catch (sharing_cart\exception $ex) {
	print_error($ex->errorcode, $ex->module, $returnurl, $ex->a);
} catch (Exception $ex) {
	if (!empty($CFG->debug) and $CFG->debug >= DEBUG_DEVELOPER) {
		print_error('notlocalisederrormessage', 'error', '', $ex->__toString());
	} else {
		print_error('unexpectederror', 'block_sharing_cart', $returnurl);
	}
}

$orderby = 'tree,weight,modtext';
if ($DB->get_dbfamily() == 'mssql' || $DB->get_dbfamily() == 'oracle') {
	// SQL Server and Oracle do not support ordering by TEXT field.
	$orderby = 'tree,weight,CAST(modtext AS VARCHAR(255))';
}
$items = $DB->get_records(sharing_cart\record::TABLE, array('userid' => $USER->id), $orderby);

$title = get_string('bulkdelete', 'block_sharing_cart');

$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/sharing_cart/bulkdelete.php', array('course' => $courseid));
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add(get_string('pluginname', 'block_sharing_cart'))->add($title, '');

echo $OUTPUT->header();
{
	echo $OUTPUT->heading($title);
	
	echo '
	<div style="width:100%; text-align:center;">';
	if (empty($items)) {
		echo '
		<div>
			<input type="button" onclick="history.back();" value="', get_string('back'), '" />
		</div>';
	} else {
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
				return confirm("', s(
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
		<form action="', $PAGE->url->out_omit_querystring(), '"
		 method="post" id="form" onsubmit="return confirm_delete_selected();">
		<input type="hidden" name="sesskey" value="', s(sesskey()), '" />
		<div style="display:none;">
			' . html_writer::input_hidden_params($PAGE->url) . '
		</div>
		<div><label style="cursor:default;">
			<input type="checkbox" checked="checked" onclick="check_all(this);"
			 style="height:16px; vertical-align:middle;" />
			<span>', get_string('selectall'), '</span>
		</label></div>';
		
		$i = 0;
		echo '
		<ul style="list-style-type:none; float:left;">';
		foreach ($items as $id => $item) {
			echo '
			<li style="list-style-type:none; clear:left;">
				<input type="checkbox" name="delete['.$id.']" checked="checked" onclick="check();"
				 style="float:left; height:16px;" id="delete_'.$id.'" />
				<div style="float:left;">', sharing_cart\renderer::render_modicon($item), '</div>
				<div style="float:left;">
					<label for="delete_'.$id.'">', format_string($item->modtext), '</label>
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
			<input type="button" onclick="history.back();" value="', s(get_string('cancel')), '" />
			<input type="submit" name="delete_checked" value="', s(get_string('deleteselected')), '" />
		</div>
		</form>';
	}
	echo '
	</div>';
}
echo $OUTPUT->footer();
