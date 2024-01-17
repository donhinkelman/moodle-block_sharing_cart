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


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class restore_task_options
{
    private array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function get_sharing_cart_id(): int
    {
        return $this->data['sharing_cart_id'] ?? 0;
    }

    public function get_course_id(): int
    {
        return $this->data['course_id'] ?? 0;
    }

    public function get_section_number(): int
    {
        return $this->data['section_number'] ?? 0;
    }

    public function get_user_id(): int
    {
        return $this->data['user_id'] ?? 0;
    }

    public function to_array(): array
    {
        return $this->data;
    }

    public static function create_by_json(string $json): self
    {
        return new self(json_decode($json, true));
    }
}
