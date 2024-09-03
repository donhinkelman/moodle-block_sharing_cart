<?php

namespace block_sharing_cart\privacy;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider
{
    public static function get_metadata(collection $collection): collection
    {
        $collection->add_database_table('block_sharing_cart_items', [
            'user_id' => 'privacy:metadata:sharing_cart_items:user_id',
            'file_id' => 'privacy:metadata:sharing_cart_items:file_id',
            'parent_item_id' => 'privacy:metadata:sharing_cart_items:parent_item_id',
            'old_instance_id' => 'privacy:metadata:sharing_cart_items:old_instance_id',
            'type' => 'privacy:metadata:sharing_cart_items:type',
            'name' => 'privacy:metadata:sharing_cart_items:name',
            'status' => 'privacy:metadata:sharing_cart_items:status',
            'timecreated' => 'privacy:metadata:sharing_cart_items:timecreated',
            'timemodified' => 'privacy:metadata:sharing_cart_items:timemodified',
        ], 'privacy:metadata:sharing_cart_items:tabledesc');

        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();
        $sql = "SELECT id FROM {context} WHERE contextlevel = 30 AND instanceid = :userid";
        $contextlist->add_from_sql($sql, ['userid' => $userid]);
        return $contextlist;
    }

    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();
        $sql = 'SELECT *
                  FROM {block_sharing_cart_items} i
                 WHERE i.user_id = :userid';

        $context = \context_user::instance($user->id);
        $contextpath = [get_string('pluginname', 'block_sharing_cart')];

        $recordset = $DB->get_recordset_sql($sql, ['userid' => $user->id]);
        foreach ($recordset as $record) {
            $data = (object) [
                'name' => format_string($record->name),
                'timecreated' => transform::datetime($record->timecreated),
            ];

            writer::with_context($context)->export_data(array_merge($contextpath, [
                clean_param($record->idopensesame, PARAM_FILE),
            ]), $data);
        }

        $recordset->close();

    }

    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        // Only delete data for a user context.
        if ($context->contextlevel == CONTEXT_USER) {
            $DB->delete_records('block_sharing_cart_items', ['user_id' => $context->instanceid]);
        }

    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        foreach ($contextlist as $context) {
            if ($context->contextlevel == CONTEXT_USER && $contextlist->get_user()->id == $context->instanceid) {
                $DB->delete_records('block_sharing_cart_items', ['user_id' => $context->instanceid]);
            }
        }
    }

    public static function get_users_in_context(userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        if (!$context instanceof \context_user) {
            return;
        }

        if ($DB->record_exists('block_sharing_cart_items', ['user_id' => $context->instanceid])) {
            $userlist->add_user($context->instanceid);
        }
    }

    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        if ($context instanceof \context_user && in_array($context->instanceid, $userlist->get_userids())) {
            $DB->delete_records('block_sharing_cart_items', ['user_id' => $context->instanceid]);
        }
    }
}