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

/**
 * Replaces the title and description of the section a copy merges into.
 *
 * Core only writes the copied title and description into fields that are empty, so the target's fields are blanked
 * (description files included) before the restore runs and core then fills them from the backup as if the section
 * were new. The previous values are kept in a snapshot until the restore has finished, so a failed restore can put
 * them back.
 *
 * @package   block_sharing_cart
 * @copyright moxis
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class section_details_replacement {
    /** @var string Component of the snapshot file area. */
    public const COMPONENT = 'block_sharing_cart';
    /** @var string File area holding the snapshots; the item id is the section id. */
    public const FILEAREA = 'section_snapshot';

    /** @var string File holding the section's title and description. */
    private const SNAPSHOT_FILENAME = 'section.json';
    /** @var string File path prefix under which the section's description files are kept. */
    private const FILES_PREFIX = '/files';

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
     * Keep the section's title, description and description files in a snapshot, then blank them.
     *
     * @param int $courseid
     * @param int $sectionid
     * @return void
     */
    public function snapshot_and_blank(int $courseid, int $sectionid): void {
        $db = $this->basefactory->moodle()->db();
        $fs = get_file_storage();
        $context = \core\context\course::instance($courseid);

        $section = $db->get_record(
            'course_sections',
            ['id' => $sectionid, 'course' => $courseid],
            'id, name, summary, summaryformat',
            MUST_EXIST
        );

        $this->discard($courseid, $sectionid);

        $fs->create_file_from_string(
            $this->snapshot_record($context->id, $sectionid, '/', self::SNAPSHOT_FILENAME),
            json_encode([
                'name' => $section->name,
                'summary' => $section->summary,
                'summaryformat' => $section->summaryformat,
            ])
        );

        foreach ($fs->get_area_files($context->id, 'course', 'section', $sectionid, 'id', false) as $file) {
            $fs->create_file_from_storedfile(
                $this->snapshot_record(
                    $context->id,
                    $sectionid,
                    self::FILES_PREFIX . $file->get_filepath(),
                    $file->get_filename()
                ),
                $file
            );
        }

        $db->update_record('course_sections', (object)[
            'id' => $sectionid,
            'name' => null,
            'summary' => '',
            'timemodified' => time(),
        ]);
        $fs->delete_area_files($context->id, 'course', 'section', $sectionid);

        rebuild_course_cache($courseid, true);
    }

    /**
     * Put the snapshot back.
     *
     * @param int $courseid
     * @param int $sectionid
     * @return bool false when there is no snapshot for the section
     */
    public function rollback(int $courseid, int $sectionid): bool {
        $db = $this->basefactory->moodle()->db();
        $fs = get_file_storage();
        $context = \core\context\course::instance($courseid);

        $snapshot = $fs->get_file(
            $context->id,
            self::COMPONENT,
            self::FILEAREA,
            $sectionid,
            '/',
            self::SNAPSHOT_FILENAME
        );
        if (!$snapshot) {
            return false;
        }

        $details = json_decode($snapshot->get_content());

        $fs->delete_area_files($context->id, 'course', 'section', $sectionid);
        foreach ($fs->get_area_files($context->id, self::COMPONENT, self::FILEAREA, $sectionid, 'id', false) as $file) {
            if (!str_starts_with($file->get_filepath(), self::FILES_PREFIX . '/')) {
                continue;
            }
            $fs->create_file_from_storedfile([
                'contextid' => $context->id,
                'component' => 'course',
                'filearea' => 'section',
                'itemid' => $sectionid,
                'filepath' => substr($file->get_filepath(), strlen(self::FILES_PREFIX)),
                'filename' => $file->get_filename(),
            ], $file);
        }

        $db->update_record('course_sections', (object)[
            'id' => $sectionid,
            'name' => $details->name,
            'summary' => $details->summary,
            'summaryformat' => $details->summaryformat,
            'timemodified' => time(),
        ]);

        $this->discard($courseid, $sectionid);
        rebuild_course_cache($courseid, true);

        return true;
    }

    /**
     * Drops the snapshot once the restore succeeded.
     *
     * @param int $courseid
     * @param int $sectionid
     * @return void
     */
    public function discard(int $courseid, int $sectionid): void {
        get_file_storage()->delete_area_files(
            \core\context\course::instance($courseid)->id,
            self::COMPONENT,
            self::FILEAREA,
            $sectionid
        );
    }

    /**
     * Whether a snapshot is kept for the section.
     *
     * @param int $courseid
     * @param int $sectionid
     * @return bool
     */
    public function has_snapshot(int $courseid, int $sectionid): bool {
        return get_file_storage()->file_exists(
            \core\context\course::instance($courseid)->id,
            self::COMPONENT,
            self::FILEAREA,
            $sectionid,
            '/',
            self::SNAPSHOT_FILENAME
        );
    }

    /**
     * File record for a file in the section's snapshot area.
     *
     * @param int $contextid
     * @param int $sectionid
     * @param string $filepath
     * @param string $filename
     * @return array
     */
    private function snapshot_record(int $contextid, int $sectionid, string $filepath, string $filename): array {
        return [
            'contextid' => $contextid,
            'component' => self::COMPONENT,
            'filearea' => self::FILEAREA,
            'itemid' => $sectionid,
            'filepath' => $filepath,
            'filename' => $filename,
        ];
    }
}
