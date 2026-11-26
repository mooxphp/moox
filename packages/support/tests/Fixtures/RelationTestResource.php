<?php

declare(strict_types=1);

namespace Moox\Support\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Moox\Support\Relation\Concerns\InteractsWithConfiguredRelations;

class RelationTestResource
{
    use InteractsWithConfiguredRelations;

    public static function getModel(): string
    {
        return RelationModel::class;
    }

    public static function belongsToRelationsForTest(): array
    {
        return static::belongsToRelations();
    }

    public static function isListedBelongsToForTest(string $relation, mixed $configured): bool
    {
        return static::isListedBelongsTo($relation, $configured);
    }

    public static function isMorphRelationForTest(array $configured): bool
    {
        return static::isMorphRelation($configured);
    }

    public static function relationTitleIsTranslatedForTest(array $configured): bool
    {
        return static::relationTitleIsTranslated($configured);
    }

    public static function ownerTitleColumnsForTest(array $definition): array
    {
        return static::ownerTitleColumns($definition);
    }

    public static function morphOwnerFromKeyForTest(mixed $key): ?array
    {
        return static::morphOwnerFromKey($key);
    }

    public static function morphOwnerOptionsForTest(string $search): array
    {
        return static::morphOwnerOptions($search);
    }

    public static function morphOwnerLabelForTest(string $key): ?string
    {
        return static::morphOwnerLabel($key);
    }

    public static function ownerRecordLabelForTest(\Illuminate\Database\Eloquent\Model $owner): string
    {
        return static::ownerRecordLabel($owner);
    }

    public static function featureResourceNameForTest(): string
    {
        return static::featureResourceName();
    }
    public static function labelForOwner(Model $owner): ?string
{
    $name = $owner->getAttribute('name');

    return is_string($name) && $name !== ''
        ? $name
        : null;
}
protected static function isListedBelongsTo(string $relation, mixed $configured): bool
{
    return $relation !== ''
        && is_array($configured)
        && ($configured['kind'] ?? null) === 'belongs_to';
}
}