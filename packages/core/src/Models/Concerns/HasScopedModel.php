<?php

namespace Moox\Core\Models\Concerns;

use Moox\Core\Casts\ScopeCast;
use Moox\Core\Services\ScopeRegistry;
use Moox\Core\Support\Scopes\ScopeValue;

trait HasScopedModel
{
    public function initializeHasScopedModel(): void
    {
        $this->mergeFillable(['scope']);
        $this->mergeCasts([
            'scope' => ScopeCast::class,
        ]);
    }

    public static function bootHasScopedModel(): void
    {
        static::creating(function ($model): void {
            if (blank($model->getAttribute('scope'))) {
                $defaultScope = $model->getDefaultScope();

                if (filled($defaultScope)) {
                    $model->setAttribute('scope', $defaultScope);
                }
            }
        });
    }

    public function getDefaultScope(): ?string
    {
        return $this->resolveGlobalScopeString();
    }

    public function hasScope(): bool
    {
        return $this->getCurrentScopeString() !== null;
    }

    public function getCurrentScopeString(): ?string
    {
        return ScopeValue::toStringOrNull($this->getAttribute('scope'));
    }

    public function getCurrentScopeObject(): ?ScopeValue
    {
        return ScopeValue::parse($this->getAttribute('scope'));
    }

    public function deriveChildScope(
        string $childOrigin,
        ?string $context = null,
        ?string $boundary = null,
        ?string $source = null,
    ): ?string {
        return ScopeValue::deriveChildString(
            $this->getCurrentScopeString(),
            $childOrigin,
            context: $context,
            boundary: $boundary,
            source: $source,
        );
    }

    public function getScopeString(): ?string
    {
        return $this->getCurrentScopeString();
    }

    public function getScopeObject(): ?ScopeValue
    {
        return $this->getCurrentScopeObject();
    }

    public function deriveScopeForOrigin(
        string $origin,
        ?string $context = null,
        ?string $boundary = null,
        ?string $source = null,
    ): ?string {
        return $this->deriveChildScope($origin, $context, $boundary, $source);
    }

    public function resolveScopeOrigin(): ?string
    {
        return app(ScopeRegistry::class)->resolveOriginKeyForModel(static::class);
    }

    public function resolveGlobalScopeString(): ?string
    {
        $origin = $this->resolveScopeOrigin();

        if (blank($origin)) {
            return null;
        }

        return ScopeValue::forKeyString(
            $origin,
            boundary: ScopeValue::MODE_PRIVATE,
            source: 'global',
            context: 'global',
        );
    }

    public function assignScope(string|ScopeValue|null $scope): void
    {
        $scopeString = ScopeValue::toStringOrNull($scope) ?? $this->resolveGlobalScopeString();

        $this->setAttribute('scope', $scopeString);
    }

    public function assignGlobalScope(): void
    {
        $this->assignScope(null);
    }
}
