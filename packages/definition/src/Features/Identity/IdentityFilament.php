<?php

declare(strict_types=1);

namespace Moox\Definition\Features\Identity;

use Filament\Support\Enums\Operation;
use Moox\Definition\Definitions\FieldRole;
use Moox\Definition\Filament\Resource;

final class IdentityFilament
{
    public function apply(Resource $resource): void
    {
        foreach ($resource->entity()->fields() as $field) {
            if ($field->attributes()->role() !== FieldRole::PrimaryIdentifier) {
                continue;
            }

            $resource->field($field->name())->formComponent()->hiddenOn([
                Operation::Create,
                Operation::Edit,
            ]);
        }
    }
}
