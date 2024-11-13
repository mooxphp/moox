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

    /** @var array<class-string> */
    protected array $requiredBlocks = [];

    /** @var array<class-string> */
    protected array $containsBlocks = [];

    /** @var array<class-string> */
    protected array $incompatibleBlocks = [];

    public function __construct(
        protected string $name,
        protected string $label,
        protected string $description,
        protected bool $nullable = false,
        // Additional parameters will be handled by child classes
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->description = $description;
        $this->nullable = $nullable;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function isFillable(): bool
    {
        return $this->fillable;
    }

    public function getCasts(string $type): array
    {
        return $this->casts[$type] ?? [];
    }

    public function getMigrations(string $type = 'fields'): array
    {
        return $this->migrations[$type] ?? [];
    }

    public function getUseStatements(string $path, ?string $subPath = null): array
    {
        $parts = explode('.', $path);
        if ($subPath) {
            $parts[] = $subPath;
        }

        $current = $this->useStatements;
        foreach ($parts as $part) {
            if (! isset($current[$part])) {
                return [];
            }
            $current = $current[$part];
        }

        if (is_array($current)) {
            return $this->flattenArray($current);
        }

        return is_string($current) ? [$current] : [];
    }

    public function getFlattenedUseStatements(string $path): array
    {
        $statements = $this->getUseStatements($path);

        return $this->flattenArray($statements);
    }

    public function getTraits(string $path): array
    {
        $parts = explode('.', $path);
        $current = $this->traits;

        foreach ($parts as $part) {
            if (! isset($current[$part])) {
                return [];
            }
            $current = $current[$part];
        }

        return $current;
    }

    public function getMethods(string $context, ?string $type = null): array
    {
        if ($type) {
            return $this->methods[$context][$type] ?? [];
        }

        return $this->methods[$context] ?? [];
    }

    public function getFormFields(): array
    {
        return $this->formFields['resource'] ?? [];
    }

    public function getTableColumns(): array
    {
        return $this->tableColumns['resource'] ?? [];
    }

    public function getFactories(string $type = 'definitions'): array
    {
        return $this->factories['model'][$type] ?? [];
    }

    public function getTests(string $type, string $context): array
    {
        return $this->tests[$type][$context] ?? [];
    }

    public function getFilters(): array
    {
        return $this->filters['resource'] ?? [];
    }

    public function getRequiredBlocks(): array
    {
        return $this->requiredBlocks;
    }

    public function getContainsBlocks(): array
    {
        return $this->containsBlocks;
    }

    public function getIncompatibleBlocks(): array
    {
        return $this->incompatibleBlocks;
    }

    protected function setFillable(bool $fillable): self
    {
        $this->fillable = $fillable;

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

    protected function addMigration(string $field, string $type = 'fields'): self
    {
        $this->migrations[$type][] = $field;

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

    protected function addFactory(string $definition, string $type = 'definitions'): self
    {
        $this->factories['model'][$type][] = $definition;

        return $this;
    }

    protected function addTest(string $type, string $context, string $test): self
    {
        $this->tests[$type][$context][] = $test;

        return $this;
    }

    protected function addFilter(string $filter): self
    {
        $this->filters['resource'][] = $filter;

        return $this;
    }

    protected function addAction(string $context, string $action): self
    {
        $this->actions[$context][] = $action;

        return $this;
    }

    protected function addPageAction(string $page, string $action, string $position = ''): self
    {
        if ($position) {
            $this->actions['pages'][$page][$position][] = $action;
        } else {
            $this->actions['pages'][$page][] = $action;
        }

        return $this;
    }

    public function formField(): string
    {
        return implode(",\n            ", $this->formFields['resource'] ?? []);
    }

    public function tableColumn(): string
    {
        return implode(",\n            ", $this->tableColumns['resource'] ?? []);
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

    public function getResourceUseStatements(): array
    {
        $baseStatements = [
            'use Filament\Forms\Form;',
            'use Filament\Resources\Resource;',
            'use Filament\Tables\Table;',
            'use Illuminate\Database\Eloquent\Builder;',
        ];

        $statements = array_merge(
            $baseStatements,
            $this->getFlattenedUseStatements('model'),
            $this->getFlattenedUseStatements('resource'),
            $this->getFlattenedUseStatements('pages')
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
        return array_unique($this->useStatements['pages'][$page] ?? []);
    }

    public function resolveBlockDependencies(array $blocks): array
    {
        $resolvedBlocks = $blocks;

        foreach ($blocks as $block) {
            if (isset($block->containsBlocks)) {
                foreach ($block->containsBlocks as $includedBlock) {
                    $resolvedBlocks = array_filter(
                        $resolvedBlocks,
                        fn ($b) => ! ($b instanceof $includedBlock)
                    );
                }
            }
        }

        return $resolvedBlocks;
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

    public function getTitle(): string
    {
        return class_basename($this);
    }

    public function getOptions(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'description' => $this->description,
            'nullable' => $this->nullable,
            'requiredBlocks' => $this->requiredBlocks,
            'containsBlocks' => $this->containsBlocks,
            'incompatibleBlocks' => $this->incompatibleBlocks,
        ];
    }

    protected function flattenArray(array $array): array
    {
        $result = [];
        array_walk_recursive($array, function ($value) use (&$result) {
            if (is_string($value)) {
                $result[] = $value;
            }
        });

        return $result;
    }

    public function getTableActions(): array
    {
        return $this->getActions('resource');
    }

    public function getPageActions(string $page): array
    {
        return $this->getActions("pages.{$page}");
    }

    public function getHeaderActions(string $page): array
    {
        return $this->getActions("pages.{$page}.header");
    }

    public function getFooterActions(string $page): array
    {
        return $this->getActions("pages.{$page}.footer");
    }

    public function getActions(string $path): array
    {
        $parts = explode('.', $path);
        $current = $this->actions;

        foreach ($parts as $part) {
            if (! isset($current[$part])) {
                return [];
            }
            $current = $current[$part];
        }

        return is_array($current) ? $current : [$current];
    }

    public function getTableFilters(): array
    {
        return $this->getFilters();
    }

    public function getTableHeaderActions(): array
    {
        return $this->getActions('header');
    }

    public function getTableEmptyStateActions(): array
    {
        return $this->getActions('empty_state');
    }

    public function getTableRecordActions(): array
    {
        return $this->getActions('record');
    }

    public function getTableRecordCheckboxes(): array
    {
        return $this->getActions('checkboxes');
    }

    public function getTableRecordUrl(): ?string
    {
        return null;
    }

    public function getTableReorderColumn(): ?string
    {
        return null;
    }

    public function getTablePollInterval(): ?string
    {
        return null;
    }

    public function getTableQueryString(): array
    {
        return [];
    }

    public function getTableRecordClasses(): ?string
    {
        return null;
    }

    public function setFeatureFlags(array $data): void
    {
        $this->requiredBlocks = $data['requiredBlocks'] ?? [];
        $this->containsBlocks = $data['containsBlocks'] ?? [];
        $this->incompatibleBlocks = $data['incompatibleBlocks'] ?? [];
    }

    public function setArrayData(array $data): void
    {
        $this->casts = ['model' => $data['casts'] ?? []];
        $this->migrations = [
            'fields' => $data['migrations'] ?? [],
            'indexes' => [],
            'foreign_keys' => [],
        ];
        $this->formFields = ['resource' => $data['formFields'] ?? []];
        $this->tableColumns = ['resource' => $data['tableColumns'] ?? []];
        $this->factories = [
            'model' => [
                'definitions' => $data['factories'] ?? [],
                'states' => [],
            ],
        ];
        $this->tests = [
            'unit' => [
                'model' => $data['tests'] ?? [],
                'resource' => [],
            ],
            'feature' => [
                'model' => [],
                'resource' => [],
            ],
        ];
        $this->filters = ['resource' => $data['filters'] ?? []];

        foreach (['useStatements', 'traits', 'methods', 'actions'] as $property) {
            if (isset($data[$property])) {
                $this->$property = array_merge_recursive(
                    $this->$property,
                    ['model' => $data[$property] ?? []]
                );
            }
        }
    }
}
