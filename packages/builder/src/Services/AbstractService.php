<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Moox\Builder\Contexts\BuildContext;

abstract class AbstractService
{
    public function __construct(
        protected readonly BuildContext $context,
        protected readonly array $blocks = []
    ) {}

    abstract public function execute(): void;
}
