<?php

namespace Honeybadger;

use Honeybadger\Support\EvictingQueue;

class Breadcrumbs extends EvictingQueue
{
    /**
     * @param $item
     *
     * @return self
     */
    public function add($item)
    {
        $item = [
            'message' => (string) $item['message'],
            'category' => (string) ($item['category'] ?? 'custom'),
            'metadata' => $this->sanitize($item['metadata'] ?? []),
            'timestamp' => $item['timestamp'] ?? date('c'),
        ];

        return parent::add($item);
    }

    /**
     * Limit an array to a simple [key => value] (one level) containing only primitives.
     */
    private function sanitize(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value) || is_resource($value)) {
                $value = "[DEPTH]";
            }

            if (is_string($value)) {
                // Limit strings to 64kB.
                $value = substr($value, 0, 64000);
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
