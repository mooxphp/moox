<?php

declare(strict_types=1);

namespace Moox\Core\Traits;

use Moox\Builder\Concerns\HasCustomFields as BuilderHasCustomFields;

if (trait_exists(BuilderHasCustomFields::class)) {
    class_alias(BuilderHasCustomFields::class, HasCustomFields::class);
} else {
    trait HasCustomFields
    {
        // moox/builder is optional. When not installed, custom fields are disabled.
    }
}
