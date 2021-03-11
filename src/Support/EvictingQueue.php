<?php

namespace Honeybadger\Support;

/**
 * A container to hold an ordered list of items, automatically evicting older items to maintain a specified maximum capacity.
 */
class EvictingQueue
{
    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var int
     */
    protected $capacity;

    public function __construct(int $capacity, array $items = [])
    {
        $this->capacity = $capacity;
        $this->items = $items;
    }

    /**
     * @param mixed $item
     *
     * @return self
     */
    public function add($item)
    {
        $this->items[] = $item;

        if (count($this->items) > $this->capacity) {
            array_shift($this->items);
        }

        return $this;
    }

    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Clear the queue.
     */
    public function clear(): self
    {
        $this->items = [];

        return $this;
    }
}
