<?php

namespace Moox\Builder\Blocks;

class Builder extends Base
{
    protected static array $useStatements = [
        'use Filament\Forms\Components\Builder;',
        'use Filament\Tables\Columns\TextColumn;',
        'use Filament\Tables\Filters\Filter;',
    ];

    protected array $blocks;

    public function __construct(
        string $name,
        string $label,
        string $description,
        array $blocks,
        bool $nullable = false
    ) {
        parent::__construct($name, $label, $description);
        $this->setNullable($nullable);
        $this->blocks = $blocks;
    }

    protected function getMigrationType(): string
    {
        return 'json';
    }

    public function formField(): string
    {
        $field = "Builder::make('{$this->name}')";
        $field .= '->blocks(['.implode(', ', array_map(fn ($block) => $block->formField(), $this->blocks)).'])';

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
