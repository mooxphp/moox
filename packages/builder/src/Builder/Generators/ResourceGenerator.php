<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Generators;

use Moox\Builder\Builder\Features\SoftDelete;

class ResourceGenerator
{
    protected string $namespace;

    protected string $className;

    protected string $model;

    /** @var array<Feature> */
    protected array $features = [];

    protected array $useStatements = [];

    protected array $traits = [];

    protected array $methods = [];

    protected array $formFields = [];

    protected array $tableColumns = [];

    protected array $tableFilters = [];

    protected array $tableActions = [];

    protected string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected string $navigationIcon = 'heroicon-o-rectangle-stack';

    public function __construct(
        string $namespace,
        string $className,
        string $model
    ) {
        // Allow custom namespace but default to App if none provided
        $this->namespace = $namespace ?: 'App\\Filament\\Resources';
        $this->className = $className;
        $this->model = $model;
        $this->navigationIcon = 'heroicon-o-rectangle-stack';
    }

    public function addFeature(Feature $feature): self
    {
        $this->features[] = $feature;

        return $this;
    }

    public function setNavigationIcon(string $icon): self
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function setDefaultSort(string $column, string $direction = 'desc'): self
    {
        $this->defaultSortColumn = $column;
        $this->defaultSortDirection = $direction;

        return $this;
    }

    protected function getFormFields(): array
    {
        $fields = $this->formFields;
        foreach ($this->features as $feature) {
            $fields = array_merge($fields, $feature->getFormFields());
        }

        return $fields;
    }

    protected function getTableColumns(): array
    {
        $columns = $this->tableColumns;
        foreach ($this->features as $feature) {
            $columns = array_merge($columns, $feature->getTableColumns());
        }

        return $columns;
    }

    protected function getTableFilters(): array
    {
        $filters = $this->tableFilters;
        foreach ($this->features as $feature) {
            $filters = array_merge($filters, $feature->getTableFilters());
        }

        return $filters;
    }

    protected function getTableActions(): array
    {
        $actions = $this->tableActions;
        foreach ($this->features as $feature) {
            $actions = array_merge($actions, $feature->getActions());
        }

        return $actions;
    }

    public function generate(): array
    {
        $files = [
            'resource' => $this->generateResource(),
            'pages' => [
                'create' => $this->generateCreatePage(),
                'edit' => $this->generateEditPage(),
                'list' => $this->generateListPage(),
                'view' => $this->generateViewPage(),
            ],
        ];

        $resourcePath = app_path("Filament/Resources/{$this->className}Resource.php");
        $pagesPath = app_path("Filament/Resources/{$this->className}Resource/Pages/");

        if (! file_exists(dirname($resourcePath))) {
            mkdir(dirname($resourcePath), 0755, true);
        }

        if (! file_exists($pagesPath)) {
            mkdir($pagesPath, 0755, true);
        }

        file_put_contents($resourcePath, $files['resource']);
        file_put_contents($pagesPath."Create{$this->className}.php", $files['pages']['create']);
        file_put_contents($pagesPath."Edit{$this->className}.php", $files['pages']['edit']);
        file_put_contents($pagesPath."List{$this->className}s.php", $files['pages']['list']);
        file_put_contents($pagesPath."View{$this->className}.php", $files['pages']['view']);

        return $files;
    }

    protected function generateResource(): string
    {
        $template = file_get_contents(__DIR__.'/../Templates/resource.php.stub');

        return str_replace(
            [
                '{{ namespace }}',
                '{{ class_name }}',
                '{{ model }}',
                '{{ navigation_icon }}',
                '{{ use_statements }}',
                '{{ traits }}',
                '{{ form_setup }}',
                '{{ form_schema }}',
                '{{ table_setup }}',
                '{{ table_columns }}',
                '{{ default_sort_column }}',
                '{{ default_sort_direction }}',
                '{{ table_actions }}',
                '{{ table_bulk_actions }}',
                '{{ table_filters }}',
                '{{ methods }}',
            ],
            [
                $this->namespace,
                $this->className,
                class_basename($this->model),  // Removed ::class
                $this->navigationIcon,
                implode("\n", $this->getUseStatements()),
                $this->getTraitsString(),
                $this->getFormSetup(),
                implode("\n                ", $this->getFormFields()),
                $this->getTableSetup(),
                implode("\n                ", $this->getTableColumns()),
                $this->defaultSortColumn,
                $this->defaultSortDirection,
                implode("\n                ", $this->getTableActions()),
                implode("\n                ", $this->getTableBulkActions()),
                implode("\n                ", $this->getTableFilters()),
                implode("\n\n    ", $this->getMethods()),
            ],
            $template
        );
    }

    protected function getUseStatements(): array
    {
        $statements = [
            'use Filament\Resources\Resource;',
            'use Filament\Forms\Form;',
            'use Filament\Tables\Table;',
            'use Filament\Tables\Actions\EditAction;',
            'use Filament\Tables\Actions\ViewAction;',
            'use Illuminate\Database\Eloquent\Builder;',
            str_replace(['App\Models\App\Models', '::class'], ['App\Models', ''], "use {$this->model};"),
        ];

        foreach ($this->features as $feature) {
            $statements = array_merge(
                $statements,
                $feature->getUseStatements('resource', 'forms'),
                $feature->getUseStatements('resource', 'columns'),
                $feature->getUseStatements('resource', 'filters'),
                $feature->getUseStatements('resource', 'actions')
            );
        }

        return array_values(array_unique($statements));
    }

