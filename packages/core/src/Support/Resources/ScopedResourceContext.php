<?php

namespace Moox\Core\Support\Resources;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Moox\Core\Support\Scopes\ScopeQuery;
use Moox\Core\Support\Scopes\ScopeValue;

class ScopedResourceContext
{
    public const MATCH_EXACT = 'exact';

    public const MATCH_CONTEXT = 'context';

    /**
     * @var array<class-string<Model>, bool>
     */
    protected static array $scopeSupportCache = [];

    /**
     * @param  class-string  $resource
     */
    public static function getScope(string $resource): ?string
    {
        $configuration = $resource::getConfiguration();

        if ($configuration instanceof ScopedResourceConfiguration) {
            $scope = ScopeValue::parse($configuration->getScope());

            return $scope ? (string) $scope : null;
        }

        return null;
    }

    /**
     * @param  class-string  $resource
     */
    public static function getParsedScope(string $resource): ?ScopeValue
    {
        return ScopeValue::parse(static::getScope($resource));
    }

    /**
     * @param  class-string  $resource
     */
    public static function getScopeMatchStrategy(string $resource): string
    {
        $configuration = $resource::getConfiguration();

        if (! $configuration instanceof ScopedResourceConfiguration) {
            return self::MATCH_EXACT;
        }

        $scopeMatch = $configuration->getScopeMatch();

        return in_array($scopeMatch, [self::MATCH_EXACT, self::MATCH_CONTEXT], true)
            ? $scopeMatch
            : self::MATCH_EXACT;
    }

    /**
     * @param  class-string  $resource
     */
    public static function getDefinitionValue(string $resource, string $definitionKey): mixed
    {
        $configuration = $resource::getConfiguration();

        if (! $configuration instanceof ScopedResourceConfiguration) {
            return null;
        }

        return value(ScopedResourceRegistry::getValue($resource, $configuration->getKey(), $definitionKey));
    }

    /**
     * @param  class-string  $resource
     */
    public static function applyScope(Builder $query, string $resource): Builder
    {
        $scope = static::getParsedScope($resource);

        if ($scope === null) {
            if (! static::supportsScopeColumn($query->getModel()::class)) {
                return $query;
            }

            // Global view: show only global records (empty scope).
            $scopeColumn = $query->getModel()->qualifyColumn('scope');

            return $query->where(function (Builder $builder) use ($scopeColumn): void {
                $builder->whereNull($scopeColumn)->orWhere($scopeColumn, '');
            });
        }

        if (! static::supportsScopeColumn($query->getModel()::class)) {
            return $query;
        }

        return match (static::getScopeMatchStrategy($resource)) {
            self::MATCH_CONTEXT => ScopeQuery::applyContext($query, $scope),
            default => ScopeQuery::applyExact($query, $scope),
        };
    }

    /**
     * @param  class-string  $resource
     */
    public static function applyDefaults(Model $record, string $resource): void
    {
        $scope = static::getScope($resource);

        if (blank($scope) || ! static::supportsScopeColumn($record::class)) {
            return;
        }

        $record->setAttribute('scope', $scope);
    }

    /**
     * @param  class-string<Model>  $model
     */
    protected static function supportsScopeColumn(string $model): bool
    {
        return static::$scopeSupportCache[$model] ??= Schema::hasColumn((new $model)->getTable(), 'scope');
    }
}
