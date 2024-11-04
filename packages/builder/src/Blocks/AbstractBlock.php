<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks;

abstract class AbstractBlock
{
    protected bool $fillable = true;

    protected array $casts = [
        'model' => [],
    ];

    protected array $migrations = [
        'fields' => [],
        'indexes' => [],
        'foreign_keys' => [],
    ];

    protected array $useStatements = [
        'model' => [],
        'migration' => [],
        'resource' => [
            'forms' => [],
            'columns' => [],
            'filters' => [],
            'actions' => [],
            'traits' => [],
            'pages' => [],
        ],
        'pages' => [
            'list' => [],
            'create' => [],
            'edit' => [],
            'view' => [],
        ],
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
    ];

    protected array $tableColumns = [
        'resource' => [],
    ];

    protected array $factories = [
        'model' => [
            'definitions' => [],
            'states' => [],
        ],
    ];

    protected array $tests = [
        'unit' => [
            'model' => [],
            'resource' => [],
        ],
        'feature' => [
            'model' => [],
            'resource' => [],
        ],
    ];

    protected array $filters = [
        'resource' => [],
    ];

    protected array $actions = [
        'resource' => [],
        'pages' => [
            'list' => [],
            'create' => [],
            'edit' => [
                'header' => [],
                'footer' => [],
            ],
            'view' => [],
        ],
    ];

    protected ?object $context = null;

    protected array $includes = [];

    protected array $incompatibleWith = [];

    public function resolveBlockDependencies(array $selectedBlocks): array
    {
        $resolvedBlocks = $selectedBlocks;

        foreach ($selectedBlocks as $block) {
            if (isset($block->includes)) {
                foreach ($block->includes as $includedBlock) {
                    $resolvedBlocks = array_filter(
                        $resolvedBlocks,
                        fn ($b) => ! ($b instanceof $includedBlock)
                    );
                }
            }
        }

        return $resolvedBlocks;
    }

    public function __construct(
        protected readonly string $name,
        protected readonly string $label,
        protected readonly string $description,
        protected readonly bool $nullable = false,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function isFillable(): bool
    {
        return $this->fillable;
    }

    public function getMigrations(): array
    {
        return $this->migration();
    }

    public function migration(): array
    {
        return $this->migrations['fields'] ?? [];
    }

    public function modelCast(): ?string
    {
        if (empty($this->casts['model'])) {
            return null;
        }

        if (is_array($this->casts['model'])) {
            return implode(",\n        ", $this->casts['model']);
        }

        return $this->casts['model'];
    }

    public function getMethods(string $context): array
    {
        return $this->methods[$context] ?? [];
    }

    public function getUseStatements(string $context, ?string $subContext = null): array
    {
        if ($subContext) {
            return $this->useStatements[$context][$subContext] ?? [];
        }

        return $this->useStatements[$context] ?? [];
    }

    public function getTraits(string $context): array
    {
        return $this->traits[$context] ?? [];
    }

    public function formField(): string
    {
        return implode(",\n            ", $this->formFields['resource'] ?? []);
    }

    public function tableColumn(): string
    {
        return implode(",\n            ", $this->tableColumns['resource'] ?? []);
    }

    public function getTableActions(): array
    {
        return $this->actions['resource'] ?? [];
    }

    public function getPageActions(string $page, string $position = ''): array
    {
        if ($position) {
            return $this->actions['pages'][$page][$position] ?? [];
        }

        return $this->actions['pages'][$page] ?? [];
    }

    public function getFilters(): array
    {
        return $this->filters['resource'] ?? [];
    }

    public function getFactoryDefinitions(): array
    {
        return $this->factories['model']['definitions'] ?? [];
    }

    public function getFactoryStates(): array
    {
        return $this->factories['model']['states'] ?? [];
    }

    public function getTests(string $type, string $context): array
    {
        return $this->tests[$type][$context] ?? [];
    }

    public function getTableFilters(): array
    {
        return $this->filters['resource'] ?? [];
    }

    public function getResourceUseStatements(): array
    {
        if (! $this->context) {
            return [];
        }

        $baseStatements = [
            'use Filament\Forms\Form;',
            'use Filament\Resources\Resource;',
            'use Filament\Tables\Table;',
            'use Illuminate\Database\Eloquent\Builder;',
        ];

        $statements = array_merge(
            $baseStatements,
            $this->useStatements['model'] ?? [],
            $this->useStatements['resource']['forms'] ?? [],
            $this->useStatements['resource']['columns'] ?? [],
            $this->useStatements['resource']['filters'] ?? [],
            $this->useStatements['resource']['actions'] ?? [],
            $this->useStatements['resource']['traits'] ?? [],
            $this->useStatements['pages'] ?? []
        );

        if ($this->traits['resource']) {
            foreach ($this->traits['resource'] as $trait) {
                $statements[] = 'use Moox\Core\Traits\\'.$trait.';';
            }
        }

        return array_unique($statements);
    }

    public function getPageUseStatements(string $page): array
    {
        $statements = [];

        if (isset($this->useStatements['pages'][$page])) {
            $statements = array_merge($statements, $this->useStatements['pages'][$page]);
        }

        return array_unique($statements);
    }

    protected function setFillable(bool $fillable): self
    {
        $this->fillable = $fillable;

        return $this;
    }

    protected function addMigration(string $field): self
    {
        $this->migrations['fields'][] = $field;

        return $this;
    }

    protected function addUseStatement(string $context, string $statement, ?string $subContext = null): self
    {
        if ($subContext) {
            $this->useStatements[$context][$subContext][] = $statement;
        } else {
            $this->useStatements[$context][] = $statement;
        }

        return $this;
    }

    protected function addTrait(string $context, string $trait): self
    {
        $this->traits[$context][] = $trait;

        return $this;
    }

    protected function addMethod(string $context, string $method, string $type = ''): self
    {
        if ($type) {
            $this->methods[$context][$type][] = $method;
        } else {
            $this->methods[$context][] = $method;
        }

        return $this;
    }

    protected function addCast(string $cast): self
    {
        if (! isset($this->casts['model'])) {
            $this->casts['model'] = [];
        }
        if (! is_array($this->casts['model'])) {
            $this->casts['model'] = [$this->casts['model']];
        }
        $this->casts['model'][] = $cast;

        return $this;
    }

    protected function setCast(string $cast): self
    {
        $this->casts['model'] = $cast;

        return $this;
    }

    protected function addFormField(string $field): self
    {
        $this->formFields['resource'][] = $field;

        return $this;
    }

    protected function addTableColumn(string $column): self
    {
        $this->tableColumns['resource'][] = $column;

        return $this;
    }

    public function setContext(object $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function getFormSetup(): string
    {
        return '';
    }

    public function getTableSetup(): string
    {
        return '';
    }

    public function getDefaultSortColumn(): string
    {
        return '';
    }

    public function getDefaultSortDirection(): string
    {
        return '';
    }

    public function getTableBulkActions(): array
    {
        return [];
    }
}
