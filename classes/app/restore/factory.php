<?php

namespace block_sharing_cart\app\restore;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use block_sharing_cart\app\factory as base_factory;

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

class factory
{
    private base_factory $base_factory;

    public function __construct(base_factory $base_factory)
    {
        $this->base_factory = $base_factory;
    }

    public function restore_controller(\stored_file $backup_file, int $course_id, int $user_id): \restore_controller
    {
        $backupdir = \restore_controller::get_tempdir_name($course_id, $user_id);
        $path = make_backup_temp_directory($backupdir);

        $fp = get_file_packer('application/vnd.moodle.backup');
        $fp->extract_to_pathname($backup_file, $path);

        return new \restore_controller(
            $backupdir,
            $course_id,
            \backup::INTERACTIVE_NO,
            \backup::MODE_ASYNC,
            $user_id,
            \backup::TARGET_EXISTING_ADDING,
            releasesession: \backup::RELEASESESSION_YES
        );
    }

    public function handler(): handler
    {
        return new handler($this->base_factory);
    }
}