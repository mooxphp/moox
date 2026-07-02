<?php

declare(strict_types=1);

namespace Moox\Builder\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Moox\Builder\Database\Eloquent\CustomFieldsBuilder;
use Moox\Builder\FieldTypes\Value\StoredPassword;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Registry\EntityRegistry;
use Moox\Builder\Services\BuilderValuesResolver;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Support\BuilderLocaleResolver;

/**
 * Expose custom field values as if they belong to the Eloquent model.
 *
 * Entity key resolution (first match wins):
 * 1. customFieldsEntity() on the model
 * 2. getResourceName() (Moox convention, e.g. Item → item)
 * 3. Filament resource registered via HasCustomFields
 * 4. Model basename in kebab-case
 */
trait InteractsWithCustomFields
{
    /**
     * @var array<string, mixed>|null
     */
    protected ?array $customFieldsCache = null;

    protected ?string $customFieldsCacheLocale = null;

    protected bool $mergeCustomFieldsIntoArray = true;

    /**
     * @var array<string, list<string>>
     */
    protected static array $customFieldNamesCache = [];

    /**
     * @var array<string, list<string>>
     */
    protected static array $passwordCustomFieldNamesCache = [];

    /**
     * @return array<string, mixed>
     */
    public function customFields(bool $fresh = false, ?string $locale = null): array
    {
        $locale = app(BuilderLocaleResolver::class)->valuesLocaleForEntity(
            static::resolveCustomFieldsEntity(),
            $locale,
            static::class,
        );

        if (! $fresh
            && $this->customFieldsCache !== null
            && $this->customFieldsCacheLocale === $locale) {
            return $this->customFieldsCache;
        }

        /** @var CustomFieldsManager $manager */
        $manager = app(CustomFieldsManager::class);

        $entity = static::resolveCustomFieldsEntity();
        $fields = $manager->fieldsForEntity($entity);

        if ($fields->isEmpty()) {
            $this->customFieldsCache = [];
            $this->customFieldsCacheLocale = $locale;

            return [];
        }

        $values = $fresh
            ? $manager->loadValuesWithDefaults($entity, $this, $fields, $locale)
            : $manager->loadCachedValuesWithDefaults($entity, $this, $fields, $locale);

        $this->customFieldsCache = $values;
        $this->customFieldsCacheLocale = $locale;

        return $values;
    }

    public function customField(string $name, mixed $default = null): mixed
    {
        return $this->customFields()[$name] ?? $default;
    }

    public function hasCustomField(string $name): bool
    {
        return array_key_exists($name, $this->customFields());
    }

    public function hasCustomFieldDefinition(string $name): bool
    {
        return in_array($name, static::customFieldNames(), true);
    }

