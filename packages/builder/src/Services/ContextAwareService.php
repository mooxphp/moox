<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Moox\Builder\Contexts\BuildContext;

abstract class ContextAwareService
{
    protected ?BuildContext $context = null;

    public function setContext(BuildContext $context): void
    {
        $this->context = $context;
    }

    protected function ensureContextIsSet(): void
    {
        if (! $this->context) {
            throw new \RuntimeException('Context must be set before execution');
        }
    }

    abstract public function execute(): void;
}
