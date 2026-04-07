<?php

namespace Moox\Core\Services;

class ScopeRegistry
{
    /**
     * Build the scope registry by merging all package contributions
     * (declared in each package config under `scope_registry`) and then
     * applying any application overrides from `core.scopes`.
     *
     * @return array{origins: array<string, class-string|null>, sources: array<string, class-string|null>}
     */
    protected function registry(): array
    {
        /** @var array{origins?: array<string, class-string|null>, sources?: array<string, class-string|null>} $merged */
        $merged = [
            'origins' => [],
            'sources' => [],
        ];

        $packages = config('core.packages', []);

        foreach (array_keys($packages) as $packageKey) {
            $contribution = config($packageKey.'.scope_registry', []);

            if (! is_array($contribution)) {
                continue;
            }

            /** @var array<string, class-string|null> $origins */
            $origins = is_array($contribution['origins'] ?? null) ? $contribution['origins'] : [];
            /** @var array<string, class-string|null> $sources */
            $sources = is_array($contribution['sources'] ?? null) ? $contribution['sources'] : [];

            $merged['origins'] = array_replace($merged['origins'], $origins);
            $merged['sources'] = array_replace($merged['sources'], $sources);
        }

        $overrides = config('core.scopes', []);
        if (is_array($overrides)) {
            /** @var array<string, class-string|null> $overrideOrigins */
            $overrideOrigins = is_array($overrides['origins'] ?? null) ? $overrides['origins'] : [];
            /** @var array<string, class-string|null> $overrideSources */
            $overrideSources = is_array($overrides['sources'] ?? null) ? $overrides['sources'] : [];

            $merged['origins'] = array_replace($merged['origins'], $overrideOrigins);
            $merged['sources'] = array_replace($merged['sources'], $overrideSources);
        }

        return $merged;
    }

    /**
     * @return array<string, class-string|null>
     */
    public function getOrigins(): array
    {
        return $this->registry()['origins'];
    }

    /**
     * @return array<string, class-string|null>
     */
    public function getSources(): array
    {
        return $this->registry()['sources'];
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
