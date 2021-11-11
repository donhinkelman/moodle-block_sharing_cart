<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *  Sharing Cart
 *
 * @package    block_sharing_cart
 * @copyright  2017 (C) VERSION2, INC.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_sharing_cart;

use block_sharing_cart\exceptions\cannot_find_file_exception;
use context;
use context_user;
use file_exception;
use file_storage;
use stored_file;
use stored_file_creation_exception;
use function get_file_storage;

defined('MOODLE_INTERNAL') || die();

/**
 *  Sharing Cart file storage manager
 */
class storage {
    public const COMPONENT = 'user';
    public const FILEAREA = 'backup';
    public const ITEMID = 0;
    public const FILEPATH = '/';

    /** @var file_storage */
    private $storage;
    /** @var context */
    private $context;

    /**
     *  Constructor
     *
     * @param int $userid = $USER->id
     */
    public function __construct($userid = null) {
        global $USER;
        $this->storage = get_file_storage();
        $this->context = context_user::instance($userid ?: $USER->id);
    }

    /**
     *  Copy a stored file into storage
     *
     * @param stored_file $file
     * @throws file_exception
     * @throws stored_file_creation_exception
     */
    public function copy_from(stored_file $file): void {
        $filerecord = (object) array(
                'contextid' => $this->context->id,
                'component' => self::COMPONENT,
                'filearea' => self::FILEAREA,
                'itemid' => self::ITEMID,
                'filepath' => self::FILEPATH,
        );
        $this->storage->create_file_from_storedfile($filerecord, $file);
    }

    /**
     *  Get a stored_file instance by filename
     *
     * @param string $filename
     * @return stored_file
     * @throws cannot_find_file_exception
     */
    public function get(string $filename): stored_file {
        $file = $this->storage->get_file($this->context->id,
            self::COMPONENT, self::FILEAREA, self::ITEMID, self::FILEPATH,
            $filename);

        if ($file === false) {
            throw new cannot_find_file_exception(
                $filename,
                $this->context->id,
                self::ITEMID,
                self::COMPONENT,
                self::FILEAREA,
                self::FILEPATH
            );
        }

        return $file;
    }

    /**
     *  Delete a file in the storage by filename
     *
     * @param string $filename
     * @return boolean
     */
    public function delete(string $filename): bool {
        try {
            $file = $this->get($filename);
            return $file && $file->delete();
        }
        catch (\Exception $exception) {
        }

        return false;
    }
}
