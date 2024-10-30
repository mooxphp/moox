<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Blocks;

class TitleWithSlug extends AbstractBlock
{
    protected string $titleFieldName;

    protected string $slugFieldName;

    protected array $useStatements = [
        'resource' => [
            'forms' => ['use Camya\Filament\Forms\Components\TitleWithSlugInput;'],
            'columns' => ['use Filament\Tables\Columns\TextColumn;'],
            'filters' => ['use Filament\Tables\Filters\TextFilter;'],
        ],
    ];

    public function __construct(
        string $titleFieldName,
        string $slugFieldName,
        string $label,
        string $description,
        bool $nullable = false
    ) {
        parent::__construct($titleFieldName, $label, $description);
        $this->titleFieldName = $titleFieldName;
        $this->slugFieldName = $slugFieldName;
        $this->setNullable($nullable);
    }

    public function getMigrationType(): string
    {
        return 'string';
    }

    public function formField(): string
    {
        $field = "TitleWithSlugInput::make(
            fieldTitle: '{$this->titleFieldName}',
            fieldSlug: '{$this->slugFieldName}'
        )";

        return $this->applyCommonFormFieldAttributes($field);
    }

    public function tableColumn(): string
    {
        return "TextColumn::make('{$this->titleFieldName}')->searchable()->sortable(),
                TextColumn::make('{$this->slugFieldName}')->searchable()->sortable()";
    }

    public function tableFilter(): string
    {
        return "TextFilter::make('{$this->titleFieldName}'),
                TextFilter::make('{$this->slugFieldName}')";
    }

    public function migration(): array
    {
        return [
            '$table->string(\''.$this->titleFieldName.'\');',
            '$table->string(\''.$this->slugFieldName.'\')->unique();',
        ];
    }

    public function modelAttribute(): string
    {
        return "'{$this->titleFieldName}', '{$this->slugFieldName}'";
    }

    public function modelCast(): string
    {
        return '';
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
