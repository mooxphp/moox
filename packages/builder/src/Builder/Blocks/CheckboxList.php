<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Blocks;

class CheckboxList extends AbstractBlock
{
    protected array $useStatements = [
        'resource' => [
            'forms' => ['use Filament\Forms\Components\CheckboxList;'],
            'columns' => ['use Filament\Tables\Columns\TextColumn;'],
            'filters' => ['use Filament\Tables\Filters\MultiSelectFilter;'],
        ],
    ];

    protected array $options;

    public function __construct(
        string $name,
        string $label,
        string $description,
        array $options,
        bool $nullable = false
    ) {
        parent::__construct($name, $label, $description);
        $this->setNullable($nullable);
        $this->options = $options;
    }

    public function getMigrationType(): string
    {
        return 'json';
    }

    public function formField(): string
    {
        $field = "CheckboxList::make('{$this->name}')";
        $field .= '->options('.var_export($this->options, true).')';

        return $this->applyCommonFormFieldAttributes($field);
    }

    public function tableColumn(): string
    {
        return "TextColumn::make('{$this->name}')->listWithLineBreaks()";
    }

    public function tableFilter(): string
    {
        return "MultiSelectFilter::make('{$this->name}')->options(".var_export($this->options, true).')';
    }

    public function modelCast(): string
    {
        return "'{$this->name}' => 'array'";
    }
}
