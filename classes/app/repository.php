<?php

namespace block_sharing_cart\app;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

use block_sharing_cart\app\factory as base_factory;

abstract class repository {
    protected base_factory $base_factory;
    protected \moodle_database $db;

    public function __construct(base_factory $base_factory) {
        global $DB;

        $this->base_factory = $base_factory;
        $this->db = $DB;
    }

    abstract public function get_table(): string;

    public function get_all(): collection {
        return $this->base_factory->collection(
            $this->db->get_records($this->get_table())
        );
    }

    public function get_by_id(int $id): false|object {
        return $this->db->get_record($this->get_table(), ['id' => $id]);
    }

    public function insert(object $record): int {
        return $this->db->insert_record($this->get_table(), $record);
    }

    public function update(object $record): void {
        $this->db->update_record($this->get_table(), $record);
    }
}