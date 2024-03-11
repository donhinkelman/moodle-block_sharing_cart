<?php

namespace block_sharing_cart\app;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

class collection implements \Iterator, \Countable, \ArrayAccess, \JsonSerializable {
    protected array $items = [];

    public function __construct(array $items = []) {
        $this->set($items);
    }

    public function set(array $items): void {
        $this->items = $items;
    }

    public function sort_asc(callable $selector): self {
        $this->sort($selector);
        return $this;
    }

    public function sort_desc(callable $selector): self {
        $this->sort($selector, false);
        return $this;
    }

    /**
     * Example:
     * $test = new TestCollection();
     * $test->sort(function(entity $entity) {
     *    return $entity->id;
     * });
     *
     * @param callable $selector
     * @param bool $direction_asc
     * @return void
     */
    private function sort(callable $selector, bool $direction_asc = true): void {
        usort($this->items, static function ($a, $b) use ($selector, $direction_asc) {
            if ($direction_asc) {
                return strnatcasecmp($selector($a), $selector($b)
                );
            }
            return strnatcasecmp($selector($b), $selector($a)
            );
        });
    }

    /**
     * Example:
     * $test = new TestCollection();
     * $names = $collection->pluck(function($instance) {
     *    return $instance['name'] ?? null;
     * });
     *
     * @param callable $instance
     * @return self
     */
    public function pluck(callable $instance): self {
        $values = [];
        foreach ($this->items as $item) {
            $values[] = $instance($item);
        }
        return new static($values);
    }

    public function count(): int {
        return count($this->items);
    }

    /**
     * The model needs to implement \JsonSerializable and
     * use the method "jsonSerialize" for this to work.
     *
     * @param bool $indexed
     * @return array
     * @throws JsonException
     */
    public function to_array(bool $indexed = false): array {
        $encoded = json_encode($this->items, JSON_THROW_ON_ERROR);
        $items = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);

        if ($indexed) {
            return array_values($items);
        }

        return $items;
    }

    public function implode(string $separator): string {
        return implode($separator, $this->items);
    }

    public function explode(string $text, string $separator = ','): self {
        foreach (explode($separator, $text) as $item) {
            $this->append(trim($item));
        }
        return $this;
    }

    public function by_key(string $key) {
        if ($this->empty()) {
            throw new \Exception('Key not found in collection');
        }

        return $this->items[$key] ?? null;
    }

    public function first(): mixed {
        if ($this->empty()) {
            throw new \Exception('No first item in collection');
        }

        return reset($this->items);
    }

    public function last($optional = false): mixed {
        if ($this->empty()) {
            throw new \Exception('No last item in collection');
        }

        return end($this->items);
    }

    public function slice(int $offset, int $length): self {
        $this->items = array_values(array_slice($this->items, $offset, $length));
        return $this;
    }

    public function empty(): bool {
        return empty($this->items);
    }

    public function not_empty(): bool {
        return !$this->empty();
    }

    public function filter(callable $item): self {
        return new static(array_filter($this->items, $item));
    }

    public function map(callable $items): self {
        return new static(array_map($items, $this->items));
    }

    /**
     * Example:
     * $test = new TestCollection();
     * $list = $collection->to_list(
     *    function($item){
     *       return $item->id;
     *    },
     *    function($item){
     *       return $item->name;
     *    }
     * );
     *
     * @param callable $key
     * @param callable $value
     * @param bool $append_items
     * @return self
     */
    public function to_list(callable $key, callable $value, bool $append_items = false): self {
        $items = [];
        foreach ($this->items as $instance) {
            if (!$append_items) {
                $items[$key($instance)] = $value($instance);
            } else {
                $items[$key($instance)][] = $value($instance);
            }
        }
        return new static($items);
    }

    public function splice(int $offset, int $length, mixed $replacement): self {
        $items = array_splice($this->items, $offset, $length, $replacement);
        return new static($items);
    }

    public function shuffle(int $times = 1): self {
        for ($i = 0; $i < $times; $i++) {
            shuffle($this->items);
        }
        return $this;
    }

    public function find(mixed $value, string $field = ''): self {
        $found = [];
        foreach ($this as $item) {
            if (is_object($item) && isset($item->$field)) {
                if ($item->$field == $value) {
                    $found[] = $item;
                }
            } elseif (is_array($item) && isset($item[$field])) {
                if ($item[$field] == $value) {
                    $found[] = $item;
                }
            } elseif (empty($field) && $item == $value) {
                $found[] = $item;
            }
        }

        return new static($found);
    }

    public function add(mixed $item): self {
        return $this->append($item);
    }

    public function append(mixed $item): self {
        $this->items[] = $item;
        return $this;
    }

    public function prepend(mixed $item): collection {
        array_unshift($this->items, $item);
        return new static($this->items);
    }

    public function merge(self $collection): self {
        foreach ($collection as $item) {
            $this->append($item);
        }
        return $this;
    }

    public function contains(callable $field, mixed $value): bool {
        foreach ($this->items as $item) {
            if ($field($item) === $value) {
                return true;
            }
        }
        return false;
    }

    public function column(string $key): array {
        return array_column($this->items, $key);
    }

    public function combine(array $keys): array {
        return array_combine($keys, $this->items);
    }


    public function current(): mixed {
        return current($this->items);
    }

    public function next(): void {
        next($this->items);
    }

    public function key(): string|int|null {
        return key($this->items);
    }

    public function valid(): bool {
        return array_key_exists(key($this->items), $this->items);
    }

    public function rewind(): void {
        reset($this->items);
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetExists(mixed $offset): bool {
        return isset($this->items[$offset]);
    }

    public function offsetUnset(mixed $offset): void {
        unset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->items[$offset] ?? null;
    }

    public function jsonSerialize(): array {
        return $this->to_array(true);
    }
}
