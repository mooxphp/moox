<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Blocks;

class ColorPicker extends AbstractBlock
{
    protected array $useStatements = [
        'resource' => [
            'forms' => ['use Filament\Forms\Components\ColorPicker;'],
            'columns' => ['use Filament\Tables\Columns\ColorColumn;'],
            'filters' => ['use Filament\Tables\Filters\TextFilter;'],
        ],
    ];

    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false
    ) {
        parent::__construct($name, $label, $description);
        $this->setNullable($nullable);
    }

    protected function getMigrationType(): string
    {
        return 'string';
    }

    public function formField(): string
    {
        $field = "ColorPicker::make('{$this->name}')";

        return $this->applyCommonFormFieldAttributes($field);
    }

    public function tableColumn(): string
    {
        return "ColorColumn::make('{$this->name}')";
    }

    public function tableFilter(): string
    {
        return "TextFilter::make('{$this->name}')";
    }
}
