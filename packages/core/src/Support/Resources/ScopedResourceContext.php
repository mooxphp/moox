<?php

namespace Moox\Core\Support\Resources;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Moox\Core\Models\Concerns\HasScopedModel;
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
            // Global view (no scoped resource context): show unassigned and scoped records.
            return $query;
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
     * Only models that opt into multi-tenancy scoping via HasScopedModel.
     * The scopes catalog table also has a `scope` column (identity key) — that
     * must not be treated as an assignment column.
     *
     * @param  class-string<Model>  $model
     */
    protected static function supportsScopeColumn(string $model): bool
    {
        return static::$scopeSupportCache[$model] ??= in_array(HasScopedModel::class, class_uses_recursive($model), true)
            && Schema::hasColumn((new $model)->getTable(), 'scope');
    }
}
