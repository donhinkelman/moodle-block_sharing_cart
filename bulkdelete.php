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
 * @package    block_sharing_cart
 * @copyright  2017 (C) VERSION2, INC.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_sharing_cart\exception as sharing_cart_exception;
use block_sharing_cart\record;
use block_sharing_cart\renderer;
use block_sharing_cart\storage;

require_once '../../config.php';

$PAGE->requires->css('/blocks/sharing_cart/custom.css');
$PAGE->requires->js_call_amd('block_sharing_cart/bulkdelete', 'init');
$PAGE->requires->strings_for_js(
        array(
                'modal_bulkdelete_title', 'modal_bulkdelete_confirm'
        ),
        'block_sharing_cart'
);

$courseid = required_param('course', PARAM_INT);
$returnurl = new moodle_url('/course/view.php', array('id' => $courseid));

require_login($courseid);

$delete_param = function_exists('optional_param_array')
        ? optional_param_array('delete', null, PARAM_RAW)
        : optional_param('delete', null, PARAM_RAW);

if (is_array($delete_param)) {
    try {

        confirm_sesskey();
        set_time_limit(0);

        $delete_ids = array_map('intval', array_keys($delete_param));

        list ($sql, $params) = $DB->get_in_or_equal($delete_ids);
        $records = $DB->get_records_select(record::TABLE, "userid = $USER->id AND id $sql", $params);
        if (!$records) {
            throw new sharing_cart_exception('recordnotfound');
        }

        $storage = new storage();

        $deleted_ids = array();
        foreach ($records as $record) {
            $storage->delete($record->filename);
            $deleted_ids[] = $record->id;
        }

        list ($sql, $params) = $DB->get_in_or_equal($deleted_ids);
        $DB->delete_records_select(record::TABLE, "id $sql", $params);

        record::renumber($USER->id);

        redirect($returnurl);
    } catch (sharing_cart_exception $ex) {
        print_error($ex->errorcode, $ex->module, $returnurl, $ex->a);
    } catch (Exception $ex) {
        if (!empty($CFG->debug) && $CFG->debug >= DEBUG_DEVELOPER) {
            print_error('notlocalisederrormessage', 'error', '', $ex->__toString());
        } else {
            print_error('unexpectederror', 'block_sharing_cart', $returnurl);
        }
    }
}

$orderby = 'tree,weight,modtext';
if ($DB->get_dbfamily() == 'mssql' || $DB->get_dbfamily() == 'oracle') {
    // SQL Server and Oracle do not support ordering by TEXT field.
    $orderby = 'tree,weight,CAST(modtext AS VARCHAR(255))';
}
$items = $DB->get_records(record::TABLE, array('userid' => $USER->id), $orderby);

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
	    <div style="width:100%; text-align:center;">
	';
    if (empty($items)) {
        echo '
		<div>
			<input type="button" class="btn btn-primary" onclick="history.back();" value="', get_string('back'), '" />
		</div>';
    } else {
        echo '
		<form action="', $PAGE->url->out_omit_querystring(), '"
		 method="post" id="form">
		<input type="hidden" name="sesskey" value="', s(sesskey()), '" />
		<div style="display:none;">
			' . html_writer::input_hidden_params($PAGE->url) . '
		</div>
		<div class="bulk-delete-select-all">
		<label style="cursor:default;">
			<input type="checkbox" checked="checked" style="height:16px; vertical-align:middle;" />
			
		</label></div>';

        $i = 0;
        echo '
		<ul class="bulk-delete-list">';
        foreach ($items as $id => $item) {
            echo '
			<li class="bulk-delete-item">
				<input type="checkbox" name="delete[' . $id . ']" checked="checked" id="delete_' . $id . '" />
				', renderer::render_modicon($item), '
                <label for="delete_' . $id . '">', format_string($item->modtext), '</label>
			</li>';
        }
        echo '
		</ul>';

        echo '
		<div>
			<input class="btn btn-primary form_submit" type="button" name="delete_checked" value="', get_string('deleteselected'), '" />
			<input class="btn" type="button" onclick="history.back();" value="', get_string('cancel'), '" />
		</div>
		</form>';
    }
    echo '
	</div>';
}
echo $OUTPUT->footer();
