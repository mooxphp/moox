<?php

declare(strict_types=1);

namespace Moox\Builder\Concerns;

use Astrotomic\Translatable\Translatable;

trait InteractsWithTranslatedCustomFields
{
    use InteractsWithCustomFields, Translatable {
        InteractsWithCustomFields::getAttribute insteadof Translatable;
        InteractsWithCustomFields::setAttribute insteadof Translatable;
        Translatable::getAttribute as getTranslatedAttribute;
        Translatable::setAttribute as setTranslatedAttribute;
    }
}
