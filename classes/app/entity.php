<?php

namespace block_sharing_cart\app;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

abstract class entity extends \stdClass implements \ArrayAccess, \JsonSerializable
{
    protected array $record;

    public function __construct(array $record = [])
    {
        $this->record = $record;
    }

    public function get_id(): int
    {
        return $this->record['id'] ?? 0;
    }

    public function set_id(int $value): self
    {
        $this->record['id'] = $value;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return $this->to_array();
    }

    abstract public function to_array(): array;

    public function __get($name): mixed
    {
        return $this->record[$name] ?? null;
    }

    public function __set($name, $value): void
    {
        $this->record[$name] = $value;
    }

    public function __isset($name): bool
    {
        return isset($this->record[$name]);
    }

    public function __unset($name): void
    {
        if (isset($this->record[$name])) {
            $this->record[$name] = null;
        }
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->record[] = $value;
        } else {
            $this->record[$offset] = $value;
        }
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->record[$offset]);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->record[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->record[$offset] ?? null;
    }
}