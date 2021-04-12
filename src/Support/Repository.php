<?php

namespace Honeybadger\Support;

class Repository implements \ArrayAccess
{
    /**
     * @var array
     */
    protected $items = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * @param  string  $key
     * @param  mixed  $value
     * @return array
     */
    public function set(string $key, $value): array
    {
        $this->items[$key] = $value;

        return $this->items;
    }

    public function get(string $key): ?array
    {
        return $this->items[$key] ?? null;
    }

    /**
     * @param  string   $key
     * @param  mixed  $value
     * @return void
     */
    public function __set(string $key, $value): void
    {
        $this->set($key, $value);
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @param  string|int $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * @param  int|string  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    /**
     * @param  int|string  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        $this->items[$offset] = $value;
    }

    /**
     * @param  int|string  $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }

    /**
     * Return all values except those specified.
     *
     * @param string|array $keys
     * @return array
     */
    public function except($keys): array
    {
        $items = $this->items;

        if (is_array($keys)) {
            foreach ($keys as $key) {
                unset($items[$key]);
            }

            return $items;
        }

        unset($items[$keys]);

        return $items;
    }
}
