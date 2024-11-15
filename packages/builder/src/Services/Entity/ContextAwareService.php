<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Moox\Builder\Contexts\BuildContext;

abstract class ContextAwareService
{
    protected BuildContext $context;

    protected array $blocks = [];

    public function __construct(?BuildContext $context = null, array $blocks = [])
    {
        if ($context) {
            $this->context = $context;
            $this->blocks = $blocks;
        }
    }

    public function setContext(BuildContext $context): void
    {
        $this->context = $context;
    }

    public function setBlocks(array $blocks): void
    {
        $this->blocks = $blocks;
    }

    abstract public function execute(): void;
}
