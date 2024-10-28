<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Features;

class Author extends AbstractFeature
{
    protected function initializeFeature(): void
    {
        $this->useStatements = [
            'resource' => [
                'forms' => [
                    'use Filament\Forms\Components\Select;',
                ],
                'columns' => [
                    'use Filament\Tables\Columns\TextColumn;',
                ],
            ],
            'model' => [
                'use Moox\Core\Traits\HasAuthor;',
            ],
            'migration' => [],
            'pages' => [
                'create' => [],
                'edit' => [],
                'list' => [],
                'view' => [],
            ],
        ];

        $this->traits = [
            'resource' => [],
            'model' => ['HasAuthor'],
        ];

        $this->methods = [
            'resource' => [],
            'model' => [],
            'pages' => [
                'create' => [],
                'edit' => [],
                'list' => [],
                'view' => [],
            ],
        ];
    }

    public function getFormFields(): array
    {
        return [
            "Select::make('author_id')
                ->relationship('author', 'name')
                ->searchable()
                ->preload()
                ->required()",
        ];
    }

    public function getTableColumns(): array
    {
        return [
            "TextColumn::make('author.name')
                ->label(__('core::core.author'))
                ->sortable()
                ->searchable()
                ->toggleable()",
        ];
    }

    public function getTableFilters(): array
    {
        return [];
    }

    public function getActions(): array
    {
        return [];
    }

    public function getMigrations(): array
    {
        return [
            '$table->foreignId("author_id")->constrained()->cascadeOnDelete()',
        ];
    }
}
