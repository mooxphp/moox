<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Services;

use Moox\Builder\Builder\Contexts\BuildContext;

abstract class AbstractService
{
    public function __construct(
        protected readonly BuildContext $context
    ) {}

    abstract public function execute(): void;
}
