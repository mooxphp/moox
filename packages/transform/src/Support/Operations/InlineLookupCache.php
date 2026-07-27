<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Operations;

/**
 * Request/run-scoped memo for expensive inline lookup operations.
 * Flushed at the start of each TransformRunner run.
 */
final class InlineLookupCache
{
    /** @var array<string, mixed> */
    private array $entries = [];

    public function remember(string $key, callable $callback): mixed
    {
        if (array_key_exists($key, $this->entries)) {
            return $this->entries[$key];
        }

        return $this->entries[$key] = $callback();
    }

    public function flush(): void
    {
        $this->entries = [];
    }

    public function count(): int
    {
        return count($this->entries);
    }
}
