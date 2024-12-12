<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Moox\Builder\Contexts\BuildContext;
use RuntimeException;

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
            throw new RuntimeException('Context must be set before execution');
        }
    }

    protected function validateContext(): void
    {
        $this->ensureContextIsSet();
        $this->validateContextType();
        $this->validateContextConfig();
    }

    protected function validateContextType(): void
    {
        $validTypes = ['app', 'preview', 'package'];
        if (! in_array($this->context->getContextType(), $validTypes)) {
            throw new RuntimeException(
                'Invalid context type: '.$this->context->getContextType()
            );
        }
    }

    protected function validateContextConfig(): void
    {
        $config = $this->context->getConfig();
        $contextType = $this->context->getContextType();

        if (! isset($config['base_path'], $config['base_namespace'], $config['generators'])) {
            throw new RuntimeException(
                "Missing required configuration for context {$contextType}"
            );
        }

        foreach ($config['generators'] as $type => $genConfig) {
            if (! isset($genConfig['path'])) {
                throw new RuntimeException(
                    "Missing path configuration for generator {$type} in context {$contextType}"
                );
            }
        }

        if ($contextType === 'package') {
            $this->validatePackageConfig($config);
        }
    }

    protected function validatePackageConfig(array $config): void
    {
        if (! isset($config['package']['name'], $config['package']['namespace'])) {
            throw new RuntimeException('Invalid package configuration');
        }
    }

    abstract public function execute(): void;
}
