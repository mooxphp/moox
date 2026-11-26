<?php

declare(strict_types=1);

namespace Moox\Support\Relation\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait InteractsWithConfiguredRelations
{
    /**
     * @return array<string, array<string, mixed>>
     */
    protected static function belongsToRelations(): array
    {
        $relations = config(static::featureResourceName().'.relations', []);

        if (! is_array($relations)) {
            return [];
        }

        $belongsTo = [];

        foreach ($relations as $relation => $configured) {
            if (! static::isListedBelongsTo($relation, $configured)) {
                continue;
            }

            $belongsTo[$relation] = $configured;
        }

        return $belongsTo;
    }

    protected static function isListedBelongsTo(mixed $relation, mixed $configured): bool
    {
        return is_string($relation)
            && is_array($configured)
            && ($configured['kind'] ?? null) === 'belongs_to';
    }

    /**
     * @param  array<string, mixed>  $configured
     */
    protected static function isMorphRelation(array $configured): bool
    {
        return is_string($configured['morph_name'] ?? null) && $configured['morph_name'] !== '';
    }

    /**
     * @param  array<string, mixed>  $configured
     */
    protected static function relationTitleIsTranslated(array $configured): bool
    {
        $model = $configured['model'] ?? null;

        return is_string($model) && is_a($model, 'Astrotomic\\Translatable\\Contracts\\Translatable', true);
    }

    /**
     * @return array<string, string>
     */
    protected static function morphOwnerOptions(string $search): array
    {
        $search = trim($search);

        if ($search === '') {
            return [];
        }

        $like = '%'.addcslashes($search, '%_\\').'%';
        $options = [];

        foreach (config(static::featureResourceName().'.owner_types', []) as $class => $definition) {
            if (! is_string($class) || ! is_array($definition) || ! is_a($class, Model::class, true)) {
                continue;
            }

            $columns = static::ownerTitleColumns($definition);

            if ($columns === []) {
                continue;
            }

            foreach ($class::query()->where(function (Builder $query) use ($columns, $like): void {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', $like);
                }
            })->limit(15)->get() as $owner) {
                $options[$class.'|'.$owner->getKey()] = static::ownerRecordLabel($owner);
            }
        }

        return $options;
    }

    protected static function morphOwnerLabel(?string $key): ?string
    {
        $owner = static::morphOwnerFromKey($key);

        if ($owner === null) {
            return null;
        }

        $record = $owner['type']::query()->find($owner['id']);

        return $record instanceof Model ? static::ownerRecordLabel($record) : null;
    }

    /**
     * @return array{type: class-string<Model>, id: string}|null
     */
    protected static function morphOwnerFromKey(mixed $key): ?array
    {
        if (! is_string($key) || ! str_contains($key, '|')) {
            return null;
        }

        [$type, $id] = explode('|', $key, 2);
        $ownerTypes = config(static::featureResourceName().'.owner_types', []);

        if ($id === '' || ! is_array($ownerTypes) || ! isset($ownerTypes[$type]) || ! is_a($type, Model::class, true)) {
            return null;
        }

        return [
            'type' => $type,
            'id' => $id,
        ];
    }

    protected static function ownerRecordLabel(Model $owner): string
    {
        if (method_exists(static::class, 'labelForOwner')) {
            $label = static::labelForOwner($owner);

            if (is_string($label) && $label !== '') {
                return $label;
            }
        }

        return (string) $owner->getKey();
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return list<string>
     */
    protected static function ownerTitleColumns(array $definition): array
    {
        $columns = $definition['title_attributes'] ?? null;

        if (! is_array($columns)) {
            $titleAttribute = $definition['title_attribute'] ?? null;
            $columns = is_string($titleAttribute) && $titleAttribute !== '' ? [$titleAttribute] : [];
        }

        return array_values(array_filter(
            $columns,
            fn (mixed $column): bool => is_string($column) && $column !== '',
        ));
    }

    protected static function featureResourceName(): string
    {
        $model = static::getModel();

        if (! method_exists($model, 'getResourceName')) {
            throw new \RuntimeException(sprintf('Model %s must implement getResourceName().', $model));
        }

        return $model::getResourceName();
    }
}
