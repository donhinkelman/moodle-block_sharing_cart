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
 *  Sharing Cart - Restore Operation
 *
 * @package    block_sharing_cart
 * @copyright  2017 (C) VERSION2, INC.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_sharing_cart\controller;
use block_sharing_cart\section_title_form;

require_once '../../config.php';

global $CFG, $OUTPUT, $PAGE, $DB, $USER;

$directory = required_param('directory', PARAM_BOOL);
$target = required_param('target', PARAM_RAW);
$courseid = required_param('course', PARAM_INT);
$sectionnumber = required_param('section', PARAM_INT);
$overwrite = optional_param('overwrite', 0, PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_TEXT);

if (!$returnurl) {
    $returnurl = ($courseid == SITEID) ? new moodle_url('/') : new moodle_url('/course/view.php', ['id' => $courseid]);
}

require_login($courseid);

try {

    $controller = new controller();

    // Trying to restore a directory of items
    if ($directory) {

        $form = new section_title_form($directory, $target, $courseid, $sectionnumber, [], $returnurl, 0);

        if ($form->is_cancelled()) {
            redirect($returnurl); exit;
        }

        $target = ltrim($target, '/');

        $sections = $controller->get_path_sections($target);

        // Directory contains an entire section of items. Display form to let user resolve conflicts
        if (count($sections) > 0 && !$form->is_submitted()) {

            $items = $DB->get_records('block_sharing_cart', array('tree' => $target, 'userid' => $USER->id));
            $items_count = count($items);

            $dest_section = $DB->get_record('course_sections', array('course' => $courseid, 'section' => $sectionnumber));

            $PAGE->set_pagelayout('standard');
            $PAGE->set_url($returnurl);
            $PAGE->set_title(get_string('pluginname', 'block_sharing_cart').' - '.get_string('restore', 'block_sharing_cart'));
            $PAGE->set_heading(get_string('restore', 'block_sharing_cart'));

            $form = new section_title_form($directory, $target, $courseid, $sectionnumber, $sections, $returnurl, $items_count);

            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('section_name_conflict', 'block_sharing_cart'));
            $form->display();
            echo $OUTPUT->footer();
            exit;
        }

        // Perform directory restore
        $controller->restore_directory($target, $courseid, $sectionnumber, $overwrite);

    } else {

        // Restore single item
        $controller->restore($target, $courseid, $sectionnumber);

    }

    redirect($returnurl);

} catch (\block_sharing_cart\exception $ex) {

    print_error($ex->errorcode, $ex->module, $returnurl, $ex->a);

} catch (Exception $ex) {

    if (!empty($CFG->debug) && $CFG->debug >= DEBUG_DEVELOPER) {
        print_error('notlocalisederrormessage', 'error', '', $ex->__toString());
    } else {
        print_error('unexpectederror', 'block_sharing_cart', $returnurl);
    }

}
