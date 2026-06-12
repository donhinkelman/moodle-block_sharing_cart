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
    protected bool $output = true;
    protected ?base_factory $base_factory = null;
    private ?\backup_controller $controller = null;

    protected function factory(): base_factory
    {
        return $this->base_factory ??= base_factory::make();
    }

    protected function db(): \moodle_database
    {
        return $this->factory()->moodle()->db();
    }

    protected function get_backup_id(): string
    {
        return $this->get_custom_data()->backupid ?? '';
    }

    protected function output(string $message): void
    {
        if (!$this->output) {
            return;
        }
        mtrace($message);
    }

    public function get_backup_controller(): ?\backup_controller
    {
        try {
            if ($this->controller === null) {
                $backupid = $this->get_backup_id();
                $record = $this->db()->get_record(
                    'backup_controllers',
                    ['backupid' => $backupid],
                    'id, controller',
                    MUST_EXIST
                );

                // Get the backup controller by backup id. If controller is invalid, this task can never complete.
                if ($record->controller === '') {
                    return null;
                }

                $this->controller = \backup_controller::load_controller($backupid);
                $this->controller->set_progress(
                    new \core\progress\db_updater(
                        $record->id,
                        'backup_controllers',
                        'progress'
                    )
                );
            }
            return $this->controller;
        } catch (\Exception) {
            return null;
        }
    }

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
        $bc = $this->get_backup_controller();

        /*
         * This task cannot be rerun, so we need to handle all exceptions.
         * If an exception occurs and the item exists, we need to set the status of the item to failed.
         * If an exception occurs and the item does not exist, we need to log the error and abort.
         * By catching all exceptions, we can ensure that the task will always complete and not rerun,
         * which would always fail.
         */
        try {
            $started = time();

            $backupid = $this->get_backup_id();
            $this->output('Processing asynchronous backup for backup: ' . $backupid);

            if ($bc === null) {
                $this->output('Bad backup controller status, invalid controller, ending backup execution.');
                return;
            }

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
                $coremessageenabled = (bool)get_config('backup', 'backup_async_message_users');
                $cartmessageenabled = (bool)get_config('block_sharing_cart', 'backup_async_message_users');
                $messageenabled = ($coremessageenabled && $cartmessageenabled);
                if ($messageenabled && $bc->get_status() == \backup::STATUS_FINISHED_OK) {
                    $asynchelper = new async_helper('backup', $backupid);
                    $asynchelper->send_message();
                }
            } else {
                // If status isn't 700, it means the process has failed.
                // Retrying isn't going to fix it, so marked operation as failed.
                $bc->set_status(\backup::STATUS_FINISHED_ERR);
                $this->output(
                    'Bad backup controller status, is: ' . $status . ' should be 700, marking job as failed.'
                );
            }

            $this->after_backup_finished_hook($bc);

            // Cleanup.
            $bc->destroy();

            $duration = time() - $started;
            $this->output('Backup completed in: ' . $duration . ' seconds');
        } catch (\Exception $e) {
            $this->output("An error occurred during asynchronous backup task execution");
            $this->output($e->getMessage());
            $this->output($e->getTraceAsString());
            $bc?->set_status(\backup::STATUS_FINISHED_ERR);

            $this->fail_task();
        }
    }

    public function retry_until_success(): bool
    {
        return false;
    }

    protected function before_backup_started_hook(\backup_controller $backup_controller): void
    {
        try {
            $this->output('Executing before_backup_started_hook...');
            $custom_data = $this->get_custom_data();

            $db = $this->db();
            $item_entity = $this->factory()->item()->repository()->get_by_id($custom_data->item->id);

            if ($item_entity->get_type() === 'section') {
                $db->get_record(
                    'course_sections',
                    ['id' => $item_entity->get_old_instance_id()],
                    strictness: MUST_EXIST
                );
            } else {
                $db->get_record(
                    'course_modules',
                    ['id' => $item_entity->get_old_instance_id()],
                    strictness: MUST_EXIST
                );
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
                'badges' => false,
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
            $settings += $this->factory()->backup()
                ->settings_helper()
                ->get_course_settings_by_item($item_entity, $settings['users']);

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

            $this->toggle_question_bank_setting($plan, $item_entity);

            $this->filter_away_disabled_course_modules($backup_controller);

            $this->output('Executing before_backup_started_hook completed, continuing with backup...');
        } catch (\Exception $e) {
            $this->output("An error occurred during before_backup_started_hook");
            throw $e;
        }
    }

    protected function after_backup_finished_hook(\backup_controller $backup_controller): void
    {
        try {
            $this->output('Executing after_backup_finished_hook...');

            $custom_data = $this->get_custom_data();
            $item = $custom_data->item ?? null;
            $root_item = $this->factory()->item()->repository()->get_by_id($item->id);
            if (!$root_item) {
                throw new \Exception(
                    "Couldn't fetch item (id: {$item->id})"
                );
            }

            if ($backup_controller->get_status() === \backup::STATUS_FINISHED_ERR) {
                throw new \Exception("Backup failed");
            }

            $this->output("Fetching backup results...");
            $backup_results = $backup_controller->get_results();

            /**
             * @var ?\stored_file $file
             */
            $file = $backup_results['backup_destination'] ?? null;
            if (!$file) {
                $this->output("Backup results: " . print_r($backup_results, true));
                throw new \Exception("No backup file found in results");
            }

            $this->output("Copying backup file into sharing cart...");
            $sharing_cart_file = $this->copy_backup_file_to_sharing_cart_filearea($file, $root_item);

            $this->output("Deleting original backup file...");
            $file->delete();

            $this->output("Updating items in sharing cart using contents of backup file...");
            $this->factory()->item()->repository()->update_sharing_cart_item_with_backup_file(
                $root_item,
                $sharing_cart_file
            );

            $this->output('Executing after_backup_finished_hook completed...');
        } catch (\Exception $e) {
            $this->output("An error occurred during after_backup_finished_hook");
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
        $db = $this->db();

        $this->output("Excluding activities which are disabled on the site...");

        foreach ($backup_controller->get_plan()->get_tasks() as $task) {
            if ($task instanceof \backup_activity_task) {
                $cm_id = (int)$task->get_moduleid();
                $modulename = $task->get_modulename();

                $include_activity = $db->get_record('modules', [
                        'name' => $modulename,
                        'visible' => true
                    ]) !== false;

                if ($include_activity === false) {
                    $this->output('...' . ("Excluding activity: (id: $cm_id)"));
                    $task->get_setting('included')->set_value(false);
                }
            }
        }
    }

    private function fail_task(): void
    {
        $db = $this->db();

        $this->output("Async backup failed, trying to set item status to failed...");

        $custom_data = $this->get_custom_data();
        $item = $custom_data->item ?? null;
        $root_item = $this->factory()->item()->repository()->get_by_id($item->id);
        if (!$root_item) {
            $table = "{$db->get_prefix()}{$this->factory()->item()->repository()->get_table()}";
            $this->output(
                "Couldn't fetch item (id: {$item->id}) from {$table}, aborting..."
            );
            return;
        }

        $root_item->set_status(entity::STATUS_BACKUP_FAILED);
        $this->factory()->item()->repository()->update($root_item);

        $this->output("Async backup failed, item status has been set to failed, aborting...");
    }

    private function get_course_modules_settings_by_item(
        int $course_id,
        entity $item
    ): array {
        try {
            $item_id = $item->get_old_instance_id();
            if (empty($item_id)) {
                return [];
            }

            $mod_info = get_fast_modinfo($course_id);
            $cms = [];

            if ($item->get_type() === 'section') {
                $section = $mod_info->get_section_info_by_id($item_id);
                if (empty($section->sequence)) {
                    return [];
                }
                $cms = array_map(static function ($id) {
                    return (int)$id;
                }, explode(',', $section->sequence));
            } else {
                $cms[] = $item_id;
            }

            $settings = [];
            foreach ($cms as $id) {
                $cm = $mod_info->get_cm($id);
                $name = "{$cm->modname}_{$cm->id}_included";
                $settings[$name] = $id;
            }
            return $settings;
        } catch (\Exception) {
            return [];
        }
    }

    private function toggle_question_bank_setting(
        \backup_plan $plan,
        entity $item
    ): void {
        if (!$plan->setting_exists('questionbank')) {
            return;
        }

        $question_bank_setting = $plan->get_setting('questionbank');
        $status = $question_bank_setting->get_status();
        if (\base_setting::NOT_LOCKED !== $status) {
            $question_bank_setting->set_status(\base_setting::NOT_LOCKED);
        }

        $question_bank_setting->set_value(false);

        $course_id = $plan->get_courseid();
        if (empty($course_id)) {
            $question_bank_setting->set_status($status);
            return;
        }

        $course_modules = $this->get_course_modules_settings_by_item(
            $course_id,
            $item
        );

        $dependencies = $question_bank_setting->get_dependencies();
        foreach ($dependencies as $name => $dependency) {
            if (!isset($course_modules[$name])) {
                continue;
            }
            if (!$plan->setting_exists($name)) {
                continue;
            }

            $question_bank_setting->set_value(true);
            break;
        }

        $question_bank_setting->set_status($status);
    }

    public function get_name(): string
    {
        return parent::get_name() . ' (block_sharing_cart)';
    }
}
