<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks;

abstract class AbstractBlock
{
    protected array $useStatements = [
        'model' => [],
        'migration' => [],
        'resource' => [
            'forms' => [],
            'columns' => [],
            'filters' => [],
            'actions' => [],
        ],
        'pages' => [
            'list' => [],
            'create' => [],
            'edit' => [],
            'view' => [],
        ],
        'plugin' => [],
    ];

    protected array $traits = [
        'model' => [],
        'resource' => [],
        'pages' => [
            'list' => [],
            'create' => [],
            'edit' => [],
            'view' => [],
        ],
    ];

    protected array $methods = [
        'model' => [
            'scopes' => [],
            'relations' => [],
            'accessors' => [],
            'mutators' => [],
        ],
        'resource' => [],
        'pages' => [
            'list' => [],
            'create' => [],
            'edit' => [],
            'view' => [],
        ],
    ];

    protected array $formFields = [
        'resource' => [],
        'pages' => [
            'create' => [],
            'edit' => [],
        ],
    ];

    protected array $tableColumns = [
        'resource' => [],
        'pages' => [
            'list' => [],
            'related' => [],
        ],
    ];

    protected array $filters = [
        'resource' => [],
        'pages' => [
            'list' => [],
            'related' => [],
        ],
    ];

    protected array $actions = [
        'resource' => [],
        'table' => [],
        'bulk' => [],
        'pages' => [
            'list' => [
                'header' => [],
                'table' => [],
                'bulk' => [],
            ],
            'create' => [
                'header' => [],
                'form' => [],
            ],
            'edit' => [
                'header' => [],
                'form' => [],
            ],
            'view' => [
                'header' => [],
            ],
        ],
    ];

    protected array $migrations = [
        'fields' => [],
        'indexes' => [],
        'foreign_keys' => [],
    ];

    protected array $factories = [
        'model' => [
            'states' => [],
            'definitions' => [],
        ],
    ];

    protected array $tests = [
        'unit' => [
            'model' => [],
        ],
        'feature' => [
            'resource' => [],
            'api' => [],
        ],
    ];

    protected bool $isFeature = false;

    protected bool $isSingleFeature = false;

    protected array $includes = [];

    protected array $incompatibleWith = [];

    protected array $casts = [];

    public function __construct(
        protected string $name,
        protected string $label,
        protected string $description,
        protected bool $nullable = false,
    ) {}

    public function getUseStatements(string $context, ?string $subContext = null): array
    {
        return $subContext
            ? ($this->useStatements[$context][$subContext] ?? [])
            : ($this->useStatements[$context] ?? []);
    }

    public function getTraits(string $context, ?string $subContext = null): array
    {
        return $subContext
            ? ($this->traits[$context][$subContext] ?? [])
            : ($this->traits[$context] ?? []);
    }

    public function getMethods(string $context, ?string $subContext = null): array
    {
        return $subContext
            ? ($this->methods[$context][$subContext] ?? [])
            : ($this->methods[$context] ?? []);
    }

    public function getFormFields(string $context = 'resource', ?string $subContext = null): array
    {
        return $subContext
            ? ($this->formFields[$context][$subContext] ?? [])
            : ($this->formFields[$context] ?? []);
    }

    public function getTableColumns(string $context = 'resource', ?string $subContext = null): array
    {
        return $subContext
            ? ($this->tableColumns[$context][$subContext] ?? [])
            : ($this->tableColumns[$context] ?? []);
    }

    public function getFilters(string $context = 'resource', ?string $subContext = null): array
    {
        return $subContext
            ? ($this->filters[$context][$subContext] ?? [])
            : ($this->filters[$context] ?? []);
    }

    public function getActions(string $context = 'resource', ?string $subContext = null): array
    {
        return $subContext
            ? ($this->actions[$context][$subContext] ?? [])
            : ($this->actions[$context] ?? []);
    }

    public function getMigrations(string $type = 'fields'): array
    {
        return $this->migrations[$type] ?? [];
    }

    public function getFactories(string $context = 'model', ?string $subContext = null): array
    {
        return $subContext
            ? ($this->factories[$context][$subContext] ?? [])
            : ($this->factories[$context] ?? []);
    }

    public function getTests(string $type = 'unit', ?string $subContext = null): array
    {
        return $subContext
            ? ($this->tests[$type][$subContext] ?? [])
            : ($this->tests[$type] ?? []);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function isFeature(): bool
    {
        return $this->isFeature;
    }

    public function isSingleFeature(): bool
    {
        return $this->isSingleFeature;
    }

    public function getIncludes(): array
    {
        return $this->includes;
    }

    public function getIncompatibleWith(): array
    {
        return $this->incompatibleWith;
    }
}
