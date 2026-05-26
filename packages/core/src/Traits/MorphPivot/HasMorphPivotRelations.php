<?php

declare(strict_types=1);

namespace Moox\Core\Traits\MorphPivot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Log;
use Moox\Core\Support\MorphPivot\MorphPivotRelationRegistry;

trait HasMorphPivotRelations
{
    use HasMorphPivotRelationService;

    public function morphPivotRelation(string $relation): MorphToMany
    {
        $relations = $this->getMorphPivotRelationService()->getMorphPivotRelations();

        if (! isset($relations[$relation])) {
            Log::error('Morph pivot relation not found: '.$relation);

            return $this->emptyMorphPivotRelation();
        }

        $model = $this->getMorphPivotRelationService()->getMorphPivotRelationModel($relation);

        if ($model === null) {
            return $this->emptyMorphPivotRelation();
        }

        $service = $this->getMorphPivotRelationService();

        $builder = $this->morphToMany(
            $model,
            $service->getMorphName($relation),
            $service->getPivotTable($relation),
            $service->getForeignKey($relation),
            $service->getRelatedKey($relation),
        );

        $pivotColumns = $service->getMorphPivotRelationPivotColumns($relation);

        if ($pivotColumns !== []) {
            $builder->withPivot($pivotColumns);
        }

        $pivotModel = $service->getMorphPivotRelationPivotModel($relation);

        if ($pivotModel !== null) {
            $builder->using($pivotModel);
        }

        return $builder->withTimestamps();
    }

    public function primaryMorphPivotRelation(string $relation): MorphToMany
    {
        $service = $this->getMorphPivotRelationService();
        $query = $this->morphPivotRelation($relation);

        if ($service->getPrimaryOn($relation) === 'related') {
            $model = $service->getMorphPivotRelationModel($relation);

            if ($model === null) {
                return $query;
            }

            $table = (new $model)->getTable();

            return $query->where(
                "{$table}.{$service->getPrimaryRelatedColumn($relation)}",
                $service->getPrimaryValue($relation),
            );
        }

        return $query->wherePivot(
            $service->getPrimaryColumn($relation),
            $service->getPrimaryValue($relation),
        );
    }

    protected function emptyMorphPivotRelation(): MorphToMany
    {
        return $this->morphToMany(Model::class, 'addressable', 'addressables')->whereRaw('1 = 0');
    }

    /**
     * Resolve {@see morphPivotRelation()} by config key or {@see MorphPivotRelationService::getRelationshipMethodName()}.
     *
     * @param  array<int, mixed>  $parameters
     */
    public function morphPivotCall(string $method, array $parameters): mixed
    {
        $service = $this->getMorphPivotRelationService();

        foreach ($service->getMorphPivotRelations() as $configKey => $config) {
            if (! is_array($config)) {
                continue;
            }

            $config = MorphPivotRelationRegistry::mergeConfig($config);

            if ($configKey === $method || ($config['relationship'] ?? $configKey) === $method) {
                return $this->morphPivotRelation((string) $configKey);
            }
        }

        return parent::__call($method, $parameters);
    }
}
