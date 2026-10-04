<?php

declare(strict_types=1);

namespace Moox\Definition\Laravel;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Moox\Definition\Definitions\Relation;
use Moox\Definition\Definitions\RelationType;
use Moox\Definition\Projection\IncompleteProjection;
use Moox\Definition\Projection\UnsupportedProjection;

abstract class Model extends EloquentModel
{
    use ConfiguresDefinitionModel;

    /**
     * @return array<string, class-string<EloquentModel>>
     */
    protected static function modelBindings(): array
    {
        return [];
    }

    public function isRelation($key): bool
    {
        return parent::isRelation($key) || $this->resolvedDefinition()->relation($key) !== null;
    }

    /**
     * @param  array<mixed>  $parameters
     */
    public function __call($method, $parameters): mixed
    {
        $resolved = $this->resolvedDefinition()->relation($method);

        if ($resolved !== null) {
            return $this->projectRelation($resolved->relation());
        }

        return parent::__call($method, $parameters);
    }

    private function projectRelation(Relation $relation): BelongsTo|HasOne|HasMany|BelongsToMany
    {
        $bindings = static::modelBindings();
        $related = $bindings[$relation->name()] ?? null;

        if ($related === null) {
            throw new IncompleteProjection("Laravel model class for relation [{$relation->name()}] is not configured.");
        }

        $keys = $relation->keys();

        return match ($relation->type()) {
            RelationType::BelongsTo => $this->belongsTo(
                $related,
                $this->requiredKey($relation, $keys->foreignKey(), 'foreignKey'),
                $this->requiredKey($relation, $keys->ownerKey(), 'ownerKey'),
                $relation->name(),
            ),
            RelationType::HasOne => $this->hasOne(
                $related,
                $this->requiredKey($relation, $keys->foreignKey(), 'foreignKey'),
                $this->requiredKey($relation, $keys->localKey(), 'localKey'),
            ),
            RelationType::HasMany => $this->hasMany(
                $related,
                $this->requiredKey($relation, $keys->foreignKey(), 'foreignKey'),
                $this->requiredKey($relation, $keys->localKey(), 'localKey'),
            ),
            RelationType::BelongsToMany => $this->belongsToMany(
                $related,
                $this->requiredKey($relation, $relation->pivot()?->table(), 'pivot.table'),
                $this->requiredKey($relation, $relation->pivot()?->foreignPivotKey(), 'pivot.foreignPivotKey'),
                $this->requiredKey($relation, $relation->pivot()?->relatedPivotKey(), 'pivot.relatedPivotKey'),
                $this->requiredKey($relation, $keys->localKey(), 'localKey'),
                $this->requiredKey($relation, $keys->relatedKey(), 'relatedKey'),
                $relation->name(),
            ),
            default => throw UnsupportedProjection::relation('Laravel model', $relation->name(), $relation->type()->value),
        };
    }

    private function requiredKey(Relation $relation, ?string $value, string $key): string
    {
        if ($value === null || $value === '') {
            throw new IncompleteProjection("Relation [{$relation->name()}] is missing [{$key}].");
        }

        return $value;
    }
}
