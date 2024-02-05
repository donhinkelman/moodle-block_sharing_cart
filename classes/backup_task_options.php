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


use block_sharing_cart\repositories\backup_options;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class backup_task_options
{
    private array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function get_cm_id(): int
    {
        return $this->data['cm_id'] ?? 0;
    }

    public function get_course_id(): int
    {
        return $this->data['course_id'] ?? 0;
    }

    public function get_section_id(): int
    {
        return $this->data['section_id'] ?? 0;
    }

    public function get_user_id(): int
    {
        return $this->data['user_id'] ?? 0;
    }

    public function get_settings(): array
    {
        return $this->data['settings'] ?? [];
    }

    public function get_sharing_cart_record(): ?object
    {
        if (isset($this->data['sharing_cart_record'])) {
            return (object)$this->data['sharing_cart_record'];
        }
        return null;
    }

    public function get_backup_options(): backup_options
    {
        return new backup_options($this->get_settings());
    }

    public static function create_by_json(string $json): self
    {
        return new self(json_decode($json, true));
    }
}
