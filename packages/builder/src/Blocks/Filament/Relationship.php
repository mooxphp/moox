<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Filament;

use Moox\Builder\Blocks\AbstractBlock;

class Relationship extends AbstractBlock
{
    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        protected string $relatedModel = '',
        protected string $displayColumn = 'name',
        protected string $relationshipType = 'belongsTo',
        protected bool $multiple = false,
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $this->useStatements = [
            'resource' => [
                'forms' => ['use Filament\Forms\Components\\'.($multiple ? 'MultiSelect' : 'Select').';'],
                'columns' => ['use Filament\Tables\Columns\TextColumn;'],
                'filters' => ['use Filament\Tables\Filters\\'.($multiple ? 'MultiSelectFilter' : 'SelectFilter').';'],
            ],
        ];

        $this->formFields['resource'] = [
            ($multiple ? 'MultiSelect' : 'Select')."::make('{$this->name}')
                ->label('{$this->label}')
                ->relationship('{$name}', '{$this->displayColumn}')
                ->searchable()
                ->preload()"
                .($this->nullable ? '' : '->required()'),
        ];

        $this->tableColumns['resource'] = [
            "TextColumn::make('{$this->name}.{$this->displayColumn}')
                ->sortable()",
        ];

        $this->filters['resource'] = [
            ($multiple ? 'MultiSelectFilter' : 'SelectFilter')."::make('{$this->name}')
                ->relationship('{$name}', '{$this->displayColumn}')",
        ];

        $this->migrations['fields'] = $this->relationshipType === 'belongsTo'
            ? ["\$table->foreignId('{$this->name}_id')->constrained()".($this->nullable ? '->nullable()' : '')]
            : [];

        $this->methods['model']['relations'] = [
            "public function {$name}()
            {
                return \$this->{$this->relationshipType}({$this->relatedModel}::class);
            }",
        ];
    }
}
