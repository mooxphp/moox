<?php

namespace Moox\Builder\Builder\Blocks;

class TitleWithSlug extends Base
{
    protected string $titleFieldName;

    protected string $slugFieldName;

    protected static array $useStatements = [
        'use Camya\Filament\Forms\Components\TitleWithSlugInput;',
        'use Filament\Tables\Columns\TextColumn;',
        'use Filament\Tables\Filters\TextFilter;',
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

    public static function getUseStatements(): array
    {
        return self::$useStatements;
    }

    protected function getMigrationType(): string
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

    public function migration(): string
    {
        return "\$table->string('{$this->titleFieldName}');".PHP_EOL.
               "\$table->string('{$this->slugFieldName}')->unique();";
    }

    public function modelAttribute(): string
    {
        return "'{$this->titleFieldName}', '{$this->slugFieldName}'";
    }

    public function modelCast(): string
    {
        return '';
    }
}
