<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Fields;

use Moox\Builder\Blocks\AbstractBlock;

class Text extends AbstractBlock
{
    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        protected int $length = 255,
        protected bool $unique = false,
        protected bool $searchable = false,
        protected bool $sortable = false,
        protected bool $toggleable = false,
        protected bool $filterable = false,
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $this->useStatements['resource'] = [
            'forms' => ['use Filament\Forms\Components\TextInput;'],
            'columns' => ['use Filament\Tables\Columns\TextColumn;'],
            'filters' => [
                'use Filament\Tables\Filters\Filter;',
                'use Illuminate\Database\Eloquent\Builder;',
            ],
        ];

        $this->addSection('form')
            ->withFields([
                "TextInput::make('{$this->name}')
                    ->label('{$this->label}')
                    ->maxLength({$this->length})"
                .($this->nullable ? '->nullable()' : '->required()'),
            ]);

        $this->tableColumns['resource'] = [
            "TextColumn::make('{$this->name}')"
                .($this->sortable ? '->sortable()' : '')
                .($this->searchable ? '->searchable()' : '')
                .($this->toggleable ? '->toggleable()' : ''),
        ];

        $this->migrations['fields'] = [
            "\$table->string('{$this->name}', {$this->length})"
                .($this->nullable ? '->nullable()' : ''),
        ];

        if ($this->unique) {
            $this->migrations['indexes'][] = "\$table->unique('{$this->name}')";
        }

        $this->factories['model']['definitions'] = [
            "{$this->name}" => "fake()->text({$this->length})",
        ];

        $this->filters['resource'] = [
            "Filter::make('{$this->name}')
                ->form([
                    TextInput::make('{$this->name}')
                        ->label('{$this->label}')
                        ->placeholder(__('core::core.search')),
                ])
                ->query(function (Builder \$query, array \$data): Builder {
                    return \$query->when(
                        \$data['{$this->name}'],
                        fn (Builder \$query, \$value): Builder => \$query->where('{$this->name}', 'like', \"%{\$value}%\"),
                    );
                })
                ->indicateUsing(function (array \$data): ?string {
                    if (! \$data['{$this->name}']) {
                        return null;
                    }

                    return '{$this->label}: '.\$data['{$this->name}'];
                })",
        ];
    }
}
