<?php

namespace block_sharing_cart\task;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use async_helper;
use block_sharing_cart\app\factory as base_factory;

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
        $db = base_factory::make()->moodle()->db();
        $started = time();

        $customdata = $this->get_custom_data();
        $restoreid = $customdata->backupid;
        $restorerecord = $db->get_record(
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

    public function retry_until_success(): bool
    {
        return false;
    }

    private function after_restore_finished_hook(\restore_controller $restore_controller): void
    {
        try {
            mtrace('Executing after_restore_finished_hook...');

            $customdata = $this->get_custom_data();

            mtrace('Executing after_restore_finished_hook completed...');
        } catch (\Exception $e) {
            mtrace("An error occurred: " . $e->getMessage());
            mtrace($e->getTraceAsString());

            // Uh uhh, something went wrong.
            throw $e;
        }
    }

    private function before_restore_finished_hook(\restore_controller $restore_controller): void
    {
        try {
            mtrace('Executing before_restore_finished_hook...');

            $customdata = $this->get_custom_data();

            $backup_settings = $customdata->backup_settings ?? null;

            $move_to_section_id = $backup_settings->move_to_section_id ?? null;
            if ($move_to_section_id) {
                $this->update_section_number($restore_controller, $move_to_section_id);
            }

            $course_modules_to_include = array_map('intval', $backup_settings->course_modules_to_include ?? []);
            if (!empty($course_modules_to_include) && $course_modules_to_include !== [0]) {
                $this->only_include_specified_course_modules($restore_controller, $course_modules_to_include);
            }

            $has_atleast_one_course_module_included = false;
            foreach ($restore_controller->get_plan()->get_tasks() as $task) {
                if (($task instanceof \restore_activity_task) && $task->get_setting('included')->get_value()) {
                    $has_atleast_one_course_module_included = true;
                    break;
                }
            }

            if (!$has_atleast_one_course_module_included) {
                throw new \Exception('No course modules were included in the restore.');
            }

            mtrace('Executing before_restore_finished_hook completed, continuing with restore');
        } catch (\Exception $e) {
            mtrace("An error occurred: " . $e->getMessage());
            mtrace($e->getTraceAsString());

            // Uh uhh, something went wrong.
            throw $e;
        }
    }

    private function update_section_number(\restore_controller $restore_controller, int $section_id): void
    {
        $db = base_factory::make()->moodle()->db();

        $new_section_number = $db->get_field(
            'course_sections',
            'section',
            ['id' => $section_id],
            strictness: MUST_EXIST
        );

        /**
         * Dirty hack which updates the section number in the section.xml & module.xml files.
         * This is necessary because the section number is hardcoded in the section.xml & module.xml files and cannot be changed
         * through the restore_controller API or any other way. ;(
         */
        foreach ($restore_controller->get_plan()->get_tasks() as $task) {
            // Make sure we import into the correct section
            if ($task instanceof \restore_activity_task) {
                $module_xml_path = "{$task->get_taskbasepath()}/module.xml";

                $module_xml = simplexml_load_string(
                    file_get_contents($module_xml_path)
                );
                $module_xml->sectionnumber = $new_section_number;

                $module_xml->asXML($module_xml_path);
            }

            // Overwrite empty/missing section settings in the target section
            if ($task instanceof \restore_section_task) {
                $section_xml_path = "{$task->get_taskbasepath()}/section.xml";

                $section_xml = simplexml_load_string(
                    file_get_contents($section_xml_path)
                );
                $section_xml->number = $new_section_number;

                $section_xml->asXML($section_xml_path);
            }
        }
    }

    private function only_include_specified_course_modules(
        \restore_controller $restore_controller,
        array $course_modules_to_include
    ): void {
        mtrace("Excluding/Including activities...");

        foreach ($restore_controller->get_plan()->get_tasks() as $task) {
            if ($task instanceof \restore_activity_task) {
                $cm_id = (int)$task->get_old_moduleid();

                $include_activity = in_array($cm_id, $course_modules_to_include, true);
                mtrace(
                    '...' . ($include_activity ? "Including activity: (id: $cm_id)" : "Excluding activity: (id: $cm_id)")
                );

                $task->get_setting('included')->set_value($include_activity);
            }
        }
    }
}
