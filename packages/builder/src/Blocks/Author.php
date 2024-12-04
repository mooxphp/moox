<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks;

class Author extends AbstractBlock
{
    protected bool $searchable;

    protected bool $toggleable;

    protected bool $sortable;

    protected string $userModel;

    public function __construct(
        string $name = 'author',
        string $label = 'Author',
        string $description = 'Author management',
        bool $nullable = false,
        bool $searchable = true,
        bool $toggleable = true,
        bool $sortable = true,
        string $userModel = 'App\Models\User',
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $this->searchable = $searchable;
        $this->toggleable = $toggleable;
        $this->sortable = $sortable;
        $this->userModel = $userModel;

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
                ->relationship('author', 'name')"
                .($this->nullable ? '' : '->required()'),
        ];

        $this->tableColumns['resource'] = [
            "TextColumn::make('author.name')
                ->label(__('core::core.author'))"
                .($this->sortable ? '' : '->sortable()')
                .($this->searchable ? '' : '->searchable()')
                .($this->toggleable ? '' : '->toggleable()'),
        ];

        $this->migrations['fields'] = [
            '$table->foreignId("author_id")->constrained()->cascadeOnDelete()',
        ];
    }
}
