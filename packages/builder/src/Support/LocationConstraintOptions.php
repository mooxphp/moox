<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Moox\Builder\Registry\EntityRegistry;
use Moox\Core\Services\TaxonomyService;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

final class LocationConstraintOptions
{
    public function __construct(
        protected EntityRegistry $entityRegistry,
        protected TaxonomyService $taxonomyService,
        protected BuilderLocaleResolver $localeResolver,
    ) {}

    /**
     * @return array<string, string>
     */
    public function taxonomyKeysForEntities(mixed $entities): array
    {
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

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public function termOptionsForTaxonomy(string $taxonomy, mixed $entities): array
    {
        if ($taxonomy === '') {
            return [];
        }

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

            if (! is_string($modelClass) || ! class_exists($modelClass)) {
                continue;
            }

            return $this->termOptionsForModel($modelClass);
        }

        return [];
    }

    /**
     * @return array<string, string>
     */
    public function roleOptions(): array
    {
        if (! $this->supportsUserRoles()) {
            return [];
        }

        $rolesTable = (string) config('permission.table_names.roles');

        return DB::table($rolesTable)
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
            'user_role' => __('builder::builder.field_group.location_param_user_role'),
            'taxonomy' => __('builder::builder.field_group.location_param_taxonomy'),
        ];
    }

    public function supportsUserRoles(): bool
    {
        if (! class_exists(Role::class) || ! trait_exists(HasRoles::class)) {
            return false;
        }

        $rolesTable = config('permission.table_names.roles');

        if (! is_string($rolesTable) || $rolesTable === '') {
            return false;
        }

        if (! Schema::hasTable($rolesTable) || ! DB::table($rolesTable)->exists()) {
            return false;
        }

        $userModel = $this->authUserModel();

        if ($userModel === null || ! class_exists($userModel)) {
            return false;
        }

        return in_array(HasRoles::class, class_uses_recursive($userModel), true);
    }

    public function userRoleUnavailableReason(): ?string
    {
        if (! class_exists(Role::class) || ! trait_exists(HasRoles::class)) {
            return __('builder::builder.field_group.location_value_role_unavailable_permissions');
        }

        $rolesTable = config('permission.table_names.roles');

        if (! is_string($rolesTable) || $rolesTable === '' || ! Schema::hasTable($rolesTable)) {
            return __('builder::builder.field_group.location_value_role_unavailable_permissions');
        }

        if (! DB::table($rolesTable)->exists()) {
            return __('builder::builder.field_group.location_value_role_unavailable_empty');
        }

        $userModel = $this->authUserModel();

        if ($userModel === null || ! class_exists($userModel) || ! in_array(HasRoles::class, class_uses_recursive($userModel), true)) {
            return __('builder::builder.field_group.location_value_role_unavailable_user_model');
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public function recordTypeOptionsForEntities(mixed $entities): array
    {
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

        return $options;
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

        $options = $resourceClass::getTypeSelect()->getOptions();

        if ($options instanceof \Closure) {
            $options = $options();
        }

        if (! is_array($options)) {
            return [];
        }

        return collect($options)
            ->filter(fn (mixed $label, mixed $value): bool => filled($value))
            ->mapWithKeys(fn (mixed $label, mixed $value): array => [
                (string) $value => filled($label) ? (string) $label : $this->recordTypeLabel((string) $value),
            ])
            ->all();
    }

    public function termLabelForValue(string $taxonomy, mixed $entities, mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        $options = $this->termOptionsForTaxonomy($taxonomy, $entities);

        return $options[$value]
            ?? $options[(int) $value]
            ?? $options[(string) $value]
            ?? null;
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
        $options = $this->termOptionsForTaxonomy($taxonomy, $entities);
        $labels = [];

        foreach ($values as $value) {
            $label = $options[$value]
                ?? $options[(int) $value]
                ?? $options[(string) $value]
                ?? null;

            if ($label !== null) {
                $labels[$value] = $label;
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
        $model = $modelClass::query()->getModel();
        $table = $model->getTable();

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'type')) {
            return [];
        }

        return $modelClass::query()
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
