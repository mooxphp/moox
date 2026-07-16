<?php

declare(strict_types=1);

namespace Moox\Builder\Data;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Moox\Builder\Concerns\HasCustomFields;
use Moox\Builder\Concerns\InteractsWithCustomFields;
use Moox\Core\Services\TaxonomyService;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;

readonly class LocationContext
{
    /**
     * @param  array<string, mixed>  $params
     */
    public function __construct(
        public string $entity,
        public array $params = [],
        public ?Model $record = null,
    ) {}

    public function get(string $param, mixed $default = null): mixed
    {
        return $this->params[$param] ?? $default;
    }

    public function hasRecord(): bool
    {
        return $this->record !== null;
    }

    /**
     * @param  class-string  $resourceClass
     */
    public static function forResource(string $resourceClass, ?Model $record = null): self
    {
        /** @var class-string<HasCustomFields> $resourceClass */
        $entity = $resourceClass::resolveCustomFieldsEntityIdentifier();
        $params = $resourceClass::customFieldsLocationParams($record);

        if ($record !== null) {
            $params = array_merge(self::paramsFromRecord($record), $params);
        }

        $params = array_merge($params, self::paramsFromAuthenticatedUser());

        return new self($entity, $params, $record);
    }

    public static function forEntity(string $entity, ?Model $record = null, ?string $modelClass = null): self
    {
        $params = [];

        if ($record !== null) {
            $params = self::paramsFromRecord($record);

            if ($modelClass === null && in_array(InteractsWithCustomFields::class, class_uses_recursive($record), true)) {
                $params = array_merge($params, $record::customFieldsLocationParams($record));
            }
        }

        if ($modelClass !== null && is_subclass_of($modelClass, Model::class)) {
            /** @var class-string<Model&InteractsWithCustomFields> $modelClass */
            if (method_exists($modelClass, 'customFieldsLocationParams')) {
                $params = array_merge($params, $modelClass::customFieldsLocationParams($record));
            }
        }

        $params = array_merge($params, self::paramsFromAuthenticatedUser());

        return new self($entity, $params, $record);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function paramsFromRecord(Model $record): array
    {
        $params = [];

        if ($record->offsetExists('type') && filled($record->getAttribute('type'))) {
            $params['record_type'] = (string) $record->getAttribute('type');
        }

        $recordStatus = self::resolveRecordStatus($record);

        if ($recordStatus !== null) {
            $params['record_status'] = $recordStatus;
        }

        if (in_array(HasModelTaxonomy::class, class_uses_recursive($record), true)) {
            $resourceName = method_exists($record, 'getResourceName')
                ? $record::getResourceName()
                : null;

            if (filled($resourceName)) {
                $taxonomyService = app(TaxonomyService::class);
                $taxonomyService->setCurrentResource((string) $resourceName);

                foreach (array_keys($taxonomyService->getTaxonomies()) as $taxonomy) {
                    $relationQuery = $record->taxonomy($taxonomy);
                    $relationName = $relationQuery->getRelationName();

                    $ids = $record->relationLoaded($relationName)
                        ? collect($record->getRelation($relationName))->modelKeys()
                        : $relationQuery->pluck($relationQuery->getRelated()->getQualifiedKeyName())->all();

                    if ($ids !== []) {
                        $params['taxonomy:'.$taxonomy] = array_values(array_map('intval', $ids));
                    }
                }
            }
        }

        return $params;
    }

    protected static function resolveRecordStatus(Model $record): ?string
    {
        if (method_exists($record, 'translate')) {
            $locale = request()->query('lang') ?? request()->input('lang');

            if (! is_string($locale) || $locale === '') {
                $locale = app()->getLocale();
            }

            $translation = $record->translate($locale);

            if ($translation !== null && filled($translation->translation_status ?? null)) {
                $status = $translation->translation_status;

                return $status instanceof \BackedEnum ? $status->value : (string) $status;
            }
        }

        if ($record->offsetExists('status') && filled($record->getAttribute('status'))) {
            return (string) $record->getAttribute('status');
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function paramsFromAuthenticatedUser(): array
    {
        $user = auth()->user();

        if (! $user instanceof Authenticatable) {
            return [];
        }

        if (! method_exists($user, 'getRoleNames')) {
            return [];
        }

        $roles = $user->getRoleNames()->values()->all();

        if ($roles === []) {
            return [];
        }

        return ['user_role' => $roles];
    }
}
