<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace block_sharing_cart\external\backup;

use block_sharing_cart\app\factory;
use block_sharing_cart\app\item\entity;
use core_external\external_api;
use core_external\external_description;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * section_into_sharing_cart external API.
 *
 * @package   block_sharing_cart
 * @copyright moxis
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section_into_sharing_cart extends external_api
{
    /**
     * execute_parameters.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'sectionid' => new external_value(PARAM_INT, '', VALUE_REQUIRED),
            'settings' => new external_single_structure([
                'users' => new external_value(PARAM_BOOL, 'Whether to include user data in the backup', VALUE_REQUIRED),
                'anonymize' => new external_value(
                    PARAM_BOOL,
                    'Whether to anonymize user data in the backup',
                    VALUE_REQUIRED
                ),
            ], 'The settings of the item'),
        ]);
    }

    /**
     * execute.
     * @param int $sectionid
     * @param array $settings
     */
    public static function execute(int $sectionid, array $settings): object {
        global $USER, $DB;

        $basefactory = factory::make();

        $params = self::validate_parameters(self::execute_parameters(), [
            'sectionid' => $sectionid,
            'settings' => $settings,
        ]);

        $sectionfields = 'id, section, sequence, course, itemid';
        // The itemid field is not supported until Moodle 4.5+.
        if (get_config('core', 'version') < 2024100700) {
            $sectionfields = 'id, section, sequence, course';
        }

        $section = $DB->get_record(
            'course_sections',
            ['id' => $params['sectionid']],
            $sectionfields,
            MUST_EXIST
        );

        if ($section === false) {
            throw new \Exception("Section does not exist");
        }

        self::validate_context(
            \context_course::instance($section->course)
        );

        $backuphandler = $basefactory->backup()->handler();

        // Nested formats may declare descendants; a section is empty only if none of them has content either.
        $sectiontree = $backuphandler->resolve_section_tree((int)$section->course, (int)$section->id);
        if (!$backuphandler->section_has_content($section, $sectiontree)) {
            throw new \moodle_exception('no_course_modules_in_section_description', 'block_sharing_cart');
        }

        $item = $basefactory->item()->repository()->insert_section(
            $section,
            $USER->id,
            null,
            entity::STATUS_AWAITING_BACKUP
        );

        $backuptask = $backuphandler->backup_section($section, $item, $settings, $sectiontree);

        $return = $item->to_array();
        $return['taskid'] = $backuptask["task"]->get_id();

        return (object)$return;
    }

    /**
     * execute_returns.
     */
    public static function execute_returns(): external_description {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'The id of the item in the sharing cart', VALUE_REQUIRED),
            'user_id' => new external_value(PARAM_INT, 'The id of the user who owns the item', VALUE_REQUIRED),
            'file_id' => new external_value(PARAM_INT, 'The id of the backup file', VALUE_REQUIRED),
            'parent_item_id' => new external_value(PARAM_INT, 'The id of the parent item', VALUE_REQUIRED),
            'old_instance_id' => new external_value(PARAM_INT, 'The old instance id', VALUE_REQUIRED),
            'taskid' => new external_value(PARAM_INT, 'The task id of backup adhoc task', VALUE_REQUIRED),
            'type' => new external_value(PARAM_TEXT, 'The type of the item', VALUE_REQUIRED),
            'name' => new external_value(PARAM_TEXT, 'The name of the item', VALUE_REQUIRED),
            'status' => new external_value(PARAM_INT, 'The status of the item', VALUE_REQUIRED),
            'version' => new external_value(PARAM_INT, 'The version of the item', VALUE_REQUIRED),
            'timecreated' => new external_value(PARAM_INT, 'The time the item was created', VALUE_REQUIRED),
            'timemodified' => new external_value(PARAM_INT, 'The time the item was last modified', VALUE_REQUIRED),
        ]);
    }
}
