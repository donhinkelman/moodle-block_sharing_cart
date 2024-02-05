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

use block_sharing_cart\exception as sharing_cart_exception;

/**
 *  Sharing Cart record manager
 * @property int $id
 * @property int $userid
 * @property string $modname
 * @property string $modicon
 * @property string $modtext
 * @property int $ctime
 * @property string $filename
 * @property string $tree
 * @property int $weight
 * @property int $course
 * @property int $section
 * @property int $fileid
 */
class record implements \ArrayAccess, \JsonSerializable {
    public const TABLE = 'block_sharing_cart';

    public const WEIGHT_BOTTOM = 9999;
    private array $data = [];

    /**
     *  Constructor
     *
     * @param mixed $record = empty
     */
    public function __construct($record = []) {
        global $USER;

        $record = (array)$record;
        foreach ($record as $field => $value) {
            $this->data[$field] = $value;
        }

        // default values
        $this->data['userid'] ??= $USER->id;
        $this->data['ctime'] ??= time();
        $this->data['section'] ??= 0;
        $this->data['fileid'] ??= 0;
    }

    /**
     *  Create record instance from record ID
     *
     * @param int $id
     * @return record
     * @throws exception|\dml_exception
     */
    public static function from_id($id): record {
        global $DB;
        $record = $DB->get_record(self::TABLE, array('id' => $id));
        if (!$record) {
            throw new sharing_cart_exception('recordnotfound');
        }
        return new self($record);
    }

    /**
     *  Insert record
     *
     * @return int
     * @throws exception|\dml_exception
     */
    public function insert(): int {
        global $DB;
        if (!$this->weight) {
            $this->weight = self::WEIGHT_BOTTOM;
        }
        $this->id = $DB->insert_record(self::TABLE, $this->to_record());
        if (!$this->id) {
            throw new sharing_cart_exception('unexpectederror');
        }
        self::renumber($this->userid);

        return $this->id;
    }

    /**
     *  Update record
     *
     * @throws exception|\dml_exception
     */
    public function update(): void {
        global $DB;
        if (!$DB->update_record(self::TABLE, $this->to_record())) {
            throw new sharing_cart_exception('unexpectederror');
        }
        self::renumber($this->userid);
    }

    /**
     *  Delete record
     *
     * @throws exception|\dml_exception
     */
    public function delete(): void {
        global $DB;
        $DB->delete_records(self::TABLE, array('id' => $this->id));
        self::renumber($this->userid);
    }

    public function __set(string $name, $value): void
    {
        $this->offsetSet($name, $value);
    }

    #[\ReturnTypeWillChange]
    public function __get(string $name)
    {
        return $this->offsetGet($name);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->data;
    }

    public function to_record(): object
    {
        return (object)$this->data;
    }

    /**
     *  Renumber all items sequentially
     *
     * @param int|null $userid = $USER->id
     * @throws exception|\dml_exception
     * @global \moodle_database $DB
     * @global \stdClass $USER
     */
    public static function renumber(int $userid = null): void {
        global $DB, $USER;
        if ($items = $DB->get_records(self::TABLE, array('userid' => $userid ?: $USER->id))) {
            $tree = array();
            foreach ($items as $it) {
                if (!isset($tree[$it->tree])) {
                    $tree[$it->tree] = array();
                }
                $tree[$it->tree][] = $it;
            }
            foreach ($tree as $items) {
                usort($items, static function($lhs, $rhs) {
                    // keep their order if already weighted
                    if ($lhs->weight < $rhs->weight) {
                        return -1;
                    }
                    if ($lhs->weight > $rhs->weight) {
                        return +1;
                    }
                    // order by modtext otherwise
                    return strnatcasecmp($lhs->modtext, $rhs->modtext);
                });
                foreach ($items as $i => $it) {
                    $DB->set_field(self::TABLE, 'weight', 1 + $i, array('id' => $it->id));
                }
            }
        }
    }
}
