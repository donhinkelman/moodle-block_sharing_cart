<?php

namespace block_sharing_cart\task;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use async_helper;
use block_sharing_cart\app\factory as base_factory;
use block_sharing_cart\app\item\entity;

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/moodle2/backup_plan_builder.class.php');

class asynchronous_backup_task extends \core\task\adhoc_task
{
    protected base_factory $base_factory;

    /**
     * Should always resemble
     * @see \core\task\asynchronous_backup_task::execute
     * with the addition of calling
     * @see self::before_backup_started_hook
     * and
     * @see self::after_backup_finished_hook
     */
    public function execute(): void
    {
        $this->base_factory = base_factory::make();
        $db = $this->base_factory->moodle()->db();

        /*
         * This task cannot be rerun, so we need to handle all exceptions.
         * If an exception occurs and the item exists, we need to set the status of the item to failed.
         * If an exception occurs and the item does not exist, we need to log the error and abort.
         * By catching all exceptions, we can ensure that the task will always complete and not rerun, which would always fail.
         */
        try {
            $started = time();

            $backupid = $this->get_custom_data()->backupid;
            $backuprecord = $db->get_record(
                'backup_controllers',
                ['backupid' => $backupid],
                'id, controller',
                MUST_EXIST
            );
            mtrace('Processing asynchronous backup for backup: ' . $backupid);

            // Get the backup controller by backup id. If controller is invalid, this task can never complete.
            if ($backuprecord->controller === '') {
                mtrace('Bad backup controller status, invalid controller, ending backup execution.');
                return;
            }
            $bc = \backup_controller::load_controller($backupid);
            $bc->set_progress(new \core\progress\db_updater($backuprecord->id, 'backup_controllers', 'progress'));

            // Do some preflight checks on the backup.
            $status = $bc->get_status();
            $execution = $bc->get_execution();

            // Check that the backup is in the correct status and
            // that is set for asynchronous execution.
            if ($status == \backup::STATUS_AWAITING && $execution == \backup::EXECUTION_DELAYED) {
                $this->before_backup_started_hook($bc);

                // Execute the backup.
                $bc->execute_plan();

                // Send message to user if enabled.
                $messageenabled = (bool)get_config('backup', 'backup_async_message_users');
                if ($messageenabled && $bc->get_status() == \backup::STATUS_FINISHED_OK) {
                    $asynchelper = new async_helper('backup', $backupid);
                    $asynchelper->send_message();
                }
            } else {
                // If status isn't 700, it means the process has failed.
                // Retrying isn't going to fix it, so marked operation as failed.
                $bc->set_status(\backup::STATUS_FINISHED_ERR);
                mtrace('Bad backup controller status, is: ' . $status . ' should be 700, marking job as failed.');
            }

            $this->after_backup_finished_hook($bc);

            // Cleanup.
            $bc->destroy();

            $duration = time() - $started;
            mtrace('Backup completed in: ' . $duration . ' seconds');
        } catch (\Exception $e) {
            mtrace("An error occurred during asynchronous backup task execution");
            mtrace($e->getMessage());
            mtrace($e->getTraceAsString());
            $bc->set_status(\backup::STATUS_FINISHED_ERR);

            $this->fail_task();
        }
    }

    public function retry_until_success(): bool
    {
        return false;
    }

    private function before_backup_started_hook(\backup_controller $backup_controller): void
    {
        try {
            mtrace('Executing before_backup_started_hook...');
            $custom_data = $this->get_custom_data();

            $db = $this->base_factory->moodle()->db();
            $item_entity = $this->base_factory->item()->repository()->get_by_id($custom_data->item->id);

            if ($item_entity->get_type() === 'section') {
                $db->get_record('course_sections',['id' => $item_entity->get_old_instance_id()] , strictness: MUST_EXIST);
            } else {
                $db->get_record('course_modules',['id' => $item_entity->get_old_instance_id()] , strictness: MUST_EXIST);
            }

            $settings = [
                'role_assignments' => false,
                'activities' => true,
                'blocks' => false,
                'filters' => false,
                'comments' => false,
                'calendarevents' => false,
                'userscompletion' => false,
                'logs' => false,
                'grade_histories' => false,
                'users' => false,
                'anonymize' => false,
                'include_badges' => false,
                'filename' => 'sharing_cart_backup-' . $item_entity->get_id() . '.mbz'
            ];

            $context = $this->get_backup_controller_context($backup_controller);

            if ($custom_data->backup_settings->users) {
                require_capability('moodle/backup:userinfo', $context);

                $settings['users'] = true;
            }

            if ($custom_data->backup_settings->anonymize && $settings['users']) {
                require_capability('moodle/backup:anonymise', $context);

                $settings['anonymize'] = true;
            }
            $settings += $this->base_factory->backup()->settings_helper()->get_course_settings_by_item($item_entity, $settings['users']);

            $plan = $backup_controller->get_plan();
            foreach ($settings as $name => $value) {
                if ($plan->setting_exists($name)) {
                    $setting = $plan->get_setting($name);

                    // If locked
                    if (\base_setting::NOT_LOCKED !== $setting->get_status()) {
                        continue;
                    }

                    $setting->set_value($value);
                }
            }

            $this->filter_away_disabled_course_modules($backup_controller);

            mtrace('Executing before_backup_started_hook completed, continuing with backup...');
        } catch (\Exception $e) {
            mtrace("An error occurred during before_backup_started_hook");
            throw $e;
        }
    }

