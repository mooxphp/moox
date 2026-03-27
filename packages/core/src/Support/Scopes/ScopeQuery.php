<?php

namespace Moox\Core\Support\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ScopeQuery
{
    protected static ?bool $hasScopesTable = null;

    public static function applyExact(Builder $query, string|ScopeValue $scope, ?string $column = null): Builder
    {
        $parsedScope = ScopeValue::parse($scope);

        if ($parsedScope === null) {
            return $query;
        }

        $qualifiedColumn = static::qualifyColumn($query, $column);
        $scopeString = (string) $parsedScope;

        if (static::isGlobalScope($parsedScope)) {
            $query->where(function (Builder $builder) use ($qualifiedColumn, $scopeString): void {
                $builder
                    ->where($qualifiedColumn, $scopeString)
                    ->orWhereNull($qualifiedColumn);
            });
        } else {
            $query->where($qualifiedColumn, $scopeString);
        }

        return static::applyActiveScopeGuardExact(
            $query,
            $qualifiedColumn,
            $scopeString,
        );
    }

    public static function applyContext(Builder $query, string|ScopeValue $scope, ?string $column = null): Builder
    {
        $parsedScope = ScopeValue::parse($scope);

        if ($parsedScope === null) {
            return $query;
        }

        $qualifiedColumn = static::qualifyColumn($query, $column);
        $contextPattern = $parsedScope->contextLikePattern();

        if (static::isGlobalScope($parsedScope)) {
            $query->where(function (Builder $builder) use ($qualifiedColumn, $contextPattern): void {
                $builder
                    ->where($qualifiedColumn, 'like', $contextPattern)
                    ->orWhereNull($qualifiedColumn);
            });
        } else {
            $query->where($qualifiedColumn, 'like', $contextPattern);
        }

        return static::applyActiveScopeGuardContext(
            $query,
            $qualifiedColumn,
            $contextPattern,
        );
    }

    protected static function applyActiveScopeGuardExact(Builder $query, string $qualifiedColumn, string $scope): Builder
    {
        if (! static::hasScopesTable()) {
            return $query;
        }

        return $query->whereExists(function ($subQuery) use ($qualifiedColumn, $scope): void {
            $subQuery
                ->select(DB::raw('1'))
                ->from('scopes')
                ->whereColumn('scopes.scope', $qualifiedColumn)
                ->where('scopes.scope', $scope)
                ->where('scopes.is_active', true);
        });
    }

    protected static function applyActiveScopeGuardContext(Builder $query, string $qualifiedColumn, string $contextPattern): Builder
    {
        if (! static::hasScopesTable()) {
            return $query;
        }

        // Context matching should only require that the context itself is active.
        // If a specific boundary bucket (e.g. :public) is disabled, records from that
        // boundary can still be shown inside the active context list.
        return $query->whereExists(function ($subQuery) use ($contextPattern): void {
            $subQuery
                ->select(DB::raw('1'))
                ->from('scopes')
                ->where('scopes.scope', 'like', $contextPattern)
                ->where('scopes.is_active', true);
        });
    }

    protected static function hasScopesTable(): bool
    {
        return static::$hasScopesTable ??= Schema::hasTable('scopes');
    }

    protected static function isGlobalScope(ScopeValue $scope): bool
    {
        return $scope->source() === 'global' && $scope->context() === 'global';
    }

    protected static function qualifyColumn(Builder $query, ?string $column = null): string
    {
        return $query->getModel()->qualifyColumn($column ?? 'scope');
    }
}
