<?php

namespace Moox\Core\Support\Resources;

use Filament\Resources\ResourceConfiguration;
use Moox\Core\Support\Scopes\ScopeValue;

class ScopedResourceConfiguration extends ResourceConfiguration
{
    protected ?string $scope = null;

    protected ?string $scopeMatch = null;

    /**
     * @param  class-string  $resource
     */
    public static function make(string $resource, string $key): static
    {
        /** @var static $configuration */
        $configuration = parent::make($resource, $key);

        $definition = ScopedResourceRegistry::get($resource, $key);

        if (filled($definition['slug'] ?? null)) {
            $configuration->slug($definition['slug']);
        }

        $configuration->scope($definition['scope'] ?? null);
        $configuration->scopeMatch($definition['scope_match'] ?? null);

        return $configuration;
    }

    public function scope(?string $scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function scopeMatch(?string $scopeMatch): static
    {
        $this->scopeMatch = $scopeMatch;

        return $this;
    }

    public function getScopeMatch(): ?string
    {
        return $this->scopeMatch;
    }

    public function getParsedScope(): ?ScopeValue
    {
        return ScopeValue::parse($this->scope);
    }
}
