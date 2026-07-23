<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Moox\Builder\Registry\EntityRegistry;
use Moox\Core\Services\TaxonomyService;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

final class LocationConstraintOptions
{
    /**
     * @var array<string, array<string, string>>
     */
    protected array $taxonomyKeysCache = [];

    /**
     * @var array<string, array<string, string>>
     */
    protected array $recordTypeOptionsCache = [];

    /**
     * @var array<string, array<string, string>>
     */
    protected array $recordStatusOptionsCache = [];

    /**
     * @var array<string, array<string, string>>
     */
    protected array $termOptionsCache = [];

    /**
     * @var array<string, array<string, string>>
     */
    protected array $availableParamOptionsCache = [];

    protected ?bool $supportsUserRolesCache = null;

    protected bool $userRoleUnavailableReasonResolved = false;

    protected ?string $userRoleUnavailableReasonCache = null;

    /**
     * @var array<string, string>|null
     */
    protected ?array $roleOptionsCache = null;

    /**
     * @var array<class-string<Model>, array<string, string>>
     */
    protected array $recordTypeOptionsByModelCache = [];

    /**
     * @var array<class-string<Model>, array<string, string>>
     */
    protected array $recordStatusOptionsByModelCache = [];

    public function __construct(
        protected EntityRegistry $entityRegistry,
        protected TaxonomyService $taxonomyService,
        protected BuilderLocaleResolver $localeResolver,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function taxonomyKeysForEntities(mixed $entities): array
    {
        $cacheKey = $this->entitiesCacheKey($entities);

        if (array_key_exists($cacheKey, $this->taxonomyKeysCache)) {
            return $this->taxonomyKeysCache[$cacheKey];
        }

        $options = [];

        foreach ($this->normalizeEntities($entities) as $entity) {
            $configKey = $this->configKeyForEntity($entity);

            if ($configKey === null) {
                continue;
            }

            $this->taxonomyService->setCurrentResource($configKey);

            foreach ($this->taxonomyService->getTaxonomies() as $key => $definition) {
                $options[(string) $key] = $this->taxonomyLabel((string) $key, $definition);
            }
        }

        asort($options);

        return $this->taxonomyKeysCache[$cacheKey] = $options;
    }

    /**
     * @return array<string, string>
     */
    public function termOptionsForTaxonomy(string $taxonomy, mixed $entities): array
    {
        if ($taxonomy === '') {
            return [];
        }

        $cacheKey = $this->entitiesCacheKey($entities).'|'.$taxonomy;

        if (array_key_exists($cacheKey, $this->termOptionsCache)) {
            return $this->termOptionsCache[$cacheKey];
        }

        $modelClass = $this->taxonomyModelFor($taxonomy, $entities);

        if ($modelClass === null) {
            return $this->termOptionsCache[$cacheKey] = [];
        }

        return $this->termOptionsCache[$cacheKey] = $this->termOptionsForModel($modelClass);
    }

    /**
     * @return array<string, string>
     */
    public function searchTermOptionsForTaxonomy(string $taxonomy, mixed $entities, string $search, int $limit = 50): array
    {
        $search = trim($search);

        if ($taxonomy === '') {
            return [];
        }

        $modelClass = $this->taxonomyModelFor($taxonomy, $entities);

        if ($modelClass === null) {
            return [];
        }

        $model = $modelClass::query()->getModel();
        $query = $modelClass::query()
            ->limit($limit)
            ->orderBy($model->getKeyName());

        if (method_exists($model, 'translations')) {
            $query->with('translations');
        }

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';

            $query->where(function ($builder) use ($model, $like): void {
                foreach (['title', 'name', 'label', 'slug'] as $column) {
                    if ($this->entityRegistry->databaseTableHasColumn($model->getTable(), $column)) {
                        $builder->orWhere($column, 'like', $like);
                    }
                }

                if (method_exists($model, 'translations')) {
                    $builder->orWhereHas('translations', function ($translationQuery) use ($like): void {
                        $translationQuery
                            ->where('title', 'like', $like)
                            ->orWhere('name', 'like', $like)
                            ->orWhere('label', 'like', $like);
                    });
                }
            });
        }

        return $query
            ->get()
            ->mapWithKeys(fn (Model $term): array => [
                (string) $term->getKey() => $this->termLabel($term),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function roleOptions(): array
    {
        if ($this->roleOptionsCache !== null) {
            return $this->roleOptionsCache;
        }

        if (! $this->supportsUserRoles()) {
            return $this->roleOptionsCache = [];
        }

        $rolesTable = (string) config('permission.table_names.roles');

        return $this->roleOptionsCache = DB::table($rolesTable)
            ->orderBy('name')
            ->pluck('name', 'name')
            ->mapWithKeys(fn (mixed $name, mixed $key): array => [(string) $key => (string) $name])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function availableParamOptions(): array
    {
        return [
            'record_type' => __('builder::builder.field_group.location_param_record_type'),
            'record_status' => __('builder::builder.field_group.location_param_record_status'),
            'user_role' => __('builder::builder.field_group.location_param_user_role'),
            'taxonomy' => __('builder::builder.field_group.location_param_taxonomy'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function availableParamOptionsForEntities(mixed $entities): array
    {
        $cacheKey = $this->entitiesCacheKey($entities);

        if (array_key_exists($cacheKey, $this->availableParamOptionsCache)) {
            return $this->availableParamOptionsCache[$cacheKey];
        }

        $options = [
            'user_role' => __('builder::builder.field_group.location_param_user_role'),
        ];

        if ($this->recordTypeOptionsForEntities($entities) !== []) {
            $options = [
                'record_type' => __('builder::builder.field_group.location_param_record_type'),
                ...$options,
            ];
        }

        if ($this->recordStatusOptionsForEntities($entities) !== []) {
            $options = [
                'record_status' => __('builder::builder.field_group.location_param_record_status'),
                ...$options,
            ];
        }

        if ($this->taxonomyKeysForEntities($entities) !== []) {
            $options['taxonomy'] = __('builder::builder.field_group.location_param_taxonomy');
        }

        return $this->availableParamOptionsCache[$cacheKey] = $options;
    }

    public function supportsUserRoles(): bool
    {
        if ($this->supportsUserRolesCache !== null) {
            return $this->supportsUserRolesCache;
        }

        if (! class_exists(Role::class) || ! trait_exists(HasRoles::class)) {
            return $this->supportsUserRolesCache = false;
        }

        $rolesTable = config('permission.table_names.roles');

        if (! is_string($rolesTable) || $rolesTable === '') {
            return $this->supportsUserRolesCache = false;
        }

        if (! $this->entityRegistry->databaseTableExists($rolesTable) || ! DB::table($rolesTable)->exists()) {
            return $this->supportsUserRolesCache = false;
        }

        $userModel = $this->authUserModel();

        if ($userModel === null || ! class_exists($userModel)) {
            return $this->supportsUserRolesCache = false;
        }

        return $this->supportsUserRolesCache = in_array(HasRoles::class, class_uses_recursive($userModel), true);
    }

    public function userRoleUnavailableReason(): ?string
    {
        if ($this->userRoleUnavailableReasonResolved) {
            return $this->userRoleUnavailableReasonCache;
        }

        $this->userRoleUnavailableReasonResolved = true;

        if (! class_exists(Role::class) || ! trait_exists(HasRoles::class)) {
            return $this->userRoleUnavailableReasonCache = __('builder::builder.field_group.location_value_role_unavailable_permissions');
        }

        $rolesTable = config('permission.table_names.roles');

        if (! is_string($rolesTable) || $rolesTable === '' || ! $this->entityRegistry->databaseTableExists($rolesTable)) {
            return $this->userRoleUnavailableReasonCache = __('builder::builder.field_group.location_value_role_unavailable_permissions');
        }

        if (! DB::table($rolesTable)->exists()) {
            return $this->userRoleUnavailableReasonCache = __('builder::builder.field_group.location_value_role_unavailable_empty');
        }

        $userModel = $this->authUserModel();

        if ($userModel === null || ! class_exists($userModel) || ! in_array(HasRoles::class, class_uses_recursive($userModel), true)) {
            return $this->userRoleUnavailableReasonCache = __('builder::builder.field_group.location_value_role_unavailable_user_model');
        }

        return $this->userRoleUnavailableReasonCache = null;
    }

    /**
     * @return array<string, string>
     */
    public function recordTypeOptionsForEntities(mixed $entities): array
    {
        $cacheKey = $this->entitiesCacheKey($entities);

        if (array_key_exists($cacheKey, $this->recordTypeOptionsCache)) {
            return $this->recordTypeOptionsCache[$cacheKey];
        }

        $options = [];

        foreach ($this->normalizeEntities($entities) as $entity) {
            $options = array_replace($options, $this->recordTypeOptionsForEntity($entity));

            $modelClass = $this->entityRegistry->modelFor($entity);

            if (! is_string($modelClass) || ! class_exists($modelClass)) {
                continue;
            }

            $options = array_replace($options, $this->recordTypeOptionsForModel($modelClass));
        }

        asort($options);

        return $this->recordTypeOptionsCache[$cacheKey] = $options;
    }

    /**
     * @return array<string, string>
     */
    protected function recordTypeOptionsForEntity(string $entity): array
    {
        $resourceClass = $this->entityRegistry->resourceFor($entity);

        if (! is_string($resourceClass) || ! class_exists($resourceClass) || ! method_exists($resourceClass, 'getTypeSelect')) {
            return [];
        }

        return $this->normalizeSelectOptions(
            $resourceClass::getTypeSelect()->getOptions(),
            fn (string $value): string => $this->recordTypeLabel($value),
        );
    }

    public function termLabelForValue(string $taxonomy, mixed $entities, mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        $modelClass = $this->taxonomyModelFor($taxonomy, $entities);

        if ($modelClass === null) {
            return null;
        }

        $query = $modelClass::query();

        if (method_exists($modelClass, 'translations')) {
            $query->with('translations');
        }

        $term = $query->find($value);

        return $term instanceof Model ? $this->termLabel($term) : null;
    }

    public function recordTypeLabelForValue(mixed $entities, mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        $options = $this->recordTypeOptionsForEntities($entities);

        return $options[$value]
            ?? $options[(string) $value]
            ?? $this->recordTypeLabel((string) $value);
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int|string, string>
     */
    public function termLabelsForValues(string $taxonomy, mixed $entities, array $values): array
    {
        $modelClass = $this->taxonomyModelFor($taxonomy, $entities);

        if ($modelClass === null || $values === []) {
            return [];
        }

        $normalizedValues = array_values(array_filter(
            $values,
            static fn (mixed $value): bool => filled($value),
        ));

        if ($normalizedValues === []) {
            return [];
        }

        $query = $modelClass::query()->whereIn(
            $modelClass::query()->getModel()->getKeyName(),
            $normalizedValues,
        );

        if (method_exists($modelClass, 'translations')) {
            $query->with('translations');
        }

        $termsById = $query->get()->keyBy(
            fn (Model $term): string => (string) $term->getKey(),
        );

        $labels = [];

        foreach ($normalizedValues as $value) {
            $term = $termsById[(string) $value] ?? null;

            if ($term instanceof Model) {
                $labels[$value] = $this->termLabel($term);
            }
        }

        return $labels;
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int|string, string>
     */
    public function recordTypeLabelsForValues(mixed $entities, array $values): array
    {
        $options = $this->recordTypeOptionsForEntities($entities);
        $labels = [];

        foreach ($values as $value) {
            if (! filled($value)) {
                continue;
            }

            $labels[$value] = $options[$value]
                ?? $options[(string) $value]
                ?? $this->recordTypeLabel((string) $value);
        }

        return $labels;
    }

    /**
     * @return array<string, string>
     */
    public function recordStatusOptionsForEntities(mixed $entities): array
    {
        $cacheKey = $this->entitiesCacheKey($entities);

        if (array_key_exists($cacheKey, $this->recordStatusOptionsCache)) {
            return $this->recordStatusOptionsCache[$cacheKey];
        }

        $options = [];

        foreach ($this->normalizeEntities($entities) as $entity) {
            $options = array_replace($options, $this->recordStatusOptionsForEntity($entity));

            $modelClass = $this->entityRegistry->modelFor($entity);

            if (! is_string($modelClass) || ! class_exists($modelClass)) {
                continue;
            }

            $options = array_replace($options, $this->recordStatusOptionsForModel($modelClass));
        }

        asort($options);

        return $this->recordStatusOptionsCache[$cacheKey] = $options;
    }

    public function recordStatusLabelForValue(mixed $entities, mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        $options = $this->recordStatusOptionsForEntities($entities);

        return $options[$value]
            ?? $options[(string) $value]
            ?? $this->recordStatusLabel((string) $value);
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int|string, string>
     */
    public function recordStatusLabelsForValues(mixed $entities, array $values): array
    {
        $options = $this->recordStatusOptionsForEntities($entities);
        $labels = [];

        foreach ($values as $value) {
            if (! filled($value)) {
                continue;
            }

            $labels[$value] = $options[$value]
                ?? $options[(string) $value]
                ?? $this->recordStatusLabel((string) $value);
        }

        return $labels;
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array<string, string>
     */
    protected function termOptionsForModel(string $modelClass): array
    {
        $query = $modelClass::query()
            ->orderBy($modelClass::query()->getModel()->getKeyName());

        $model = $query->getModel();

        if (method_exists($model, 'translations')) {
            $query->with('translations');
        }

        return $query
            ->get()
            ->mapWithKeys(fn (Model $term): array => [
                (string) $term->getKey() => $this->termLabel($term),
            ])
            ->all();
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array<string, string>
     */
    protected function recordTypeOptionsForModel(string $modelClass): array
    {
        if (array_key_exists($modelClass, $this->recordTypeOptionsByModelCache)) {
            return $this->recordTypeOptionsByModelCache[$modelClass];
        }

        $model = $modelClass::query()->getModel();
        $table = $model->getTable();

        if (! $this->entityRegistry->databaseTableExists($table) || ! $this->entityRegistry->databaseTableHasColumn($table, 'type')) {
            return $this->recordTypeOptionsByModelCache[$modelClass] = [];
        }

        return $this->recordTypeOptionsByModelCache[$modelClass] = $modelClass::query()
            ->whereNotNull('type')
            ->where('type', '!=', '')
            ->distinct()
            ->orderBy('type')
            ->pluck('type', 'type')
            ->mapWithKeys(fn (mixed $type): array => [
                (string) $type => $this->recordTypeLabel((string) $type),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function recordStatusOptionsForEntity(string $entity): array
    {
        $resourceClass = $this->entityRegistry->resourceFor($entity);

        if (! is_string($resourceClass) || ! class_exists($resourceClass)) {
            return [];
        }

        if (method_exists($resourceClass, 'getEditableTranslationStatusOptions')) {
            return $this->normalizeSelectOptions(
                $resourceClass::getEditableTranslationStatusOptions(),
                fn (string $value): string => $this->recordStatusLabel($value),
            );
        }

        return [];
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array<string, string>
     */
    protected function recordStatusOptionsForModel(string $modelClass): array
    {
        if (array_key_exists($modelClass, $this->recordStatusOptionsByModelCache)) {
            return $this->recordStatusOptionsByModelCache[$modelClass];
        }

        $model = $modelClass::query()->getModel();
        $table = $model->getTable();

        if (! $this->entityRegistry->databaseTableExists($table) || ! $this->entityRegistry->databaseTableHasColumn($table, 'status')) {
            return $this->recordStatusOptionsByModelCache[$modelClass] = [];
        }

        return $this->recordStatusOptionsByModelCache[$modelClass] = $modelClass::query()
            ->whereNotNull('status')
            ->where('status', '!=', '')
            ->distinct()
            ->orderBy('status')
            ->pluck('status', 'status')
            ->mapWithKeys(fn (mixed $status): array => [
                (string) $status => $this->recordStatusLabel((string) $status),
            ])
            ->all();
    }

    protected function termLabel(Model $term): string
    {
        $translatedTitle = $this->translatedTermAttribute($term, 'title');

        if (filled($translatedTitle)) {
            return $translatedTitle;
        }

        $translatedName = $this->translatedTermAttribute($term, 'name');

        if (filled($translatedName)) {
            return $translatedName;
        }

        if (method_exists($term, 'getDisplayTitleAttribute')) {
            $displayTitle = $term->display_title;

            if (filled($displayTitle) && ! str_starts_with((string) $displayTitle, 'ID: ')) {
                return (string) $displayTitle;
            }
        }

        foreach (['title', 'name', 'label', 'slug'] as $attribute) {
            $value = $term->getAttribute($attribute);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return '#'.$term->getKey();
    }

    protected function recordTypeLabel(string $value): string
    {
        return Str::headline($value);
    }

    protected function recordStatusLabel(string $value): string
    {
        return Str::headline($value);
    }

    /**
     * @return array<string, string>
     */
    protected function normalizeSelectOptions(mixed $options, callable $fallbackLabel): array
    {
        if ($options instanceof \Closure) {
            $options = $options();
        }

        if (! is_array($options)) {
            return [];
        }

        return collect($options)
            ->filter(fn (mixed $label, mixed $value): bool => filled($value))
            ->mapWithKeys(fn (mixed $label, mixed $value): array => [
                (string) $value => filled($label) ? (string) $label : $fallbackLabel((string) $value),
            ])
            ->all();
    }

    /**
     * @return class-string<Model>|null
     */
    protected function authUserModel(): ?string
    {
        $guard = (string) config('auth.defaults.guard', 'web');
        $provider = config("auth.guards.{$guard}.provider")
            ?? config('auth.guards.web.provider')
            ?? config('auth.defaults.provider');

        if (! is_string($provider) || $provider === '') {
            return null;
        }

        $model = Arr::get(config('auth.providers'), "{$provider}.model");

        return is_string($model) && $model !== '' ? $model : null;
    }

    protected function translatedTermAttribute(Model $term, string $attribute): ?string
    {
        if (! method_exists($term, 'translate')) {
            return null;
        }

        foreach ($this->localeResolver->fallbackChain() as $locale) {
            $translation = $this->translationForLocale($term, (string) $locale);

            if ($translation !== null && filled($translation->{$attribute} ?? null)) {
                return (string) $translation->{$attribute};
            }
        }

        foreach ($this->translationsForTerm($term) as $translation) {
            if (filled($translation->{$attribute} ?? null)) {
                return (string) $translation->{$attribute};
            }
        }

        return null;
    }

    protected function translationForLocale(Model $term, string $locale): ?object
    {
        if ($term->relationLoaded('translations')) {
            $match = $term->getRelation('translations')->firstWhere('locale', $locale);

            if ($match !== null) {
                return $match;
            }
        }

        $translation = $term->translate($locale, false);

        return $translation ?: null;
    }

    /**
     * @return iterable<object>
     */
    protected function translationsForTerm(Model $term): iterable
    {
        if ($term->relationLoaded('translations')) {
            return $term->getRelation('translations');
        }

        if (! method_exists($term, 'translations')) {
            return [];
        }

        return $term->translations()->get();
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    protected function taxonomyLabel(string $key, array $definition): string
    {
        $label = $definition['label'] ?? null;

        if (is_string($label) && str_starts_with($label, 'trans//')) {
            $translationKey = substr($label, strlen('trans//'));

            if ($translationKey !== '') {
                return __($translationKey);
            }
        }

        if (is_string($label) && $label !== '') {
            return $label;
        }

        return Str::headline($key);
    }

    protected function configKeyForEntity(string $entity): ?string
    {
        $modelClass = $this->entityRegistry->modelFor($entity);

        if ($modelClass !== null && method_exists($modelClass, 'getResourceName')) {
            return $modelClass::getResourceName();
        }

        return $entity;
    }

    /**
     * @return class-string<Model>|null
     */
    protected function taxonomyModelFor(string $taxonomy, mixed $entities): ?string
    {
        foreach ($this->normalizeEntities($entities) as $entity) {
            $configKey = $this->configKeyForEntity($entity);

            if ($configKey === null) {
                continue;
            }

            $this->taxonomyService->setCurrentResource($configKey);

            if (! array_key_exists($taxonomy, $this->taxonomyService->getTaxonomies())) {
                continue;
            }

            $modelClass = $this->taxonomyService->getTaxonomyModel($taxonomy);

            if (is_string($modelClass) && class_exists($modelClass)) {
                return $modelClass;
            }
        }

        return null;
    }

    protected function entitiesCacheKey(mixed $entities): string
    {
        $normalized = $this->normalizeEntities($entities);
        sort($normalized);

        return implode(',', $normalized);
    }

    /**
     * @return list<string>
     */
    protected function normalizeEntities(mixed $entities): array
    {
        if (is_string($entities) && $entities !== '') {
            return [$entities];
        }

        if (! is_array($entities)) {
            return [];
        }

        return array_values(array_filter(
            $entities,
            static fn (mixed $entity): bool => is_string($entity) && $entity !== '',
        ));
    }
}
