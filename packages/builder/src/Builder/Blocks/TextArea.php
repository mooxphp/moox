<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Blocks;

class TextArea extends AbstractBlock
{
    protected array $useStatements = [
        'resource' => [
            'forms' => ['use Filament\Forms\Components\Textarea;'],
            'columns' => ['use Filament\Tables\Columns\TextColumn;'],
            'filters' => ['use Filament\Tables\Filters\TextFilter;'],
        ],
    ];

    protected function getMigrationType(): string
    {
        return 'text';
    }

    public function formField(): string
    {
        $field = "Textarea::make('{$this->name}')";

        return $this->applyCommonFormFieldAttributes($field);
    }

    public function tableColumn(): string
    {
        return "TextColumn::make('{$this->name}')->searchable()->sortable()->limit(50)";
    }

    public function tableFilter(): string
    {
        return "TextFilter::make('{$this->name}')";
    }
}
