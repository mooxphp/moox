<?php

declare(strict_types=1);

namespace Moox\Core\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Moox\Core\Relations\Enums\RelationKind;
use Moox\Core\Relations\Enums\RelationPerspective;
use Moox\Core\Relations\Exceptions\UnsupportedRelationException;
use Moox\Core\Services\RelationService;

final class RelationFactory
{
    public static function make(Model $owner, string $key, RelationService $service): Relation
    {
        return self::forOwnerResource($owner, $service, function (RelationService $service) use ($owner, $key): Relation {
            $relation = $service->get($key);

            return match ($relation->kind) {
                RelationKind::MorphPivot => self::morphPivot($owner, $key, $service),
                RelationKind::PivotHasMany => self::pivotHasMany($owner, $key, $service),
                RelationKind::BelongsToMany => self::belongsToMany($owner, $key, $service),
                RelationKind::BelongsTo => self::belongsTo($owner, $key, $service),
                RelationKind::HasMany => self::hasMany($owner, $key, $service),
                default => throw UnsupportedRelationException::forCombination(
                    (string) $service->getCurrentResource(),
                    $key,
                    $relation->kind,
                    $relation->perspective,
                    $relation->presentation,
                ),
            };
        });
    }

    public static function primary(Model $owner, string $key, RelationService $service): Relation
    {
        return self::forOwnerResource($owner, $service, function (RelationService $service) use ($owner, $key): Relation {
            $relation = $service->get($key);

            if ($relation->kind !== RelationKind::MorphPivot) {
                return self::make($owner, $key, $service);
            }

            $query = self::morphPivot($owner, $key, $service);

            if ($service->primaryOn($key) === 'related' && $relation->relatedModel !== null) {
                $table = (new $relation->relatedModel)->getTable();

                return $query->where(
                    "{$table}.{$service->primaryRelatedColumn($key)}",
                    $service->primaryValue($key),
                );
            }

            return $query->wherePivot(
                $service->primaryColumn($key),
                $service->primaryValue($key),
            );
        });
    }

    private static function morphPivot(Model $owner, string $key, RelationService $service): MorphToMany
    {
        $relation = $service->get($key);
        $relatedModel = $relation->relatedModel;

        if ($relatedModel === null) {
            return $owner->morphToMany(Model::class, 'missing', 'missing')->whereRaw('1 = 0');
        }

        $pivotAttributes = $service->pivotAttributes($key);
        $pivotModel = $service->pivotModel($key);

        $builder = $relation->perspective === RelationPerspective::Related
            ? $owner->morphedByMany(
                $relatedModel,
                $service->morphType($key),
                $service->pivotTable($key),
                $service->relatedKey($key),
                $service->foreignKey($key),
            )
            : $owner->morphToMany(
                $relatedModel,
                $service->morphType($key),
                $service->pivotTable($key),
                $service->foreignKey($key),
                $service->relatedKey($key),
            );

        if ($pivotAttributes !== []) {
            $builder->withPivot($pivotAttributes);
        }

        if ($pivotModel !== null) {
            $builder->using($pivotModel);
        }

        return $builder->withTimestamps();
    }

    private static function pivotHasMany(Model $owner, string $key, RelationService $service): HasMany
    {
        $relation = $service->get($key);
        $pivotModel = $service->pivotModel($key);

        if ($pivotModel === null) {
            return $owner->hasMany(Model::class)->whereRaw('1 = 0');
        }

        $foreignKey = $relation->config['pivot_foreign_key'] ?? null;

        if (! is_string($foreignKey) || $foreignKey === '') {
            $foreignKey = Str::snake(class_basename($owner)).'_id';
        }

        return $owner->hasMany($pivotModel, $foreignKey);
    }

    private static function belongsToMany(Model $owner, string $key, RelationService $service): BelongsToMany
    {
        $relation = $service->get($key);
        $relatedModel = $relation->relatedModel;

        if ($relatedModel === null) {
            return $owner->belongsToMany(Model::class)->whereRaw('1 = 0');
        }

        $pivotAttributes = $service->pivotAttributes($key);
        $pivotModel = $service->pivotModel($key);

        $builder = $owner->belongsToMany(
            $relatedModel,
            (string) $relation->pivotTable,
            self::nullableString($relation->foreignKey),
            self::nullableString($relation->relatedKey),
            self::nullableString($relation->config['parent_key'] ?? null),
            self::nullableString($relation->config['related_owner_key'] ?? null),
        );

        if ($pivotAttributes !== []) {
            $builder->withPivot($pivotAttributes);
        }

        if ($pivotModel !== null) {
            $builder->using($pivotModel);
        }

        return $builder->withTimestamps();
    }

    private static function belongsTo(Model $owner, string $key, RelationService $service): BelongsTo
    {
        $relation = $service->get($key);
        $relatedModel = $relation->relatedModel ?? $owner::class;

        return $owner->belongsTo(
            $relatedModel,
            self::resolveForeignKey($relation, $key),
        );
    }

    private static function hasMany(Model $owner, string $key, RelationService $service): HasMany
    {
        $relation = $service->get($key);
        $relatedModel = $relation->relatedModel ?? $owner::class;

        return $owner->hasMany(
            $relatedModel,
            self::resolveForeignKey($relation, $key),
        );
    }

    private static function resolveForeignKey(ResolvedRelation $relation, string $key): string
    {
        $configured = self::nullableString($relation->foreignKey)
            ?? self::nullableString($relation->config['foreign_key'] ?? null);

        if ($configured !== null) {
            return $configured;
        }

        if ($relation->inverseRelationship === 'parent') {
            return 'parent_id';
        }

        $relationship = $relation->relationship;

        if (in_array($relationship, ['parent', 'children'], true) || in_array($key, ['parent', 'children'], true)) {
            return 'parent_id';
        }

        return Str::snake($key).'_id';
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @template TReturn
     *
     * @param  callable(RelationService): TReturn  $callback
     * @return TReturn
     */
    private static function forOwnerResource(Model $owner, RelationService $service, callable $callback): mixed
    {
        if (! method_exists($owner, 'getResourceName')) {
            return $callback($service);
        }

        return $service->withResource($owner::getResourceName(), $callback);
    }
}
