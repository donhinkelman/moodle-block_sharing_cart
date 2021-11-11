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

namespace block_sharing_cart\files;

defined('MOODLE_INTERNAL') || die();


class cleaner
{
    /** @var \moodle_database */
    private $db;

    /** @var file */
    private $file;

    /**
     * cleaner constructor.
     * @param \moodle_database $database
     * @param object|file $file
     */
    public function __construct(\moodle_database $database, $file){
        $this->db = $database;
        $this->file = new file($file);
    }

    /**
     * @throws \dml_exception
     */
    public function remove_related_sharing_cart_entity(): void {
        // Exit if deleted file was not from backup area
        if (!$this->file->is_backup_file()) {
            return;
        }

        // Exit if deleted file cannot be delete from sharing cart table
        // (Most likely that related entity doesn't exist in the table)
        if (!$this->can_delete()) {
            return;
        }

        $this->db->delete_records(
            'block_sharing_cart',
            $this->get_sharing_cart_file_parameters()
        );
    }

    /**
     * @return bool
     * @throws \dml_exception
     */
    private function can_delete(): bool {
        return $this->db->record_exists($this->get_table_name(), $this->get_sharing_cart_file_parameters());
    }

    /**
     * @return array
     */
    private function get_sharing_cart_file_parameters(): array {
        return [
            'userid' => $this->file->get_user_id(),
            'filename' => $this->file->get_name()
        ];
    }

    /**
     * @return string
     */
    private function get_table_name(): string {
        return 'block_sharing_cart';
    }
}
