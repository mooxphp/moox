<?php

declare(strict_types=1);

namespace Moox\Core\Traits\MorphPivot;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Moox\Core\Traits\Relations\HasRelations;

trait HasMorphPivotRelations
{
    use HasRelations;

    public function morphPivotRelation(string $relation): MorphToMany
    {
        /** @var MorphToMany */
        return $this->relation($relation);
    }

    public function primaryMorphPivotRelation(string $relation): MorphToMany
    {
        /** @var MorphToMany */
        return $this->primaryRelation($relation);
    }

    /**
     * @param  array<int, mixed>  $parameters
     */
    public function morphPivotCall(string $method, array $parameters): mixed
    {
        return $this->relationCall($method, $parameters);
    }
}
