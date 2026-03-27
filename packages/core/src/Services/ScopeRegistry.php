<?php

namespace Moox\Core\Services;

class ScopeRegistry
{
    /**
     * @return array<string, class-string|null>
     */
    public function getOrigins(): array
    {
        return config('core.scopes.origins', []);
    }

    /**
     * @return array<string, class-string|null>
     */
    public function getSources(): array
    {
        return config('core.scopes.sources', []);
    }

    public function resolveOriginModel(string $origin): ?string
    {
        return $this->getOrigins()[$origin] ?? null;
    }

    public function resolveOriginKeyForModel(string $modelClass): ?string
    {
        foreach ($this->getOrigins() as $origin => $class) {
            if ($class === $modelClass) {
                return $origin;
            }
        }

        return null;
    }

    public function resolveSourceModel(string $source): ?string
    {
        return $this->getSources()[$source] ?? null;
    }

    public function resolveSourceKeyForModel(string $modelClass): ?string
    {
        foreach ($this->getSources() as $source => $class) {
            if ($class === $modelClass) {
                return $source;
            }
        }

        return null;
    }
}
