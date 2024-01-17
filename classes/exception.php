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
 *  Sharing Cart exception
 */
class exception extends \moodle_exception {
    public const CODE_BACKUP_NOT_FOUND = 'backupnotfound';
    public const CODE_INVALID_OPERATION = 'invalidoperation';
    public const CODE_FORBIDDEN = 'forbidden';
    public const CODE_UNEXPECTED_ERROR = 'unexpectederror';

    /**
     *  Constructor
     *
     * @param string $errcode The error string ID
     * @param mixed $a (Optional) Additional parameter
     */
    public function __construct(string $errcode, $a = null) {
        parent::__construct($errcode, 'block_sharing_cart', '', $a);
    }

    public static function from_backup_not_found(): self
    {
        return new self(self::CODE_BACKUP_NOT_FOUND);
    }

    public static function from_forbidden(): self
    {
        return new self(self::CODE_FORBIDDEN);
    }

    public static function from_unexpected_error(): self
    {
        return new self(self::CODE_UNEXPECTED_ERROR);
    }

    public static function from_invalid_operation(): self
    {
        return new self(self::CODE_INVALID_OPERATION);
    }
}
