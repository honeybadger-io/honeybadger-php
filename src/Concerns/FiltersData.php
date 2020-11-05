<?php

namespace Honeybadger\Concerns;

use Honeybadger\Support\Arr;

trait FiltersData
{
    /**
     * @var array
     */
    protected $keysToFilter = [];

    /**
     * @param  array  $keysToFilter
     * @return mixed
     */
    public function filterKeys(array $keysToFilter): self
    {
        $this->keysToFilter = array_merge($this->keysToFilter, $keysToFilter);

        return $this;
    }

    /**
     * @param  array  $values
     * @return array
     */
    private function filter(array $values): array
    {
        return Arr::mapWithKeys($values, function ($value, $key) {
            if (is_array($value) && !$this->arrayHasKeys($value)) {
                return $value;
            }

            if (is_array($value)) {
                return $this->filter($value);
            }

            if (in_array($key, $this->keysToFilter)) {
                return '[FILTERED]';
            }

            return $value;
        });
    }

    private function arrayHasKeys(array $data): bool
    {
        return array_keys($data) !== range(0, count($data) - 1);
    }
}
