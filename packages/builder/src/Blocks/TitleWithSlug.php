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

        $this->addSection('form')
            ->withFields([
                "TitleWithSlugInput::make(
                    fieldTitle: '{$this->titleFieldName}',
                    fieldSlug: '{$this->slugFieldName}',
                )",
            ]);

        $this->tableColumns['resource'] = [
            "TextColumn::make('{$this->titleFieldName}')
                ->searchable()
                ->sortable()",
            "TextColumn::make('{$this->slugFieldName}')
                ->searchable()
                ->sortable()",
        ];

        $this->filters['resource'] = [
            "Filter::make('{$this->titleFieldName}')
                ->form([
                    TextInput::make('{$this->titleFieldName}')
                        ->label('{$this->label}')
                        ->placeholder(__('core::core.filter').' {$this->label}'),
                ])
                ->query(function (Builder \$query, array \$data): Builder {
                    return \$query->when(
                        \$data['{$this->titleFieldName}'],
                        fn (Builder \$query, \$value): Builder => \$query->where('{$this->titleFieldName}', 'like', \"%{\$value}%\"),
                    );
                })
                ->indicateUsing(function (array \$data): ?string {
                    if (! \$data['{$this->titleFieldName}']) {
                        return null;
                    }

                    return '{$this->label}: '.\$data['{$this->titleFieldName}'];
                })",
            "Filter::make('{$this->slugFieldName}')
                ->form([
                    TextInput::make('{$this->slugFieldName}')
                        ->label(__('core::core.slug'))
                        ->placeholder(__('core::core.filter').' {$this->label}'),
                ])
                ->query(function (Builder \$query, array \$data): Builder {
                    return \$query->when(
                        \$data['{$this->slugFieldName}'],
                        fn (Builder \$query, \$value): Builder => \$query->where('{$this->slugFieldName}', 'like', \"%{\$value}%\"),
                    );
                })
                ->indicateUsing(function (array \$data): ?string {
                    if (! \$data['{$this->slugFieldName}']) {
                        return null;
                    }

                    return __('core::core.slug').': '.\$data['{$this->slugFieldName}'];
                })",
        ];

        $this->migrations['fields'] = [
            "\$table->string('{$this->titleFieldName}')",
            "\$table->string('{$this->slugFieldName}')->unique()->nullable(false)",
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

    public function isFillable(): bool
    {
        return true;
    }

    public function getName(): string
    {
        return $this->titleFieldName;
    }

    public function getFillableFields(): array
    {
        return [$this->titleFieldName, $this->slugFieldName];
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

    public function getTitle(): string
    {
        return 'Title with Slug';
    }

    public function getDescription(): string
    {
        return 'Adds a title field with an automatically generated slug';
    }

    public function getOptions(): array
    {
        return [
            'titleFieldName' => $this->titleFieldName,
            'slugFieldName' => $this->slugFieldName,
            'label' => $this->label,
            'description' => $this->description,
            'nullable' => $this->nullable,
        ];
    }
}