    public function isQueryableCustomField(string $name): bool
    {
        return $this->hasCustomFieldDefinition($name) && ! $this->modelHasDatabaseColumn($name);
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        $debug = $this->attributesToArray();
        $passwordFields = static::passwordCustomFieldNames();

        foreach ($this->customFields() as $name => $value) {
            if ($value instanceof StoredPassword || (in_array($name, $passwordFields, true) && filled($value))) {
                $debug[$name] = '••••••••';

                continue;
            }

            $debug[$name] = $value;
        }

        return $debug;
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    public function newEloquentBuilder($query): CustomFieldsBuilder
    {
        return new CustomFieldsBuilder($query);
    }

    /**
     * @return list<string>
     */
    public static function customFieldNames(): array
    {
        $entity = static::resolveCustomFieldsEntity();

        if (array_key_exists($entity, static::$customFieldNamesCache)) {
            return static::$customFieldNamesCache[$entity];
        }

        /** @var CustomFieldsManager $manager */
        $manager = app(CustomFieldsManager::class);

        return static::$customFieldNamesCache[$entity] = $manager
            ->fieldsForEntity($entity)
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setCustomFields(array $values): static
    {
        if ($values === []) {
            return $this;
        }

        /** @var CustomFieldsManager $manager */
        $manager = app(CustomFieldsManager::class);

        $entity = static::resolveCustomFieldsEntity();
        $fields = $manager->fieldsForEntity($entity)->keyBy('name');

        $payload = [];

        foreach ($values as $name => $value) {
            if (! $fields->has($name)) {
                throw new InvalidArgumentException("Unknown custom field [{$name}] for entity [{$entity}].");
            }

            $payload[$name] = $value;
        }

        $manager->saveValues(
            $entity,
            $this,
            $payload,
            $fields->only(array_keys($payload))->values(),
            app(BuilderLocaleResolver::class)->valuesLocaleForEntity($entity, null, static::class),
        );

        $this->flushCustomFieldsCache();

        return $this;
    }

    public function setCustomField(string $name, mixed $value): static
    {
        return $this->setCustomFields([$name => $value]);
    }

    public function clearCustomField(string $name): static
    {
        if (! $this->hasCustomFieldDefinition($name)) {
            throw new InvalidArgumentException("Unknown custom field [{$name}] for entity [".static::resolveCustomFieldsEntity().'].');
        }

        FieldValue::query()
            ->forRecord(static::resolveCustomFieldsEntity(), $this->getKey())
            ->forLocale(app(BuilderLocaleResolver::class)->valuesLocaleForEntity(
                static::resolveCustomFieldsEntity(),
                null,
                static::class,
            ))
            ->where('field_name', $name)
            ->delete();

        $this->flushCustomFieldsCache();

        return $this;
    }

    /**
     * @param  iterable<int, static>  $models
     */
    public static function eagerLoadCustomFields(iterable $models): void
    {
        /** @var CustomFieldsManager $manager */
        $manager = app(CustomFieldsManager::class);

        $entity = static::resolveCustomFieldsEntity();
        $fields = $manager->fieldsForEntity($entity);

        app(BuilderValuesResolver::class)->eagerLoad($models, $entity, $fields);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithCustomFields(Builder $query): Builder
    {
        return $query->afterQuery(function (EloquentCollection $models): void {
            static::eagerLoadCustomFields($models);
        });
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setCustomFieldsCache(array $values, ?string $locale = null): void
    {
        $this->customFieldsCache = $values;
        $this->customFieldsCacheLocale = app(BuilderLocaleResolver::class)->valuesLocaleForEntity(
            static::resolveCustomFieldsEntity(),
            $locale,
            static::class,
        );
    }

    public static function flushCustomFieldDefinitionCache(): void
    {
        static::$customFieldNamesCache = [];
        static::$passwordCustomFieldNamesCache = [];
    }

    public function flushCustomFieldsCache(): void
    {
        $this->customFieldsCache = null;
        $this->customFieldsCacheLocale = null;
    }

    public function toArray(): array
    {
        $array = parent::toArray();

        if ($this->mergeCustomFieldsIntoArray) {
            $customFields = [];

            foreach ($this->customFields() as $name => $value) {
                $customFields[$name] = $value instanceof StoredPassword ? null : $value;
            }

            $array = array_merge($array, $customFields);
        }

        return $array;
    }

    public function getAttribute($key): mixed
    {
        if ($this->shouldDelegateToCustomField($key)) {
            return $this->customField($key);
        }

        return parent::getAttribute($key);
    }

    public function setAttribute($key, $value): mixed
    {
        if ($this->shouldDelegateToCustomField($key)) {
            $this->setCustomField($key, $value);

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    public static function resolveCustomFieldsEntity(): string
    {
        if (method_exists(static::class, 'customFieldsEntity')) {
            $entity = static::customFieldsEntity();

            if (is_string($entity) && $entity !== '') {
                return $entity;
            }
        }

        if (method_exists(static::class, 'getResourceName')) {
            return static::getResourceName();
        }

        $entity = app(EntityRegistry::class)->entityForModel(static::class);

        if ($entity !== null) {
            return $entity;
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = static::class;

        return Str::kebab(class_basename($modelClass));
    }

    /**
     * @return list<string>
     */
    protected static function passwordCustomFieldNames(): array
    {
        $entity = static::resolveCustomFieldsEntity();

        if (array_key_exists($entity, static::$passwordCustomFieldNamesCache)) {
            return static::$passwordCustomFieldNamesCache[$entity];
        }

        /** @var CustomFieldsManager $manager */
        $manager = app(CustomFieldsManager::class);

        return static::$passwordCustomFieldNamesCache[$entity] = $manager
            ->fieldsForEntity($entity)
            ->where('type', 'password')
            ->pluck('name')
            ->values()
            ->all();
    }

    protected function shouldDelegateToCustomField(string $key): bool
    {
        return $this->isQueryableCustomField($key);
    }

    protected function hasNativeAttribute(string $key): bool
    {
        if (array_key_exists($key, $this->getAttributes())) {
            return true;
        }

        if ($this->modelHasDatabaseColumn($key)) {
            return true;
        }

        if ($this->hasGetMutator($key) || $this->hasAttributeMutator($key)) {
            return true;
        }

        if ($this->isRelation($key) || $this->relationLoaded($key)) {
            return true;
        }

        return false;
    }

    /**
     * @return list<string>
     */
    protected function databaseColumns(): array
    {
        /** @var array<string, list<string>> $cache */
        static $cache = [];

        $table = $this->getTable();

        if (! array_key_exists($table, $cache)) {
            $cache[$table] = $this->getConnection()
                ->getSchemaBuilder()
                ->getColumnListing($table);
        }

        return $cache[$table];
    }

    protected function modelHasDatabaseColumn(string $key): bool
    {
        return in_array($key, $this->databaseColumns(), true);
    }

    /**
     * Override when the storage entity key differs from getResourceName().
     */
    protected static function customFieldsEntity(): ?string
    {
        return null;
    }
}
