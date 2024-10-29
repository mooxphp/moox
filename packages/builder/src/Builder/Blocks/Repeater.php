<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Blocks;

class Repeater extends AbstractBlock
{
    protected array $useStatements = [
        'resource' => [
            'forms' => ['use Filament\Forms\Components\Repeater;'],
            'columns' => ['use Filament\Tables\Columns\TextColumn;'],
            'filters' => ['use Filament\Tables\Filters\Filter;'],
        ],
    ];

    protected array $schema;

    public function __construct(
        string $name,
        string $label,
        string $description,
        array $schema,
        bool $nullable = false
    ) {
        parent::__construct($name, $label, $description);
        $this->setNullable($nullable);
        $this->schema = $schema;
    }

    public function getMigrationType(): string
    {
        return 'json';
    }

    public function formField(): string
    {
        $field = "Repeater::make('{$this->name}')";
        $field .= '->schema(['.implode(', ', array_map(fn ($item) => $item->formField(), $this->schema)).'])';

        return $this->applyCommonFormFieldAttributes($field);
    }

    public function tableColumn(): string
    {
        return "TextColumn::make('{$this->name}')->json()";
    }

    public function tableFilter(): string
    {
        return "Filter::make('has_{$this->name}')->query(fn (\$query) => \$query->whereNotNull('{$this->name}'))";
    }

    public function modelCast(): string
    {
        return "'{$this->name}' => 'array'";
    }
}
