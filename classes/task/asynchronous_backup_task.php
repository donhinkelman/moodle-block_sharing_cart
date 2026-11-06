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

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

use async_helper;
use block_sharing_cart\app\factory as basefactory;
use block_sharing_cart\app\item\entity;

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/moodle2/backup_plan_builder.class.php');

/**
 * asynchronous_backup_task task.
 *
 * @package   block_sharing_cart
 * @copyright moxis
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class asynchronous_backup_task extends \core\task\adhoc_task {
    /** @var bool $output Whether to output progress. */
    protected bool $output = true;
    /** @var ?basefactory $basefactory */
    protected ?basefactory $basefactory = null;
    /** @var ?\backup_controller $controller */
    private ?\backup_controller $controller = null;

    /**
     * factory
     *
     * @return basefactory
     */
    protected function factory(): basefactory {
        return $this->basefactory ??= basefactory::make();
    }

    /**
     * db
     *
     * @return \moodle_database
     */
    protected function db(): \moodle_database {
        return $this->factory()->moodle()->db();
    }

    /**
     * get_backup_id
     *
     * @return string
     */
    protected function get_backup_id(): string {
        return $this->get_custom_data()->backupid ?? '';
    }

    /**
     * output
     *
     * @param string $message
     * @return void
     */
    protected function output(string $message): void {
        if (!$this->output) {
            return;
        }
        mtrace($message);
    }

    /**
     * get_backup_controller
     *
     * @return ?\backup_controller
     */
    public function get_backup_controller(): ?\backup_controller {
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
    public function execute(): void {
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

            // Check that the backup is in the correct status and.
            // That is set for asynchronous execution.
            if ($status == \backup::STATUS_AWAITING && $execution == \backup::EXECUTION_DELAYED) {
                $this->before_backup_started_hook($bc);

                // Ensure $CFG backup version/release match backup::VERSION in this process.
                // Controller construction (often another process) already bumped the DB; load_controller
                // does not re-apply, so workers / Behat CLI would otherwise write install defaults
                // (2008111700 / 2.0 dev) into moodle_backup.xml.
                \backup_controller_dbops::apply_version_and_release();

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

    /**
     * retry_until_success
     *
     * @return bool
     */
    public function retry_until_success(): bool {
        return false;
    }

    /**
     * before_backup_started_hook
     *
     * @param \backup_controller $backupcontroller
     * @return void
     */
    protected function before_backup_started_hook(\backup_controller $backupcontroller): void {
        try {
            $this->output('Executing before_backup_started_hook...');
            $customdata = $this->get_custom_data();

            $db = $this->db();
            $itementity = $this->factory()->item()->repository()->get_by_id($customdata->item->id);

            if ($itementity->get_type() === 'section' || $itementity->get_type() === 'mod_subsection') {
                $db->get_record(
                    'course_sections',
                    ['id' => $itementity->get_old_instance_id()],
                    strictness: MUST_EXIST
                );
            } else {
                $db->get_record(
                    'course_modules',
                    ['id' => $itementity->get_old_instance_id()],
                    strictness: MUST_EXIST
                );
            }

            $backupcontrollercontext = $this->get_backup_controller_context($backupcontroller);

            // Construct backup plan settings.
            $backupplansettings = $this->factory()->backup()->settings_helper()->construct_backup_plan_settings(
                $customdata,
                $backupcontrollercontext,
                $itementity
            );

            $backupplan = $backupcontroller->get_plan();

            // Apply the backup plan settings to the backup plan.
            $this->factory()->backup()->settings_helper()->apply_backup_plan_settings($backupplansettings, $backupplan);

            $this->toggle_question_bank_setting($backupplan, $itementity);

            $this->filter_away_disabled_course_modules($backupcontroller);

            $this->output('Executing before_backup_started_hook completed, continuing with backup...');
        } catch (\Exception $e) {
            $this->output("An error occurred during before_backup_started_hook");
            throw $e;
        }
    }

    /**
     * after_backup_finished_hook
     *
     * @param \backup_controller $backupcontroller
     * @return void
     */
    protected function after_backup_finished_hook(\backup_controller $backupcontroller): void {
        try {
            $this->output('Executing after_backup_finished_hook...');

            $customdata = $this->get_custom_data();
            $item = $customdata->item ?? null;
            $rootitem = $this->factory()->item()->repository()->get_by_id($item->id);
            if (!$rootitem) {
                throw new \Exception(
                    "Couldn't fetch item (id: {$item->id})"
                );
            }

            if ($backupcontroller->get_status() === \backup::STATUS_FINISHED_ERR) {
                throw new \Exception("Backup failed");
            }

            $this->output("Fetching backup results...");
            $backupresults = $backupcontroller->get_results();

            // Type annotation.
            $file = $backupresults['backup_destination'] ?? null;
            if (!$file) {
                $this->output('Backup results keys: ' . implode(', ', array_keys((array)$backupresults)));
                throw new \Exception("No backup file found in results");
            }

            $this->output("Copying backup file into sharing cart...");
            $sharingcartfile = $this->copy_backup_file_to_sharing_cart_filearea($file, $rootitem);

            $this->output("Deleting original backup file...");
            $file->delete();

            $this->output("Updating items in sharing cart using contents of backup file...");
            $sectiontree = array_map(
                static fn(object|array $node): array => (array)$node,
                (array)($customdata->backup_settings->section_tree ?? [])
            );
            $this->factory()->item()->repository()->update_sharing_cart_item_with_backup_file(
                $rootitem,
                $sharingcartfile,
                $sectiontree
            );

            $this->output('Executing after_backup_finished_hook completed...');
        } catch (\Exception $e) {
            $this->output("An error occurred during after_backup_finished_hook");
            throw $e;
        }
    }

    /**
     * get_backup_controller_context
     *
     * @param \backup_controller $backupcontroller
     * @return \core\context
     */
    private function get_backup_controller_context(\backup_controller $backupcontroller): \core\context {
        switch ($backupcontroller->get_type()) {
            case \backup::TYPE_1COURSE:
                $courseid = $backupcontroller->get_id();
                return \core\context\course::instance($courseid);
            case \backup::TYPE_1SECTION:
                $courseid = $backupcontroller->get_courseid();
                return \core\context\course::instance($courseid);
            case \backup::TYPE_1ACTIVITY:
                $coursemoduleid = $backupcontroller->get_id();
                return \core\context\module::instance($coursemoduleid);
            default:
                throw new \Exception('Unknown backup instance type');
        }
    }

    /**
     * copy_backup_file_to_sharing_cart_filearea
     *
     * @param \stored_file $file
     * @param entity $rootitem
     * @return \stored_file
     */
    private function copy_backup_file_to_sharing_cart_filearea(\stored_file $file, entity $rootitem): \stored_file {
        // File storage instance.
        $fs = get_file_storage();

        return $fs->create_file_from_storedfile([
            'contextid' => \context_user::instance($rootitem->get_user_id())->id,
            'component' => 'block_sharing_cart',
            'filearea' => 'backup',
            'itemid' => $rootitem->get_id(),
            'filepath' => '/',
            'filename' => $file->get_filename(),
        ], $file);
    }

    /**
     * filter_away_disabled_course_modules
     *
     * @param \backup_controller $backupcontroller
     * @return void
     */
    private function filter_away_disabled_course_modules(
        \backup_controller $backupcontroller
    ): void {
        $db = $this->db();

        $this->output("Excluding activities which are disabled on the site...");

        foreach ($backupcontroller->get_plan()->get_tasks() as $task) {
            if ($task instanceof \backup_activity_task) {
                $cmid = (int)$task->get_moduleid();
                $modulename = $task->get_modulename();

                $includeactivity = $db->get_record('modules', [
                        'name' => $modulename,
                        'visible' => true,
                    ]) !== false;

                if ($includeactivity === false) {
                    $this->output('...' . ("Excluding activity: (id: $cmid)"));
                    $task->get_setting('included')->set_value(false);
                }
            }
        }
    }

    /**
     * fail_task
     *
     * @return void
     */
    private function fail_task(): void {
        $db = $this->db();

        $this->output("Async backup failed, trying to set item status to failed...");

        $customdata = $this->get_custom_data();
        $item = $customdata->item ?? null;
        $rootitem = $this->factory()->item()->repository()->get_by_id($item->id);
        if (!$rootitem) {
            $table = "{$db->get_prefix()}{$this->factory()->item()->repository()->get_table()}";
            $this->output(
                "Couldn't fetch item (id: {$item->id}) from {$table}, aborting..."
            );
            return;
        }

        $rootitem->set_status(entity::STATUS_BACKUP_FAILED);
        $this->factory()->item()->repository()->update($rootitem);

        $this->output("Async backup failed, item status has been set to failed, aborting...");
    }

    /**
     * get_course_modules_settings_by_item
     *
     * @param int $courseid
     * @param entity $item
     * @return array
     */
    private function get_course_modules_settings_by_item(
        int $courseid,
        entity $item
    ): array {
        try {
            $itemid = $item->get_old_instance_id();
            if (empty($itemid)) {
                return [];
            }

            $modinfo = get_fast_modinfo($courseid);
            $cms = [];

            if ($item->get_type() === 'section') {
                // The copied section plus the descendant sections declared by a nesting course format.
                $sectionids = array_merge(
                    [$itemid],
                    \block_sharing_cart\app\backup\backup_settings_helper::get_section_tree_ids(
                        (object)($this->get_custom_data()->backup_settings ?? [])
                    )
                );
                foreach ($sectionids as $sectionid) {
                    $section = $modinfo->get_section_info_by_id($sectionid, IGNORE_MISSING);
                    if (!$section || empty($section->sequence)) {
                        continue;
                    }
                    $cms = array_merge($cms, array_map(static function ($id) {
                        return (int)$id;
                    }, explode(',', $section->sequence)));
                }
                if (empty($cms)) {
                    return [];
                }
            } else {
                $cms[] = $itemid;
            }

            $settings = [];
            foreach ($cms as $id) {
                $cm = $modinfo->get_cm($id);
                $name = "{$cm->modname}_{$cm->id}_included";
                $settings[$name] = $id;
            }
            return $settings;
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * toggle_question_bank_setting
     *
     * @param \backup_plan $plan
     * @param entity $item
     * @return void
     */
    private function toggle_question_bank_setting(
        \backup_plan $plan,
        entity $item
    ): void {
        if (!$plan->setting_exists('questionbank')) {
            return;
        }

        $questionbanksetting = $plan->get_setting('questionbank');
        $status = $questionbanksetting->get_status();
        if (\base_setting::NOT_LOCKED !== $status) {
            $questionbanksetting->set_status(\base_setting::NOT_LOCKED);
        }

        $questionbanksetting->set_value(false);

        $courseid = $plan->get_courseid();
        if (empty($courseid)) {
            $questionbanksetting->set_status($status);
            return;
        }

        $coursemodules = $this->get_course_modules_settings_by_item(
            $courseid,
            $item
        );

        $dependencies = $questionbanksetting->get_dependencies();
        foreach ($dependencies as $name => $dependency) {
            if (!isset($coursemodules[$name])) {
                continue;
            }
            if (!$plan->setting_exists($name)) {
                continue;
            }

            $questionbanksetting->set_value(true);
            break;
        }

        $questionbanksetting->set_status($status);
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
