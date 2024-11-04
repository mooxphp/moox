<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks;

class TitleWithSlug extends AbstractBlock
{
    public function __construct(
        protected readonly string $titleFieldName = 'title',
        protected readonly string $slugFieldName = 'slug',
        string $label = 'Title',
        string $description = 'The title of the item',
        bool $nullable = false,
    ) {
        parent::__construct($titleFieldName, $label, $description, $nullable);

        $this->useStatements = [
            'resource' => [
                'forms' => [
                    'use Camya\Filament\Forms\Components\TitleWithSlugInput;',
                    'use Filament\Forms\Components\Hidden;',
                ],
                'columns' => ['use Filament\Tables\Columns\TextColumn;'],
                'filters' => [
                    'use Filament\Tables\Filters\Filter;',
                    'use Filament\Forms\Components\TextInput;',
                    'use Illuminate\Database\Eloquent\Builder;',
                ],
            ],
        ];

        $this->formFields['resource'] = [
            "TextInput::make('{$this->titleFieldName}')
                ->required()
                ->maxLength(255)",
            "Hidden::make('{$this->slugFieldName}')",
        ];

        $this->tableColumns['resource'] = [
            "TextColumn::make('{$this->titleFieldName}')
                ->searchable()
                ->sortable()",
            "TextColumn::make('{$this->slugFieldName}')
                ->searchable()
                ->sortable()",
        ];

        $this->filters['resource'] = [
            "Filter::make('{$this->slugFieldName}')
                ->form([
                    TextInput::make('{$this->slugFieldName}')
                        ->label(__('core::core.slug'))
                        ->placeholder(__('core::core.search_by_slug')),
                ])
                ->query(function (Builder \$query, array \$data): Builder {
                    return \$query->when(
                        \$data['{$this->slugFieldName}'],
                        fn (Builder \$query, \$slug): Builder => \$query->where('{$this->slugFieldName}', 'like', \"%{\$slug}%\"),
                    );
                })",
        ];

        $this->migrations['fields'] = [
            "\$table->string('{$this->titleFieldName}')",
            "\$table->string('{$this->slugFieldName}')->unique()",
        ];

        $this->factories['model']['definitions'] = [
            "{$this->titleFieldName}" => 'fake()->sentence()',
            "{$this->slugFieldName}" => 'str()->slug(fake()->sentence())',
        ];

        $this->tests['unit']['model'] = [
            'test_slug_is_generated_from_title' => "
                \$model = Model::factory()->create([
                    '{$this->titleFieldName}' => 'Test Title',
                ]);

                \$this->assertEquals('test-title', \$model->{$this->slugFieldName});
            ",
        ];

        $this->tests['feature']['resource'] = [
            'test_slug_is_unique' => "
                \$existingModel = Model::factory()->create([
                    '{$this->titleFieldName}' => 'Test Title',
                ]);

                \$response = \$this->post(route('resource.store'), [
                    '{$this->titleFieldName}' => 'Test Title',
                ]);

                \$response->assertSessionHasErrors('{$this->slugFieldName}');
            ",
        ];

        $this->addCast("'{$this->slugFieldName}' => 'string'")
            ->addCast("'{$this->titleFieldName}' => 'string'");
    }

    protected function hasMultipleFields(): bool
    {
        return true;
    }

    protected function getAdditionalFields(): array
    {
        return [$this->slugFieldName];
    }

    protected function getUniqueFields(): array
    {
        return [$this->slugFieldName];
    }

    protected function getRequiredFields(): array
    {
        return [$this->titleFieldName, $this->slugFieldName];
    }

    protected function getIndexedFields(): array
    {
        return [$this->titleFieldName, $this->slugFieldName];
    }
}
