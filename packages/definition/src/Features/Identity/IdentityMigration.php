<?php

declare(strict_types=1);

namespace Moox\Definition\Features\Identity;

use Illuminate\Database\Schema\Blueprint;
use Moox\Definition\Laravel\FeatureMigration;
use Moox\Definition\Projection\IncompleteProjection;
use Moox\Definition\Resolution\ResolvedEntity;

final class IdentityMigration implements FeatureMigration
{
    public function apply(Blueprint $table, ResolvedEntity $entity): void
    {
        foreach (['id', 'ulid', 'uuid'] as $name) {
            if ($entity->field($name) === null) {
                throw new IncompleteProjection("Identity field [{$name}] is missing from the resolved entity [{$entity->name()}].");
            }
        }
    }
}
