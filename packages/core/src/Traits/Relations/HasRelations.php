<?php

declare(strict_types=1);

namespace Moox\Core\Traits\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Moox\Core\Relations\Enums\RelationKind;
use Moox\Core\Relations\RelationFactory;
use Moox\Core\Relations\ResolvedRelation;
use Moox\Core\Services\RelationService;

trait HasRelations
{
    use HasRelationService;

    /** @var array<class-string, true> */
    private static array $configuredRelationResolversRegistered = [];

    public static function bootHasRelations(): void
    {
        static::registerConfiguredRelationResolvers();
    }

    public function relation(string $key): Relation
    {
        return RelationFactory::make($this, $key, $this->relationService());
    }

    public function primaryRelation(string $key): Relation
    {
        return RelationFactory::primary($this, $key, $this->relationService());
    }

    /**
     * @param  list<int|string>  $ids
     */
    public function syncRelation(string $key, array $ids): void
    {
        if (! $this->relationService()->has($key)) {
            return;
        }

        $this->relation($key)->sync($ids);
    }

    /**
     * @param  array<int, mixed>  $parameters
     */
    public function relationCall(string $method, array $parameters): mixed
    {
        $service = $this->relationService();

        foreach ($service->all() as $key => $resolved) {
            if ($key === $method || $resolved->relationship === $method) {
                return $this->relation($key);
            }
        }

        return parent::__call($method, $parameters);
    }

    /**
     * @param  array<int, mixed>  $parameters
     */
    public function __call($method, $parameters): mixed
    {
        $service = $this->relationService();

        foreach ($service->all() as $key => $resolved) {
            if ($key === $method || $resolved->relationship === $method) {
                return $this->relation($key);
            }
        }

        foreach ($service->all() as $key => $resolved) {
            if (
                $resolved->kind === RelationKind::MorphPivot
                && ($resolved->config['primary_relationship'] ?? null) === $method
            ) {
                return $this->primaryRelation($key);
            }
        }

        return parent::__call($method, $parameters);
    }

    protected static function registerConfiguredRelationResolvers(): void
    {
        if (! method_exists(static::class, 'getResourceName')) {
            return;
        }

        if (isset(static::$configuredRelationResolversRegistered[static::class])) {
            return;
        }

        static::$configuredRelationResolversRegistered[static::class] = true;

        app(RelationService::class)->withResource(static::getResourceName(), function (RelationService $service): void {
            foreach ($service->all() as $key => $resolved) {
                foreach (static::configuredRelationNames($key, $resolved) as $name) {
                    if (method_exists(static::class, $name)) {
                        continue;
                    }

                    static::resolveRelationUsing($name, function (Model $model) use ($key, $name, $resolved): Relation {
                        /** @var self $model */
                        if (($resolved->config['primary_relationship'] ?? null) === $name) {
                            return $model->primaryRelation($key);
                        }

                        return $model->relation($key);
                    });
                }
            }
        });
    }

    /**
     * @return list<string>
     */
    private static function configuredRelationNames(string $key, ResolvedRelation $resolved): array
    {
        $names = [$key, $resolved->relationship];

        $primaryRelationship = $resolved->config['primary_relationship'] ?? null;

        if (is_string($primaryRelationship) && $primaryRelationship !== '') {
            $names[] = $primaryRelationship;
        }

        return array_values(array_unique(array_filter(
            $names,
            fn (mixed $name): bool => is_string($name) && $name !== '',
        )));
    }
}
