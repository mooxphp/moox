<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Features;

use Moox\Builder\Builder\Generators\Feature as FeatureInterface;

abstract class Feature implements FeatureInterface
{
    protected array $useStatements = [
        'resource' => [
            'actions' => [],
            'columns' => [],
            'filters' => [],
            'forms' => [],
        ],
        'model' => [],
        'migration' => [],
        'pages' => [
            'create' => [],
            'edit' => [],
            'list' => [],
            'view' => [],
        ],
    ];

    protected array $traits = [
        'resource' => [],
        'model' => [],
    ];

    protected array $methods = [
        'resource' => [],
        'model' => [],
        'pages' => [
            'create' => [],
            'edit' => [],
            'list' => [],
            'view' => [],
        ],
    ];

    public function __construct()
    {
        $this->initializeFeature();
    }

    protected function initializeFeature(): void
    {
        // Child classes can override this to set their specific properties
    }

    public function getUseStatements(string $context, ?string $subContext = null): array
    {
        if ($subContext) {
            return $this->useStatements[$context][$subContext] ?? [];
        }

        return $this->useStatements[$context] ?? [];
    }

    public function getTraits(string $context, ?string $subContext = null): array
    {
        if ($subContext) {
            return $this->traits[$context][$subContext] ?? [];
        }

        return $this->traits[$context] ?? [];
    }

    public function getMethods(string $context, ?string $subContext = null): array
    {
        if ($subContext) {
            return $this->methods[$context][$subContext] ?? [];
        }

        return $this->methods[$context] ?? [];
    }

    public function getModelUseStatements(): array
    {
        return $this->getUseStatements('model');
    }

    public function getModelTraits(): array
    {
        return $this->getTraits('model');
    }

    public function getModelMethods(): array
    {
        return $this->getMethods('model');
    }

    public function getPluginUseStatements(): array
    {
        return [];
    }

    public function getPluginBootMethods(): array
    {
        return [];
    }

    public function getPluginMethods(): array
    {
        return [];
    }

    abstract public function getFormFields(): array;

    abstract public function getTableColumns(): array;

    abstract public function getTableFilters(): array;

    abstract public function getActions(): array;

    abstract public function getMigrations(): array;
}
