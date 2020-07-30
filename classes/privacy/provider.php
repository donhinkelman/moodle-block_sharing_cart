<?php

namespace block_sharing_cart\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\context;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();


class provider implements
    core_userlist_provider,
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Get the list of contexts that contain user information for the specified user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // There is only user context that related to sharing cart
        $contextlist->add_user_context($userid);

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        $user = $contextlist->get_user();
        // Data structure
        $root = get_string('pluginname', 'block_sharing_cart');

        $context = \context_user::instance($user->id);
        self::export_user_sharing_cart($user, $context, $root);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context $context   The specific context to delete data for.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function delete_data_for_all_users_in_context(\context $context) {

        // No data need to be delete other than user context
        if ($context->contextlevel !== CONTEXT_USER) {
            return;
        }

        self::delete_user_sharing_cart_entities($context->instanceid);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     * @throws \dml_exception
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        foreach ($contextlist as $context) {
            // Accept only user context
            if ($context->contextlevel !== CONTEXT_USER) {
                continue;
            }
            if ((int)$context->instanceid !== (int)$user->id) {
                continue;
            }

            $DB->delete_records('block_sharing_cart', ['userid' => $user->id]);
        }
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        // Out of context
        if (!$context instanceof \context_user) {
            return;
        }

        // No data available for this user
        if (!$DB->record_exists('block_sharing_cart', ['userid' => $context->instanceid])) {
            return;
        }

        $userlist->add_user($context->instanceid);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $users = $userlist->get_userids();

        if (!empty($users)) {
            self::delete_users_sharing_cart_entities($users);
        }
    }

    /**
     * Returns meta data about this system.
     *
     * @param   collection     $collection The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('block_sharing_cart', [
            'userid' => 'privacy:metadata:block_sharing_cart:userid',
            'modname' => 'privacy:metadata:block_sharing_cart:modname',
            'modicon' => 'privacy:metadata:block_sharing_cart:modicon',
            'modtext' => 'privacy:metadata:block_sharing_cart:modtext',
            'ctime' => 'privacy:metadata:block_sharing_cart:ctime',
            'tree' => 'privacy:metadata:block_sharing_cart:tree',
            'weight' => 'privacy:metadata:block_sharing_cart:weight',
        ], 'privacy:metadata:block_sharing_cart');

        return $collection;
    }

    /**
     * @param object $user
     * @param \context $context
     * @param string $root_data_path
     * @throws \dml_exception
     */
    private static function export_user_sharing_cart(object $user, \context $context, string $root_data_path) {
        global $DB;

        // Mapping
        $items = [];

        $params = [
            'userid' => $user->id
        ];
        $records = $DB->get_recordset('block_sharing_cart', $params);

        foreach ($records as $record) {
            $folder = !empty($record->tree) ? $record->tree : 0;

            $items[$folder][] = [
                'type' => $record->modname,
                'name' => $record->modtext,
                'timecreated' => transform::datetime($record->ctime),
            ];
        }

        $records->close();

        // Sharing cart under section
        foreach ($items as $folder => $data) {
            if (empty($folder)) {
                continue;
            }
            writer::with_context($context)->export_data([$root_data_path, $folder], (object)$data);
        }

        // Sharing cart root
        if (isset($items[0]) && $data = $items[0]) {
            writer::with_context($context)->export_data([$root_data_path], (object)$data);
        }
    }

    /**
     * Delete user sharing cart entities
     * @param int $userid
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private static function delete_user_sharing_cart_entities(int $userid) {
        self::delete_users_sharing_cart_entities([$userid]);
    }

    /**
     * Delete many users sharing cart entities
     * @param array $users
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private static function delete_users_sharing_cart_entities(array $users) {
        global $DB;

        // Exit if given user is empty
        if (empty($users)) {
            return;
        }

        // Get sections that belong to sharing cart entities
        [$user_sql, $user_params] = $DB->get_in_or_equal($users);
        $sections = $DB->get_fieldset_select(
            'block_sharing_cart',
            'section',
            'section != 0 AND userid ' . $user_sql,
            $user_params
        );

        // Delete sharing cart sections
        if (!empty($sections)) {
            [$in_sql, $in_params] = $DB->get_in_or_equal($sections);
            $DB->delete_records_select('block_sharing_cart_sections', 'id ' . $in_sql, $in_params);
        }

        // Delete any sharing cart entities that belong to user
        $DB->delete_records_select('block_sharing_cart', 'userid ' . $user_sql, $user_params);
    }
}
