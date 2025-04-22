<?php

namespace block_sharing_cart\app;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use block_sharing_cart\app\factory as base_factory;

abstract class repository
{
    protected base_factory $base_factory;
    protected \moodle_database $db;

    public function __construct(base_factory $base_factory)
    {
        $this->base_factory = $base_factory;
        $this->db = $this->base_factory->moodle()->db();
    }

    abstract public function get_table(): string;

    public function get_all(): collection
    {
        return $this->map_records_to_collection_of_entities(
            $this->db->get_records($this->get_table())
        );
    }

    public function get_by_id(int $id): false|entity
    {
        $record = $this->db->get_record($this->get_table(), ['id' => $id]);
        if (!$record) {
            return false;
        }
        return $this->map_record_to_entity($record);
    }

    public function insert(entity $entity): int
    {
        return $this->db->insert_record($this->get_table(), (object)$entity->to_array());
    }

    public function update(entity $entity): void
    {
        $this->db->update_record($this->get_table(), (object)$entity->to_array());
    }

    public function delete_by_id(int $id): bool
    {
        return $this->db->delete_records($this->get_table(), ['id' => $id]);
    }

    abstract public function map_record_to_entity(object $record): entity;

    public function map_records_to_collection_of_entities(array|collection $records): collection
    {
        return $this->base_factory->collection(
            array_map(fn($record) => $this->map_record_to_entity($record), $records)
        );
    }
}
