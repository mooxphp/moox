<?php

declare(strict_types=1);

namespace Moox\Cache\Support;

use Moox\Cache\Contracts\CacheTarget;
use RuntimeException;

class CacheTargetRegistry
{
    /** @var array<string, CacheTarget> */
    protected array $targets = [];

    public function register(CacheTarget $target): void
    {
        $this->targets[$target->key()] = $target;
    }

    /**
     * @param  list<CacheTarget>  $targets
     */
    public function registerMany(array $targets): void
    {
        foreach ($targets as $target) {
            $this->register($target);
        }
    }

    /**
     * @return list<CacheTarget>
     */
    public function all(): array
    {
        return array_values($this->targets);
    }

    public function get(string $key): ?CacheTarget
    {
        return $this->targets[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->targets[$key]);
    }

    /**
     * @return array<string, list<CacheTarget>>
     */
    public function groupedByCategory(): array
    {
        $grouped = [];

        foreach ($this->all() as $target) {
            $grouped[$target->category()][] = $target;
        }

        ksort($grouped);

        return $grouped;
    }

    public function require(string $key): CacheTarget
    {
        $target = $this->get($key);

        if ($target === null) {
            throw new RuntimeException("Cache target [{$key}] is not registered.");
        }

        return $target;
    }
}
