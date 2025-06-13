<?php

namespace block_sharing_cart\event;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

/**
 * @method static static create(array $data = null)
 */
abstract class base extends \core\event\base
{
    public const CRUD_CREATE = 'c';

    abstract protected function get_crud(): string;

    protected function get_table(): ?string
    {
        return 'files';
    }

    protected function init()
    {
        $table = $this->get_table();
        if (!empty($table)) {
            $this->data['objecttable'] = $table;
        }
        $this->data['edulevel'] = static::LEVEL_PARTICIPATING;
        $this->data['crud'] = $this->get_crud();
    }
}
