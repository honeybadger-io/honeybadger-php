<?php

namespace Honeybadger\Support;

/**
 * A container to hold an ordered list of items, automatically evicting older items to maintain a specified maximum size.
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
    protected $size;

    public function __construct(int $size, array $items = [])
    {
        $this->size = $size;
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

        if (count($this->items) > $this->size) {
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
