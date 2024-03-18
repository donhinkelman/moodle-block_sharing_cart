<?php

namespace block_sharing_cart\task;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use block_sharing_cart\app\factory;
use async_helper;
use block_sharing_cart\app\item\entity;

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

class asynchronous_restore_task extends \core\task\adhoc_task
{
    /**
     * Should always resemble
     * @see \core\task\asynchronous_restore_task::execute
     * with the addition of calling
     * @see self::before_restore_finished_hook
     * and
     * @see self::after_restore_finished_hook
     */
    public function execute(): void
    {
        global $DB;
        $started = time();

        $customdata = $this->get_custom_data();
        $restoreid = $customdata->backupid;
        $restorerecord = $DB->get_record(
            'backup_controllers',
            ['backupid' => $restoreid],
            'id, controller',
            IGNORE_MISSING
        );
        // If the record doesn't exist, the backup controller failed to create. Unable to proceed.
        if (empty($restorerecord)) {
            mtrace('Unable to find restore controller, ending restore execution.');
            return;
        }

        mtrace('Processing asynchronous restore for id: ' . $restoreid);

        // Get the backup controller by backup id. If controller is invalid, this task can never complete.
        if ($restorerecord->controller === '') {
            mtrace('Bad restore controller status, invalid controller, ending restore execution.');
            return;
        }
        $rc = \restore_controller::load_controller($restoreid);
        try {
            $rc->set_progress(new \core\progress\db_updater($restorerecord->id, 'backup_controllers', 'progress'));

            // Do some preflight checks on the restore.
            $status = $rc->get_status();
            $execution = $rc->get_execution();

            // Check that the restore is in the correct status and
            // that is set for asynchronous execution.
            if ($status == \backup::STATUS_AWAITING && $execution == \backup::EXECUTION_DELAYED) {
                $this->before_restore_finished_hook($rc);

                // Execute the restore.
                $rc->execute_plan();

                $this->after_restore_finished_hook($rc);

                // Send message to user if enabled.
                $messageenabled = (bool)get_config('backup', 'backup_async_message_users');
                if ($messageenabled && $rc->get_status() == \backup::STATUS_FINISHED_OK) {
                    $asynchelper = new async_helper('restore', $restoreid);
                    $asynchelper->send_message();
                }
            } else {
                // If status isn't 700, it means the process has failed.
                // Retrying isn't going to fix it, so marked operation as failed.
                $rc->set_status(\backup::STATUS_FINISHED_ERR);
                mtrace('Bad backup controller status, is: ' . $status . ' should be 700, marking job as failed.');
            }

            $duration = time() - $started;
            mtrace('Restore completed in: ' . $duration . ' seconds');
        } catch (\Exception $e) {
            // If an exception is thrown, mark the restore as failed.
            $rc->set_status(\backup::STATUS_FINISHED_ERR);

            // Retrying isn't going to fix this, so add a no-retry flag to customdata.
            // We can cancel the task in the task manager.
            $customdata->noretry = true;
            $this->set_custom_data($customdata);

            mtrace('Exception thrown during restore execution, marking job as failed.');
            mtrace($e->getMessage());
        } finally {
            // Cleanup.
            // Always destroy the controller.
            $rc->destroy();
        }
    }

    private function after_restore_finished_hook(\restore_controller $restore_controller): void
    {
        try {
            $customdata = $this->get_custom_data();

            $backup_settings = $customdata->backup_settings ?? null;

            $move_to_section_id = $backup_settings->move_to_section_id ?? null;
            if ($move_to_section_id) {
                $this->move_course_modules_to_section_id($restore_controller, $move_to_section_id);
            }
        } catch (\Exception $e) {
            // Uh uhh, something went wrong.
            throw $e;
        }
    }

    private function before_restore_finished_hook(\restore_controller $restore_controller): void {}

    private function move_course_modules_to_section_id(\restore_controller $restore_controller, int $section_id): void
    {
        global $DB;

        $section = $DB->get_record('course_sections', ['id' => $section_id], strictness: MUST_EXIST);

        foreach ($restore_controller->get_plan()->get_tasks() as $task) {
            if ($task instanceof \restore_activity_task) {
                $cmid = $task->get_moduleid();
                $cm = get_coursemodule_from_id(null, $cmid, strictness: MUST_EXIST);
                moveto_module($cm, $section);

                // Fire event.
                $event = \core\event\course_module_created::create_from_cm($cm);
                $event->trigger();
            }
        }
    }
}
