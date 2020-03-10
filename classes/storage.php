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

defined('MOODLE_INTERNAL') || die();

/**
 *  Sharing Cart file storage manager
 */
class storage {
    const COMPONENT = 'user';
    const FILEAREA = 'backup';
    const ITEMID = 0;
    const FILEPATH = '/';

    /** @var \file_storage */
    private $storage;
    /** @var \context */
    private $context;

    /**
     *  Constructor
     *
     * @param int $userid = $USER->id
     */
    public function __construct($userid = null) {
        global $USER;
        $this->storage = \get_file_storage();
        $this->context = \context_user::instance($userid ?: $USER->id);
    }

    /**
     *  Copy a stored file into storage
     *
     * @param \stored_file $file
     */
    public function copy_from(\stored_file $file) {
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
     * @return \stored_file
     */
    public function get($filename) {
        return $this->storage->get_file($this->context->id,
                self::COMPONENT, self::FILEAREA, self::ITEMID, self::FILEPATH,
                $filename);
    }

    /**
     *  Delete a file in the storage by filename
     *
     * @param string $filename
     * @return boolean
     */
    public function delete($filename) {
        $file = $this->get($filename);
        return $file && $file->delete();
    }
}
