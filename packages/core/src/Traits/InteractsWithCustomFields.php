<?php

declare(strict_types=1);

namespace Moox\Core\Traits;

use Moox\Builder\Concerns\InteractsWithCustomFields as BuilderInteractsWithCustomFields;

if (trait_exists(BuilderInteractsWithCustomFields::class)) {
    class_alias(BuilderInteractsWithCustomFields::class, InteractsWithCustomFields::class);
} else {
    trait InteractsWithCustomFields
    {
        // moox/builder is optional. When not installed, custom fields are disabled.
    }
}
