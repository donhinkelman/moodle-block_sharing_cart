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

namespace block_sharing_cart\repositories;


use core\context;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class backup_options
{
    private static array $default_settings = [
        'role_assignments' => false,
        'activities' => true,
        'blocks' => false,
        'filters' => false,
        'comments' => false,
        'calendarevents' => false,
        'userscompletion' => false,
        'logs' => false,
        'grade_histories' => false,
        'users' => false,
        'anonymize' => false,
        'badges' => false
    ];

    private array $settings = [];

    public function __construct(array $settings = [])
    {
        $this->settings = $settings;
    }

    public function set_include_user_data(bool $value, context $context): self
    {
        if (!$value) {
            $this->settings['users'] = $value;
        }
        else if (has_capability('moodle/backup:userinfo', $context)) {
            $this->settings['users'] = $value;
        }
        return $this;
    }

    public function set_include_badge(bool $value): self
    {
        $this->settings['badges'] = $value;
        return $this;
    }

    public function get_settings(): array
    {
        return $this->settings + self::$default_settings;
    }
}
