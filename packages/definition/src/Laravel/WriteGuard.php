<?php

declare(strict_types=1);

namespace Moox\Definition\Laravel;

use Illuminate\Database\Eloquent\Model;
use Moox\Definition\Resolution\ResolvedEntity;

final class WriteGuard
{
    public function assert(Model $model, ResolvedEntity $entity): void
    {
        foreach ($entity->fields() as $field) {
            if (! $model->isDirty($field->name())) {
                continue;
            }

            if ($field->attributes()->immutable()) {
                throw new WriteOwnershipException("Field [{$field->name()}] is immutable.");
            }

            if ($field->attributes()->systemManaged()) {
                throw new WriteOwnershipException("Field [{$field->name()}] is system-managed.");
            }
        }
    }
}
