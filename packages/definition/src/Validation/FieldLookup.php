<?php

declare(strict_types=1);

namespace Moox\Definition\Validation;

use Moox\Definition\Definitions\Field;

interface FieldLookup
{
    public function find(string $package, string $entity, string $field): Field|string;

    public function findEntity(string $package, string $entity): ?string;
}
