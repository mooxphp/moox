<?php

declare(strict_types=1);

namespace Moox\Builder\Data;

readonly class LocationContext
{
    public function __construct(
        public string $entity,
    ) {}
}
