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

namespace block_sharing_cart\task;

use async_helper;
use block_sharing_cart\app\factory as basefactory;
use block_sharing_cart\app\restore\section_plan;
use block_sharing_cart\hook\restore\after_sections_restored;
use block_sharing_cart\hook\restore\before_sections_restored;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * asynchronous_restore_task task.
 *
 * @package   block_sharing_cart
 * @copyright moxis
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class asynchronous_restore_task extends \core\task\adhoc_task
{
    /** @var section_plan|null Where the restored sections go; null when the restore has no target section. */
    private ?section_plan $sectionplan = null;

    /** @var int Section whose title and description were blanked for replacement; 0 when none. */
    private int $replacedsectionid = 0;

    /**
     * Factory used by the task; overridable in tests.
     *
     * @return basefactory
     */
    protected function factory(): basefactory {
        return basefactory::make();
    }

    /**
     * Should always resemble
     * @see \core\task\asynchronous_restore_task::execute
     * with the addition of calling
     * @see self::before_restore_finished_hook
     * and
     * @see self::after_restore_finished_hook
     *
     * Controller and backup tempdir are created here (worker), not at queue time,
     * so multi-frontend sites do not depend on a shared backuptempdir.
     */
    public function execute(): void {
        $factory = $this->factory();
        $db = $factory->moodle()->db();
        $started = time();

        $customdata = $this->get_custom_data();
        $courseid = (int)($customdata->courseid ?? 0);
        $userid = (int)$this->get_userid();

        if (empty($customdata->item) || $courseid <= 0 || $userid <= 0) {
            mtrace('Sharing cart restore task missing item, course_id, or userid; ending restore execution.');
            return;
        }

        $item = $factory->item()->entity((object)$customdata->item);
        $backupfile = $factory->item()->repository()->get_stored_file_by_item($item);
        if (!$backupfile) {
            mtrace(
                'Sharing cart backup file not found for item (id: ' . $item->get_id()
                . '); ending restore execution.'
            );
            return;
        }

        mtrace(implode(' | ', [
            'hostname=' . (gethostname() ?: 'unknown-host'),
            'itemid=' . $item->get_id(),
            'fileid=' . $backupfile->get_id(),
            'courseid=' . $courseid,
            'userid=' . $userid,
        ]));

        // Restore controller for this backup file.
        $rc = $factory->restore()->restore_controller($backupfile, $courseid, $userid);
        $restoreid = $rc->get_restoreid();

        mtrace('Processing asynchronous restore for id: ' . $restoreid);

        try {
            $restorerecord = $db->get_record(
                'backup_controllers',
                ['backupid' => $restoreid],
                'id, controller',
                MUST_EXIST
            );

            if (!$rc->execute_precheck(true)) {
                $results = $rc->get_precheck_results();
                if (!empty($results['errors'])) {
                    throw new \Exception("Errors found during restore precheck:\n" . implode("\n", $results['errors']));
                }
            }

            $rc->set_progress(new \core\progress\db_updater($restorerecord->id, 'backup_controllers', 'progress'));

            // Do some preflight checks on the restore.
            $status = $rc->get_status();
            $execution = $rc->get_execution();

            // Check that the restore is in the correct status and.
            // That is set for asynchronous execution.
            if ($status == \backup::STATUS_AWAITING && $execution == \backup::EXECUTION_DELAYED) {
                $this->before_restore_finished_hook($rc);

                // Execute the restore.
                $rc->execute_plan();

                $this->after_restore_finished_hook($rc);

                $this->discard_replaced_section_details($rc);

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

            $finished = time();
            $duration = $finished - $started;
            mtrace('Restore completed in: ' . $duration . ' seconds');

            $this->trigger_restored_event(
                $rc,
                $started,
                $finished
            );
        } catch (\Exception $e) {
            $this->rollback_replaced_section_details($rc);

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

    /**
     * retry_until_success
     *
     * @return bool
     */
    public function retry_until_success(): bool {
        return false;
    }

    /**
     * Runs before execute_plan(): decides which sections are restored and where, and lets the course format prepare.
     *
     * @param \restore_controller $restorecontroller
     * @return void
     */
    private function before_restore_finished_hook(\restore_controller $restorecontroller): void {
        try {
            mtrace('Executing before_restore_finished_hook...');

            $customdata = $this->get_custom_data();
            $backupsettings = $customdata->backup_settings ?? (object)[];

            $coursemodulestoinclude = array_map(
                'intval',
                $backupsettings->course_modules_to_include ?? []
            );
            if (!empty($coursemodulestoinclude) && $coursemodulestoinclude !== [0]) {
                $this->only_include_specified_course_modules($restorecontroller, $coursemodulestoinclude);
            }

            $movetosectionid = (int)($backupsettings->move_to_section_id ?? 0);
            $insertasnewsection = !empty($backupsettings->insert_as_new_section);
            if ($movetosectionid > 0 || $insertasnewsection) {
                $planner = $this->factory()->restore()->section_planner();

                $this->sectionplan = $planner->plan(
                    $restorecontroller,
                    $this->factory()->item()->repository()->get_by_id((int)($customdata->item->id ?? 0)),
                    $movetosectionid,
                    $insertasnewsection,
                    array_map('intval', (array)($backupsettings->sections_to_include ?? []))
                );
                $planner->apply($restorecontroller, $this->sectionplan);

                if (!empty($backupsettings->replace_section_details) && $this->sectionplan->merges_into_target()) {
                    $this->replace_section_details($restorecontroller);
                }

                \core\di::get(\core\hook\manager::class)->dispatch(new before_sections_restored(
                    restoreid: $restorecontroller->get_restoreid(),
                    courseid: (int)$restorecontroller->get_courseid(),
                    targetsectionid: $this->sectionplan->targetsectionid,
                    plannedsections: array_values($this->sectionplan->sections),
                ));
            }

            $hasatleastonecoursemoduleincluded = false;
            foreach ($restorecontroller->get_plan()->get_tasks() as $task) {
                if (($task instanceof \restore_activity_task) && $task->get_setting('included')->get_value()) {
                    $hasatleastonecoursemoduleincluded = true;
                    break;
                }
            }

            if (!$hasatleastonecoursemoduleincluded && !($this->sectionplan?->has_sections() ?? false)) {
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

    /**
     * Runs after execute_plan(): places the new sections and lets the course format attach them to its hierarchy.
     *
     * @param \restore_controller $restorecontroller
     * @return void
     */
    private function after_restore_finished_hook(\restore_controller $restorecontroller): void {
        try {
            mtrace('Executing after_restore_finished_hook...');

            if ($this->sectionplan !== null) {
                $planner = $this->factory()->restore()->section_planner();
                $courseid = (int)$restorecontroller->get_courseid();
                $targetsectionid = $this->sectionplan->targetsectionid;

                $restoredsections = $planner->resolve_restored_sections($restorecontroller, $this->sectionplan);

                mtrace('Placing ' . count($restoredsections) . ' restored nested section(s) after the target section...');
                $planner->place_after_target($courseid, $targetsectionid, $restoredsections);

                \core\di::get(\core\hook\manager::class)->dispatch(new after_sections_restored(
                    courseid: $courseid,
                    targetsectionid: $targetsectionid,
                    restoredsections: $restoredsections,
                ));

                rebuild_course_cache($courseid, true);
            }

            mtrace('Executing after_restore_finished_hook completed...');
        } catch (\Exception $e) {
            mtrace("An error occurred: " . $e->getMessage());
            mtrace($e->getTraceAsString());

            // Uh uhh, something went wrong.
            throw $e;
        }
    }

    /**
     * Blanks the target section's title and description so the restore fills them from the copy.
     *
     * @param \restore_controller $restorecontroller
     * @return void
     */
    private function replace_section_details(\restore_controller $restorecontroller): void {
        $sectionid = $this->sectionplan->targetsectionid;
        mtrace("...Replacing the title and description of section (id: $sectionid) with the copied section's");

        $this->factory()->restore()->section_details_replacement()->snapshot_and_blank(
            (int)$restorecontroller->get_courseid(),
            $sectionid
        );
        $this->replacedsectionid = $sectionid;
    }

    /**
     * Drops the snapshot taken by replace_section_details() once the restore succeeded.
     *
     * @param \restore_controller $restorecontroller
     * @return void
     */
    private function discard_replaced_section_details(\restore_controller $restorecontroller): void {
        if ($this->replacedsectionid === 0) {
            return;
        }

        $this->factory()->restore()->section_details_replacement()->discard(
            (int)$restorecontroller->get_courseid(),
            $this->replacedsectionid
        );
        $this->replacedsectionid = 0;
    }

    /**
     * Puts back the target section's title and description after a failed restore.
     *
     * @param \restore_controller $restorecontroller
     * @return void
     */
    private function rollback_replaced_section_details(\restore_controller $restorecontroller): void {
        if ($this->replacedsectionid === 0) {
            return;
        }

        try {
            mtrace("Restore failed, putting back the title and description of section (id: {$this->replacedsectionid})...");
            $this->factory()->restore()->section_details_replacement()->rollback(
                (int)$restorecontroller->get_courseid(),
                $this->replacedsectionid
            );
        } catch (\Exception $e) {
            mtrace('Could not put back the section details: ' . $e->getMessage());
        }
        $this->replacedsectionid = 0;
    }

    /**
     * only_include_specified_course_modules
     *
     * @param \restore_controller $restorecontroller
     * @param array $coursemodulestoinclude
     * @return void
     */
    private function only_include_specified_course_modules(
        \restore_controller $restorecontroller,
        array $coursemodulestoinclude
    ): void {
        mtrace("Excluding/Including activities...");

        foreach ($restorecontroller->get_plan()->get_tasks() as $task) {
            if ($task instanceof \restore_activity_task) {
                $cmid = (int)$task->get_old_moduleid();

                $includeactivity = in_array($cmid, $coursemodulestoinclude, true);
                mtrace(
                    '...' . ($includeactivity ? "Including activity: (id: $cmid)" : "Excluding activity: (id: $cmid)")
                );
                $task->get_setting('included')->set_value($includeactivity);
            }
        }
    }

    /**
     * trigger_restored_event
     *
     * @param \restore_controller $controller
     * @param int $started
     * @param int $finished
     * @return void
     */
    private function trigger_restored_event(
        \restore_controller $controller,
        int $started,
        int $finished
    ): void {
        foreach ($controller->get_plan()->get_tasks() as $task) {
            if ($task instanceof \restore_activity_task) {
                $this->trigger_restore_course_module_event($task, $started, $finished);
                continue;
            }

            if ($task instanceof \restore_section_task) {
                $this->trigger_restore_section_event($task, $started, $finished);
            }
        }
    }

    /**
     * trigger_restore_course_module_event
     *
     * @param \restore_activity_task $task
     * @param int $started
     * @param int $finished
     * @return void
     */
    private function trigger_restore_course_module_event(
        \restore_activity_task $task,
        int $started,
        int $finished
    ): void {
        if ($task->get_moduleid() === 0) {
            mtrace("Course module id was 0. Skipping event creation for this module.");
            return;
        }

        $event = \block_sharing_cart\event\restored_course_module::create_by_course_module(
            $task->get_courseid(),
            $task->get_moduleid(),
            $task->get_modulename(),
            $task->get_userid(),
            $started,
            $finished
        );
        $event->trigger();
    }

    /**
     * trigger_restore_section_event
     *
     * @param \restore_section_task $task
     * @param int $started
     * @param int $finished
     * @return void
     */
    private function trigger_restore_section_event(
        \restore_section_task $task,
        int $started,
        int $finished
    ): void {
        $event = \block_sharing_cart\event\restored_section::create_by_section(
            $task->get_courseid(),
            $task->get_sectionid(),
            $task->get_userid(),
            $started,
            $finished
        );
        $event->trigger();
    }

    /**
     * get_name
     *
     * @return string
     */
    public function get_name(): string {
        return parent::get_name() . ' (block_sharing_cart)';
    }
}
