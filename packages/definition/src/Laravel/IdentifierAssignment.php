<?php

declare(strict_types=1);

namespace Moox\Definition\Laravel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Moox\Definition\Projection\UnsupportedProjection;
use Moox\Definition\Resolution\ResolvedEntity;

final class IdentifierAssignment
{
    public function assign(Model $model, ResolvedEntity $entity): void
    {
        foreach ($entity->fields() as $field) {
            if (! $field->attributes()->isGenerated()) {
                continue;
            }

            $generator = $field->attributes()->generator()->value();

            if (! is_string($generator) || $generator === 'primary') {
                continue;
            }

            $current = $model->getAttribute($field->name());

            if ($current !== null && $current !== '') {
                continue;
            }

            $model->setAttribute($field->name(), match ($generator) {
                'uuid' => (string) Str::uuid(),
                'ulid' => (string) Str::ulid(),
                default => throw UnsupportedProjection::generator('Laravel model', $field->name(), $generator),
            });
        }
    }
}
