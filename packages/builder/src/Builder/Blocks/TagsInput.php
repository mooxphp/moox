<?php

namespace Moox\Builder\Builder\Blocks;

class TagsInput extends Base
{
    protected static array $useStatements = [
        'use Filament\Forms\Components\TagsInput;',
        'use Filament\Tables\Columns\TextColumn;',
        'use Filament\Tables\Filters\MultiSelectFilter;',
    ];

    protected ?array $suggestions;

    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        ?array $suggestions = null
    ) {
        parent::__construct($name, $label, $description);
        $this->setNullable($nullable);
        $this->suggestions = $suggestions;
    }

    protected function getMigrationType(): string
    {
        return 'json';
    }

    public function formField(): string
    {
        $field = "TagsInput::make('{$this->name}')";
        if ($this->suggestions) {
            $field .= '->suggestions('.var_export($this->suggestions, true).')';
        }

        return $this->applyCommonFormFieldAttributes($field);
    }

    public function tableColumn(): string
    {
        return "TextColumn::make('{$this->name}')->listWithLineBreaks()";
    }

    public function tableFilter(): string
    {
        return "MultiSelectFilter::make('{$this->name}')->options(".var_export($this->suggestions ?? [], true).')';
    }

    public function modelCast(): string
    {
        return "'{$this->name}' => 'array'";
    }
}
