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

namespace block_sharing_cart\app\restore;

use block_sharing_cart\app\factory as basefactory;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * Restore factory for the Sharing Cart block.
 *
 * @package   block_sharing_cart
 * @copyright moxis
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class factory
{
    /** @var basefactory $basefactory */
    private basefactory $basefactory;

    /**
     * __construct
     *
     * @param basefactory $basefactory
     */
    public function __construct(basefactory $basefactory) {
        $this->basefactory = $basefactory;
    }

    /**
     * extract_backup_file_to_tempdir
     *
     * @param \stored_file $backupfile
     * @param string $backupdir
     * @return string
     */
    public function extract_backup_file_to_tempdir(
        \stored_file $backupfile,
        string $backupdir
    ): string {
        $path = make_backup_temp_directory($backupdir);

        $fp = get_file_packer('application/vnd.moodle.backup');
        $result = $fp->extract_to_pathname($backupfile, $path);
        if ($result === false) {
            throw new \moodle_exception(
                'error',
                'error',
                '',
                null,
                'Failed to extract sharing cart backup file to temp directory: ' . $path
            );
        }

        return $path;
    }

    /**
     * assert_backup_file_looks_valid
     *
     * @param \stored_file $backupfile
     * @return void
     */
    public function assert_backup_file_looks_valid(\stored_file $backupfile): void {
        $fp = get_file_packer('application/vnd.moodle.backup');
        $files = $fp->list_files($backupfile);
        if ($files === false || empty($files)) {
            throw new \moodle_exception(
                'error',
                'error',
                '',
                null,
                'Sharing cart backup file is not a readable MBZ (item file id: '
                . $backupfile->get_id() . ')'
            );
        }

        $hasmoodlebackupxml = false;
        foreach ($files as $fileinfo) {
            $name = is_object($fileinfo)
                ? ($fileinfo->pathname ?? $fileinfo->name ?? '')
                : (string)$fileinfo;
            $name = ltrim(str_replace('\\', '/', $name), './');
            if ($name === 'moodle_backup.xml' || str_ends_with($name, '/moodle_backup.xml')) {
                $hasmoodlebackupxml = true;
                break;
            }
        }

        if (!$hasmoodlebackupxml) {
            throw new \moodle_exception(
                'error',
                'error',
                '',
                null,
                'Sharing cart backup file is missing moodle_backup.xml (item file id: '
                . $backupfile->get_id() . ')'
            );
        }
    }

    /**
     * restore_controller
     *
     * @param \stored_file $backupfile
     * @param int $courseid
     * @param int $userid
     * @return \restore_controller
     */
    public function restore_controller(\stored_file $backupfile, int $courseid, int $userid): \restore_controller {
        $backupdir = \restore_controller::get_tempdir_name($courseid, $userid);
        $this->extract_backup_file_to_tempdir($backupfile, $backupdir);

        return new \restore_controller(
            $backupdir,
            $courseid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_ASYNC,
            $userid,
            \backup::TARGET_EXISTING_ADDING,
            releasesession: \backup::RELEASESESSION_YES
        );
    }

    /**
     * handler
     *
     * @return handler
     */
    public function handler(): handler {
        return new handler($this->basefactory);
    }

    /**
     * section_planner
     *
     * @return section_planner
     */
    public function section_planner(): section_planner {
        return new section_planner($this->basefactory);
    }

    /**
     * section_details_replacement
     *
     * @return section_details_replacement
     */
    public function section_details_replacement(): section_details_replacement {
        return new section_details_replacement($this->basefactory);
    }
}
