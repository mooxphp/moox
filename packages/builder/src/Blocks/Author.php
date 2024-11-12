<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks;

class Author extends AbstractBlock
{
    public function __construct(
        string $name = 'author',
        string $label = 'Author',
        string $description = 'Author management',
    ) {
        parent::__construct($name, $label, $description);

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
        ];

        $this->traits['model'] = ['HasAuthor'];

        $this->formFields['resource'] = [
            "Select::make('author_id')
                ->relationship('author', 'name')
                ->searchable()
                ->preload()
                ->required()",
        ];

        $this->tableColumns['resource'] = [
            "TextColumn::make('author.name')
                ->label(__('core::core.author'))
                ->sortable()
                ->searchable()
                ->toggleable()",
        ];

        $this->migrations['fields'] = [
            '$table->foreignId("author_id")->constrained()->cascadeOnDelete()',
        ];
    }
}
