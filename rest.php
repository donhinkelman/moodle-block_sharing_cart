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
use block_sharing_cart\mysql_logger;

/**
 *  Sharing Cart - REST API
 *
 * @package    block_sharing_cart
 * @copyright  2017 (C) VERSION2, INC.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_sharing_cart\controller;
use block_sharing_cart\exception as sharing_cart_exception;
use block_sharing_cart\section;

require_once __DIR__ . '/../../config.php';

try {
    $controller = new controller();

    switch (required_param('action', PARAM_TEXT)) {
        case 'render_tree':
            $courseid = required_param('courseid', PARAM_INT);
            $PAGE->set_course(get_course($courseid));
            $PAGE->set_context(\context_user::instance($USER->id));
            echo $controller->render_tree($USER->id);
            exit;
        case 'is_userdata_copyable':
            $cmid = required_param('cmid', PARAM_INT);
            echo $controller->is_userdata_copyable($cmid);
            exit;
        case 'is_userdata_copyable_section':
            $sectionid = optional_param('sectionid', null, PARAM_INT);
            if (empty($sectionid)) {
                $sectionnumber = required_param('sectionnumber', PARAM_INT);
                $courseid = required_param('courseid', PARAM_INT);
                $section = section::get($courseid, $sectionnumber);
                $sectionid = $section->id;
            }
            echo $controller->is_userdata_copyable_section($sectionid);
            exit;
        case 'backup':
            $cmid = required_param('cmid', PARAM_INT);
            $userdata = required_param('userdata', PARAM_BOOL);
            $courseid = required_param('courseid', PARAM_INT);
            $controller->backup($cmid, $userdata, $courseid);
            exit;
        case 'backup_section':
            $sectionid = optional_param('sectionid', null, PARAM_INT);
            $sectionname = optional_param('sectionname', null, PARAM_TEXT);
            if (empty($sectionid) || empty($sectionname)) {
                $sectionnumber = required_param('sectionnumber', PARAM_INT);
                $courseid = required_param('courseid', PARAM_INT);
                $section = section::get($courseid, $sectionnumber);
                $sectionid = $section->id;
                $sectionname = $section->name;
            }
            $userdata = required_param('userdata', PARAM_BOOL);
            $courseid = required_param('courseid', PARAM_INT);
            $controller->backup_section($sectionid, $sectionname, $userdata, $courseid);
            exit;
        case 'movedir':
            $item_id = required_param('item_id', PARAM_INT);
            $folder_to = required_param('folder_to', PARAM_TEXT);
            $controller->movedir($item_id, $folder_to);
            exit;
        case 'move':
            $item_id = required_param('item_id', PARAM_INT);
            $area_to = required_param('area_to', PARAM_INT);
            $controller->move($item_id, $area_to);
            exit;
        case 'delete':
            $id = required_param('id', PARAM_INT);
            $controller->delete($id);
            exit;
        case 'delete_directory':
            $path = required_param('path', PARAM_TEXT);
            $controller->delete_directory($path);
            exit;
        case 'ensure_backup_present':
            require_sesskey();
            $cmid = required_param('cmid', PARAM_INT);
            $courseid = required_param('courseid', PARAM_INT);
            echo $controller->ensure_backup_in_module($cmid, $courseid);
            exit;
    }
    throw new sharing_cart_exception('invalidoperation');

} catch (Exception $ex) {

	header('HTTP/1.1 400 Bad Request');

    $json = array(
        'message' => $ex->getMessage(),
    );

    if (!empty($CFG->debug) && $CFG->debug >= DEBUG_DEVELOPER) {
        $json += array(
            'file' => substr($ex->getFile(), strlen($CFG->dirroot)),
            'line' => $ex->getLine(),
            'trace' => format_backtrace($ex->getTrace(), true),
        );
    }

	/**
	 * If logger exists - we log some stuff
	 */
    if(class_exists(mysql_logger::class)){
    	try{
		    $logger = new mysql_logger();
		    $logger->log($ex->getMessage(), $ex);
	    }
	    catch(\Exception $e){}
    }

    echo json_encode($json);
}
