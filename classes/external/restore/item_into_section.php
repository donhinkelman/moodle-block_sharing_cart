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

namespace block_sharing_cart\external\restore;

use block_sharing_cart\app\factory;
use core_external\external_api;
use core_external\external_description;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;

/**
 * item_into_section external API.
 *
 * @package   block_sharing_cart
 * @copyright moxis
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_into_section extends external_api
{
    /**
     * execute_parameters.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'itemid' => new external_value(PARAM_INT, '', VALUE_REQUIRED),
            'sectionid' => new external_value(PARAM_INT, '', VALUE_REQUIRED),
            'coursemodulestoinclude' => new external_multiple_structure(
                new external_value(PARAM_INT, '', VALUE_REQUIRED)
            ),
            'sectionstoinclude' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Original ids of nested sections to restore; empty means all', VALUE_REQUIRED),
                '',
                VALUE_DEFAULT,
                []
            ),
            'insertasnewsection' => new external_value(
                PARAM_BOOL,
                'Create the copied section as a new section under sectionid (0 = top level of courseid)' .
                    ' instead of merging into it',
                VALUE_DEFAULT,
                false
            ),
            'courseid' => new external_value(PARAM_INT, 'Required when sectionid is 0', VALUE_DEFAULT, 0),
            'replacesectiondetails' => new external_value(
                PARAM_BOOL,
                'When merging into sectionid, replace its title and description with the copied section\'s',
                VALUE_DEFAULT,
                false
            ),
            'asnewsection' => new external_value(
                PARAM_BOOL,
                'Legacy alias for insertasnewsection',
                VALUE_DEFAULT,
                false
            ),
            'beforecmid' => new external_value(
                PARAM_INT,
                'Legacy insertion anchor; ignored by the current restore flow',
                VALUE_DEFAULT,
                0
            ),
        ]);
    }

    /**
     * execute.
     * @param int $itemid
     * @param int $sectionid
     * @param array $coursemodulestoinclude
     * @param array $sectionstoinclude
     * @param bool $insertasnewsection
     * @param int $courseid
     * @param bool $replacesectiondetails
     * @param mixed ...$legacyparameters Legacy asnewsection and beforecmid arguments.
     */
    public static function execute(
        int $itemid,
        int $sectionid,
        array $coursemodulestoinclude,
        array $sectionstoinclude = [],
        bool $insertasnewsection = false,
        int $courseid = 0,
        bool $replacesectiondetails = false,
        mixed ...$legacyparameters
    ): bool {
        global $USER, $DB;

        $basefactory = factory::make();

        $params = self::validate_parameters(self::execute_parameters(), [
            'itemid' => $itemid,
            'sectionid' => $sectionid,
            'coursemodulestoinclude' => $coursemodulestoinclude,
            'sectionstoinclude' => $sectionstoinclude,
            'insertasnewsection' => $insertasnewsection,
            'courseid' => $courseid,
            'replacesectiondetails' => $replacesectiondetails,
            'asnewsection' => $legacyparameters['asnewsection'] ?? $legacyparameters[0] ?? false,
            'beforecmid' => $legacyparameters['beforecmid'] ?? $legacyparameters[1] ?? 0,
        ]);

        // Older pxgrid clients sent asnewsection and beforecmid. Keep accepting both without exposing the newer
        // optional arguments through the execute() signature, which older format integrations use to detect support.
        $insertasnewsection = $params['insertasnewsection'] || $params['asnewsection'];

        self::validate_context(
            \context_user::instance($USER->id)
        );

        $item = $basefactory->item()->repository()->get_by_id($params['itemid']);
        if (!$item) {
            return false;
        }

        if ($item->get_user_id() !== (int)$USER->id) {
            return false;
        }

        if ($params['sectionid'] > 0) {
            $courseid = (int)$DB->get_field('course_sections', 'course', ['id' => $params['sectionid']], MUST_EXIST);
        } else {
            // Top level of the course: only meaningful when creating a new section.
            if (!$insertasnewsection || $params['courseid'] <= 0) {
                return false;
            }
            $courseid = (int)$DB->get_field('course', 'id', ['id' => $params['courseid']], MUST_EXIST);
        }
        $context = \core\context\course::instance($courseid);

        $settings = [
            'insert_as_new_section' => $insertasnewsection,
            'replace_section_details' => $params['replacesectiondetails'],
        ];

        // Only pass include/exclude list when the user can configure restore in the target course context.
        if (has_capability('moodle/restore:configure', $context)) {
            $settings['course_modules_to_include'] = $params['coursemodulestoinclude'] ?? [];
            $settings['sections_to_include'] = $params['sectionstoinclude'] ?? [];
        }

        $result = $basefactory->restore()->handler()->restore_item_into_section(
            $item,
            $params['sectionid'],
            $params['itemid'],
            $settings,
            $courseid
        );

        return $result !== null;
    }

    /**
     * execute_returns.
     */
    public static function execute_returns(): external_description {
        return new external_value(PARAM_BOOL, '', VALUE_REQUIRED);
    }
}
