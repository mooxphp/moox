<?php

namespace Moox\Builder\Builder\Blocks;

class Radio extends Base
{
    protected static array $useStatements = [
        'use Filament\Forms\Components\Radio;',
        'use Filament\Tables\Columns\TextColumn;',
        'use Filament\Tables\Filters\SelectFilter;',
    ];

    protected array $options;

    protected bool $inline;

    public function __construct(
        string $name,
        string $label,
        string $description,
        array $options,
        bool $nullable = false,
        bool $inline = false
    ) {
        parent::__construct($name, $label, $description);
        $this->setNullable($nullable);
        $this->options = $options;
        $this->inline = $inline;
    }

    protected function getMigrationType(): string
    {
        return 'string';
    }

    public function formField(): string
    {
        $field = "Radio::make('{$this->name}')";
        $field .= '->options('.var_export($this->options, true).')';

        if ($this->inline) {
            $field .= '->inline()';
        }

        return $this->applyCommonFormFieldAttributes($field);
    }

    public function tableColumn(): string
    {
        return "TextColumn::make('{$this->name}')";
    }

    public function tableFilter(): string
    {
        return "SelectFilter::make('{$this->name}')->options(".var_export($this->options, true).')';
    }
}
