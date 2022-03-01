<?php
/**
 * @package     block_sharing_cart
 * @author      Sam Møller (https://github.com/sampraxis)
 */

namespace block_sharing_cart\integration\storage;

use advanced_testcase;
use block_sharing_cart\exceptions\cannot_find_file_exception;
use block_sharing_cart\storage;
use context_user;
use dml_exception;
use moodle_database;

defined('MOODLE_INTERNAL') || die();

class storage_test extends advanced_testcase
{
    /**
     * @inheridoc
     */
    public function setUp(): void {
        $this->resetAfterTest();
    }

    public function test_get_non_existent_file_expect_cannot_find_file_exception(): void {
        $this->expectException(cannot_find_file_exception::class);

        $user = self::getDataGenerator()->create_user();
        $filename = $this->get_unique_filename_by_user_id($user->id);
        $store = new storage($user->id);
        $store->get($filename);
    }

    public function test_get_stored_file(): void {
        $user = self::getDataGenerator()->create_user();
        $content = 'This is a text in the test file';
        $record = $this->create_file_record_by_user_id($user->id);
        $record->filename = $this->get_unique_filename_by_user_id($user->id);

        $file_storage = get_file_storage();
        $expected_file = $file_storage->create_file_from_string(
            $record,
            $content
        );

        $storage = new storage($user->id);
        $actual_file = $storage->get($record->filename);

        $this->assertSame((int)$expected_file->get_id(), (int)$actual_file->get_id());
        $this->assertSame($content, $actual_file->get_content());
        $this->assertSame($record->filename, $actual_file->get_filename());
    }

    /**
     * @param int $user_id
     * @return string
     * @throws dml_exception
     * @throws Exception
     */
    private function get_unique_filename_by_user_id(int $user_id): string {
        $filenames = $this->get_filenames_by_user_id($user_id);
        $occupied_names = array_flip($filenames);

        do  {
            $name = hash('md5', random_bytes(16));
            if ($name === false) {
                throw new Exception('Unable to randomize the filename with md5 hash function');
            }
        }
        while (isset($occupied_names[$name]));

        return $name;
    }

    /**
     * @param int $id
     * @return string[]
     * @throws dml_exception
     */
    private function get_filenames_by_user_id(int $id): array {
        $sql = "contextid = :contextid
        AND userid = :userid
        AND component = :component
        AND filearea = :filearea
        AND itemid = :itemid
        AND filepath = :filepath";

        $params = (array)$this->create_file_record_by_user_id($id);

        return $this->db()->get_fieldset_select(
            'files',
            'filename',
            $sql,
            $params
        );
    }

    /**
     * @param int $id
     * @return object
     */
    private function create_file_record_by_user_id(int $id): object {
        return (object)[
            'contextid' => context_user::instance($id)->id,
            'userid' => $id,
            'component' => storage::COMPONENT,
            'filearea' => storage::FILEAREA,
            'itemid' => storage::ITEMID,
            'filepath' => storage::FILEPATH,
        ];
    }

    /**
     * @return moodle_database
     */
    private function db(): moodle_database {
        global $DB;
        return $DB;
    }
}