    protected function getTraits(): array
    {
        $traits = [];
        foreach ($this->features as $feature) {
            $traits = array_merge($traits, $feature->getTraits('resource'));
        }

        return array_unique($traits);
    }

    // Add a new method to format traits for template
    protected function getTraitsString(): string
    {
        $traits = $this->getTraits();

        return empty($traits) ? '' : 'use '.implode(', ', $traits).';';
    }

    protected function getFormSetup(): string
    {
        return '';
    }

    protected function getTableSetup(): string
    {
        return '';
    }

    protected function getTableBulkActions(): array
    {
        $actions = [];

        if ($this->hasSoftDelete()) {
            $actions[] = 'DeleteBulkAction::make(),';
            $actions[] = 'RestoreBulkAction::make(),';
        }

        return $actions;
    }

    protected function getMethods(): array
    {
        $methods = [
            'public static function getPages(): array
            {
                return [
                    "index" => Pages\List'.$this->className.'s::route("/"),
                    "create" => Pages\Create'.$this->className.'::route("/create"),
                    "edit" => Pages\Edit'.$this->className.'::route("/{record}/edit"),
                    "view" => Pages\View'.$this->className.'::route("/{record}"),
                ];
            }',
        ];

        foreach ($this->features as $feature) {
            $methods = array_merge($methods, $feature->getMethods('resource'));
        }

        return array_unique($methods);
    }

    protected function hasSoftDelete(): bool
    {
        foreach ($this->features as $feature) {
            if ($feature instanceof SoftDelete) {
                return true;
            }
        }

        return false;
    }

    protected function generateCreatePage(): string
    {
        $template = file_get_contents(__DIR__.'/../Templates/pages/create.php.stub');
        $namespace = $this->namespace.'\\'.$this->className.'Resource\\Pages';

        return str_replace(
            [
                '{{ namespace }}',
                '{{ model }}',
                '{{ resource }}',
                '{{ use_statements }}',
                '{{ traits }}',
                '{{ methods }}',
            ],
            [
                $namespace,
                $this->className,
                $this->className.'Resource',
                $this->getPageUseStatements('create'),
                $this->getPageTraits('create'),
                $this->getPageMethods('create'),
            ],
            $template
        );
    }

    protected function generateEditPage(): string
    {
        $template = file_get_contents(__DIR__.'/../Templates/pages/edit.php.stub');
        $namespace = $this->namespace.'\\'.$this->className.'Resource\\Pages';

        return str_replace(
            [
                '{{ namespace }}',
                '{{ model }}',
                '{{ resource }}',
                '{{ use_statements }}',
                '{{ traits }}',
                '{{ methods }}',
            ],
            [
                $namespace,
                $this->className,
                $this->className.'Resource',
                $this->getPageUseStatements('edit'),
                $this->getPageTraits('edit'),
                $this->getPageMethods('edit'),
            ],
            $template
        );
    }

    protected function generateListPage(): string
    {
        $template = file_get_contents(__DIR__.'/../Templates/pages/list.php.stub');
        $namespace = $this->namespace.'\\'.$this->className.'Resource\\Pages';

        return str_replace(
            [
                '{{ namespace }}',
                '{{ model }}',
                '{{ model_plural }}',
                '{{ resource }}',
                '{{ use_statements }}',
                '{{ traits }}',
                '{{ methods }}',
            ],
            [
                $namespace,
                $this->className,
                $this->className.'s',
                $this->className.'Resource',
                $this->getPageUseStatements('list'),
                $this->getPageTraits('list'),
                $this->getPageMethods('list'),
            ],
            $template
        );
    }

    protected function generateViewPage(): string
    {
        $template = file_get_contents(__DIR__.'/../Templates/pages/view.php.stub');
        $namespace = $this->namespace.'\\'.$this->className.'Resource\\Pages';

        return str_replace(
            [
                '{{ namespace }}',
                '{{ model }}',
                '{{ resource }}',
                '{{ use_statements }}',
                '{{ traits }}',
                '{{ methods }}',
            ],
            [
                $namespace,
                $this->className,
                $this->className.'Resource',
                $this->getPageUseStatements('view'),
                $this->getPageTraits('view'),
                $this->getPageMethods('view'),
            ],
            $template
        );
    }

    protected function getPageUseStatements(string $page): string
    {
        $statements = [];
        foreach ($this->features as $feature) {
            $statements = array_merge($statements, $feature->getUseStatements('pages', $page));
        }

        return implode("\n", array_unique($statements));
    }

    protected function getPageTraits(string $page): string
    {
        $traits = [];
        foreach ($this->features as $feature) {
            $traits = array_merge($traits, $feature->getTraits('pages', $page));
        }

        return empty($traits) ? '' : 'use '.implode(', ', array_unique($traits)).';';
    }

    protected function getPageMethods(string $page): string
    {
        $methods = [];
        foreach ($this->features as $feature) {
            $methods = array_merge($methods, $feature->getMethods('pages', $page));
        }

        return implode("\n\n    ", array_unique($methods));
    }
}
