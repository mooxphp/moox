<?php

declare(strict_types=1);

namespace Moox\Definition\Features\Identity;

use Moox\Definition\Projection\IncompleteProjection;
use Moox\Definition\Resolution\ResolvedEntity;

final class IdentityModel
{
    public function apply(ResolvedEntity $entity): void
    {
        foreach (['id', 'ulid', 'uuid'] as $name) {
            $field = $entity->field($name);

            if ($field === null || ! $field->attributes()->systemManaged() || ! $field->attributes()->immutable() || ! $field->attributes()->isGenerated()) {
                throw new IncompleteProjection("Identity field [{$name}] is missing its identifier semantics on [{$entity->name()}].");
            }
        }
    }
}
