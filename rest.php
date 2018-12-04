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
 *  Sharing Cart - REST API
 *
 *  @package    block_sharing_cart
 *  @copyright  2017 (C) VERSION2, INC.
 *  @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once '../../config.php';

require_once __DIR__.'/classes/controller.php';

try {
	$controller = new sharing_cart\controller();
	
	switch (required_param('action', PARAM_TEXT)) {
        case 'render_tree':
            $PAGE->set_context(\context_user::instance($USER->id));
            echo $controller->render_tree($USER->id);
            exit;
        case 'is_userdata_copyable':
            $cmid = required_param('cmid', PARAM_INT);
            echo $controller->is_userdata_copyable($cmid);
            exit;
        case 'is_userdata_copyable_section':
            $sectionid = required_param('sectionid', PARAM_INT);
            echo $controller->is_userdata_copyable_section($sectionid);
            exit;
        case 'backup':
            $cmid     = required_param('cmid', PARAM_INT);
            $userdata = required_param('userdata', PARAM_BOOL);
            $course = required_param('course', PARAM_INT);
            $controller->backup($cmid, $userdata, $course);
            exit;
        case 'backup_section':
            $sectionid = required_param('sectionid', PARAM_INT);
            $sectionname = required_param('sectionname', PARAM_TEXT);
            $userdata = required_param('userdata', PARAM_BOOL);
            $course = required_param('course', PARAM_INT);
            $controller->backup_section($sectionid, $sectionname, $userdata, $course);
            exit;
        case 'movedir':
            $id = required_param('id', PARAM_INT);
            $to = required_param('to', PARAM_TEXT);
            $controller->movedir($id, $to);
            exit;
        case 'move':
            $id = required_param('id', PARAM_INT);
            $to = required_param('to', PARAM_INT);
            $controller->move($id, $to);
            exit;
        case 'delete':
            $id = required_param('id', PARAM_INT);
            $controller->delete($id);
            exit;
        case 'delete_directory':
            $path = required_param('path', PARAM_TEXT);
            $controller->delete_directory($path);
            exit;
	}
	throw new sharing_cart\exception('invalidoperation');
	
} catch (Exception $ex) {
	header('HTTP/1.1 400 Bad Request');
	$json = array(
		'message' => $ex->getMessage(),
		);
	if (!empty($CFG->debug) and $CFG->debug >= DEBUG_DEVELOPER) {
		$json += array(
			'file'  => substr($ex->getFile(), strlen($CFG->dirroot)),
			'line'  => $ex->getLine(),
			'trace' => format_backtrace($ex->getTrace(), true),
			);
	}
	echo json_encode($json);
}
