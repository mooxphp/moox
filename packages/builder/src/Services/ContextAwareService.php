<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Moox\Builder\Contexts\BuildContext;
use RuntimeException;

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

    protected function ensureContextIsSet(): void
    {
        if (! isset($this->context)) {
            throw new RuntimeException('BuildContext must be set before using this service');
        }
    }

    protected function validateContext(string $context): void
    {
        if (! in_array($context, ['preview', 'app', 'package'])) {
            throw new RuntimeException('Invalid build context');
        }
    }

    abstract public function execute(): void;
}