    private function after_backup_finished_hook(\backup_controller $backup_controller): void
    {
        try {
            mtrace('Executing after_backup_finished_hook...');

            $custom_data = $this->get_custom_data();
            $item = $custom_data->item ?? null;
            $root_item = $this->base_factory->item()->repository()->get_by_id($item->id);
            if (!$root_item) {
                throw new \Exception(
                    "Couldn't fetch item (id: {$item->id})"
                );
            }

            if ($backup_controller->get_status() === \backup::STATUS_FINISHED_ERR) {
                throw new \Exception("Backup failed");
            }

            mtrace("Fetching backup results...");
            $backup_results = $backup_controller->get_results();

            /**
             * @var ?\stored_file $file
             */
            $file = $backup_results['backup_destination'] ?? null;
            if (!$file) {
                mtrace("Backup results: " . print_r($backup_results, true));
                throw new \Exception("No backup file found in results");
            }

            mtrace("Copying backup file into sharing cart...");
            $sharing_cart_file = $this->copy_backup_file_to_sharing_cart_filearea($file, $root_item);

            mtrace("Updating items in sharing cart using contents of backup file...");
            $this->base_factory->item()->repository()->update_sharing_cart_item_with_backup_file(
                $root_item,
                $sharing_cart_file
            );

            mtrace('Executing after_backup_finished_hook completed...');
        } catch (\Exception $e) {
            mtrace("An error occurred during after_backup_finished_hook");
            throw $e;
        }
    }

    private function get_backup_controller_context(\backup_controller $backup_controller): \core\context
    {
        switch ($backup_controller->get_type()) {
            case \backup::TYPE_1COURSE:
                $course_id = $backup_controller->get_id();
                return \core\context\course::instance($course_id);
            case \backup::TYPE_1SECTION:
                $course_id = $backup_controller->get_courseid();
                return \core\context\course::instance($course_id);
            case \backup::TYPE_1ACTIVITY:
                $course_module_id = $backup_controller->get_id();
                return \core\context\module::instance($course_module_id);
            default:
                throw new \Exception('Unknown backup instance type');
        }
    }

    private function copy_backup_file_to_sharing_cart_filearea(\stored_file $file, entity $root_item): \stored_file
    {
        /**
         * @var \file_storage $fs
         */
        $fs = get_file_storage();

        return $fs->create_file_from_storedfile([
            'contextid' => \context_user::instance($root_item->get_user_id())->id,
            'component' => 'block_sharing_cart',
            'filearea' => 'backup',
            'itemid' => $root_item->get_id(),
            'filepath' => '/',
            'filename' => $file->get_filename(),
        ], $file);
    }

    private function filter_away_disabled_course_modules(
        \backup_controller $backup_controller
    ): void {
        $db = $this->base_factory->moodle()->db();

        mtrace("Excluding activities which are disabled on the site...");

        foreach ($backup_controller->get_plan()->get_tasks() as $task) {
            if ($task instanceof \backup_activity_task) {
                $cm_id = (int)$task->get_moduleid();
                $modulename = $task->get_modulename();

                $include_activity = $db->get_record('modules', [
                        'name' => $modulename,
                        'visible' => true
                    ]) !== false;

                if ($include_activity === false){
                    mtrace('...' . ("Excluding activity: (id: $cm_id)"));
                    $task->get_setting('included')->set_value(false);
                }
            }
        }
    }

    private function fail_task(): void
    {
        $db = $this->base_factory->moodle()->db();

        mtrace("Async backup failed, trying to set item status to failed...");

        $custom_data = $this->get_custom_data();
        $item = $custom_data->item ?? null;
        $root_item = $this->base_factory->item()->repository()->get_by_id($item->id);
        if (!$root_item) {
            mtrace(
                "Couldn't fetch item (id: {$item->id}) from {$db->get_prefix()}{$this->base_factory->item()->repository()->get_table()}, aborting..."
            );
            return;
        }

        $root_item->set_status(entity::STATUS_BACKUP_FAILED);
        $this->base_factory->item()->repository()->update($root_item);

        mtrace("Async backup failed, item status has been set to failed, aborting...");
    }
}
