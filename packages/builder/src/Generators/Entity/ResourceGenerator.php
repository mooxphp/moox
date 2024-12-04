<?php

declare(strict_types=1);

namespace Moox\Builder\Generators\Entity;

use Illuminate\Support\Str;
use Moox\Builder\Contexts\BuildContext;
use Moox\Builder\Generators\Entity\Pages\CreatePageGenerator;
use Moox\Builder\Generators\Entity\Pages\EditPageGenerator;
use Moox\Builder\Generators\Entity\Pages\ListPageGenerator;
use Moox\Builder\Generators\Entity\Pages\ViewPageGenerator;
use Moox\Builder\Services\Entity\SectionManager;
use Moox\Builder\Services\File\FileManager;
use RuntimeException;

class ResourceGenerator extends AbstractGenerator
{
    private SectionManager $sectionManager;

    public function __construct(
        BuildContext $context,
        FileManager $fileManager,
        array $blocks = []
    ) {
        parent::__construct($context, $fileManager, $blocks);
        $this->sectionManager = new SectionManager;
    }

    public function generate(): void
    {
        $this->processBlocks();
        $sections = $this->getSections();

        $this->generateResourcePages();

        $template = $this->loadStub($this->getTemplate());
        if (! $template) {
            throw new RuntimeException('Failed to load template: '.$this->getTemplate());
        }

        $formSchema = $this->generateFormSchema();
        $variables = [
            'namespace' => $this->context->formatNamespace('resource', false),
            'class_name' => $this->context->getEntityName(),
            'model' => $this->getModelReference(),
            'model_plural' => $this->context->getPluralName(),
            'Package' => match ($this->context->getContextType()) {
                'preview' => 'previews',
                'package' => explode('\\', $this->context->getBaseNamespace())[0],
                default => 'app'
            },
            'LowercaseEntity' => Str::kebab($this->context->getEntityName()),
            'navigation_icon' => $this->getNavigationIcon(),
            'use_statements' => $this->formatUseStatements(),
            'traits' => $this->formatTraits(),
            'form_schema' => $formSchema['form_schema'],
            'form_sections' => $formSchema['form_sections'],
            'meta_schema' => $formSchema['meta_schema'],
            'meta_sections' => $formSchema['meta_sections'],
            'table_columns' => $this->getTableColumns(),
            'table_filters' => $this->getTableFilters(),
            'table_actions' => $this->getTableActions(),
            'table_bulk_actions' => $this->getTableBulkActions(),
            'default_sort_column' => $this->getDefaultSortColumn(),
            'default_sort_direction' => $this->getDefaultSortDirection(),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $path = $this->context->getPath('resource').'/'.
            $this->context->getEntityName().'Resource.php';

        $this->writeFile($path, $content);
    }

    protected function processBlocks(): void
    {
        foreach ($this->getBlocks() as $block) {
            $this->sectionManager->addBlock($block);
        }
    }

    protected function getSections(): array
    {
        return $this->sectionManager->getFormattedSections();
    }

    protected function generateResourcePages(): void
    {
        $pageGenerators = [
            ListPageGenerator::class,
            CreatePageGenerator::class,
            EditPageGenerator::class,
            ViewPageGenerator::class,
        ];

        foreach ($pageGenerators as $generatorClass) {
            $generator = new $generatorClass(
                $this->context,
                $this->fileManager,
                $this->getBlocks()
            );
            $generator->generate();
        }
    }

    protected function getNavigationIcon(): string
    {
        return config('builder.generators.resource.navigation_icon', 'heroicon-o-rectangle-stack');
    }

    protected function getGeneratorType(): string
    {
        return 'resource';
    }

    protected function formatUseStatements(): string
    {
        $statements = [
            'use Filament\Forms\Form;',
            'use Filament\Forms\Components\Grid;',
            'use Filament\Forms\Components\Section;',
            'use Filament\Resources\Resource;',
            'use Filament\Tables\Table;',
            'use Illuminate\Database\Eloquent\Builder;',
        ];

        $resourcePagesNamespace = 'use '.$this->context->formatNamespace('resource', false).'\\'.$this->context->getEntityName().'Resource\\Pages;';
        $statements[] = $resourcePagesNamespace;

        foreach ($this->getBlocks() as $block) {
            if ($resourceTraits = $block->getTraits('resource')) {
                foreach ($resourceTraits as $trait) {
                    $statements[] = 'use '.$trait.';';
                }
            }

            if ($resourceStatements = $block->getUseStatements('resource')) {
                foreach ($resourceStatements as $context => $contextStatements) {
                    if ($context === 'pages') {
                        continue;
                    }
                    if (is_array($contextStatements)) {
                        foreach ($contextStatements as $statement) {
                            if ($statement !== $resourcePagesNamespace) {
                                $statements[] = $statement;
                            }
                        }
                    } else {
                        if ($contextStatements !== $resourcePagesNamespace) {
                            $statements[] = $contextStatements;
                        }
                    }
                }
            }
        }

        $statements = array_unique($statements);
        sort($statements);

        return implode("\n", array_map(function ($statement) {
            return rtrim($statement, ';').';';
        }, $statements));
    }

    protected function formatTraits(): string
    {
        $traits = [];

        foreach ($this->getBlocks() as $block) {
            if ($resourceTraits = $block->getTraits('resource')) {
                foreach ($resourceTraits as $trait) {
                    $parts = explode('\\', $trait);
                    $traits[] = end($parts);
                }
            }
        }

        if (empty($traits)) {
            return '';
        }

        return 'use '.implode(', ', array_unique($traits)).';';
    }

    protected function generateFormSchema(): array
    {
        $mainFields = [];
        $mainSections = [];
        $metaFields = [];
        $metaSections = [];
        $hasTaxonomy = false;

        foreach ($this->getBlocks() as $block) {
            foreach ($block->getSections() as $section) {
                if (str_contains($section['name'], '_actions') && $section['name'] !== 'resource_actions') {
                    continue;
                }

                if ($section['name'] === 'taxonomy') {
                    $hasTaxonomy = true;

                    continue;
                }

                if ($section['isMeta']) {
                    if ($section['name'] === 'meta') {
                        $metaFields = array_merge($metaFields, $section['fields']);
                    } else {
                        $metaSections[] = $section;
                    }
                } else {
                    if ($section['name'] === 'form') {
                        $mainFields = array_merge($mainFields, $section['fields']);
                    } else {
                        $mainSections[] = $section;
                    }
                }
            }
        }

        if ($hasTaxonomy) {
            $metaSections[] = [
                'name' => 'taxonomy',
                'isMeta' => true,
                'fields' => ['static::getTaxonomyFields()'],
                'order' => 20,
                'hideHeader' => true,
            ];
        }

        return [
            'form_schema' => implode(",\n", $mainFields),
            'form_sections' => $this->sectionManager->formatSections($mainSections),
            'meta_schema' => implode(",\n", $metaFields),
            'meta_sections' => $this->sectionManager->formatSections($metaSections),
        ];
    }

    protected function getTableColumns(): string
    {
        $columns = [];
        foreach ($this->getBlocks() as $block) {
            $tableColumns = $block->getTableColumns();
            if (! empty($tableColumns)) {
                $columns = array_merge($columns, $tableColumns);
            }
        }

        return implode(",\n            ", $columns);
    }

    protected function getTableActions(): string
    {
        $actions = [];
        foreach ($this->getBlocks() as $block) {
            $blockActions = $block->getTableActions();
            if (! empty($blockActions)) {
                $actions = array_merge($actions, $blockActions);
            }
        }

        return implode(",\n            ", $actions);
    }

    protected function getTableFilters(): string
    {
        $filters = [];
        foreach ($this->getBlocks() as $block) {
            $blockFilters = $block->getTableFilters();
            if (! empty($blockFilters)) {
                $filters = array_merge($filters, $blockFilters);
            }
        }

        return implode(",\n            ", $filters);
    }

    protected function getTableBulkActions(): string
    {
        $actions = [];
        foreach ($this->getBlocks() as $block) {
            if (method_exists($block, 'getTableBulkActions')) {
                $blockActions = $block->getTableBulkActions();
                if (! empty($blockActions)) {
                    $actions = array_merge($actions, $blockActions);
                }
            }
        }

        return implode(",\n            ", $actions);
    }

    protected function getNavigationGroup(): string
    {
        return match ($this->context->getContextType()) {
            'preview' => 'Previews',
            'package' => $this->context->getEntityName(),
            default => 'Content'
        };
    }

    protected function getModelReference(): string
    {
        return $this->context->formatNamespace('model', true).'\\'.$this->context->getEntityName();
    }

    protected function getUseStatements(string $context, ?string $subContext = null): array
    {
        $statements = [];
        foreach ($this->getBlocks() as $block) {
            if (isset($block->useStatements[$context])) {
                if ($subContext && isset($block->useStatements[$context][$subContext])) {
                    $statements = array_merge($statements, $block->useStatements[$context][$subContext]);
                } elseif (! $subContext) {
                    foreach ($block->useStatements[$context] as $key => $value) {
                        if (is_array($value)) {
                            $statements = array_merge($statements, $value);
                        } else {
                            $statements[] = $value;
                        }
                    }
                }
            }
        }

        return array_unique($statements);
    }

    protected function getDefaultSortColumn(): string
    {
        $sortableFields = ['order', 'sort', 'sorting', 'name', 'title', 'slug', 'id'];

        foreach ($sortableFields as $field) {
            foreach ($this->getBlocks() as $block) {
                if ($block->getName() === $field && str_contains($block->tableColumn(), '->sortable()')) {
                    return $field;
                }
            }
        }

        return 'id';
    }

    protected function getDefaultSortDirection(): string
    {
        return 'desc';
    }
}
