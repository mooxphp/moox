<?php

namespace Moox\Builder\Builder\Blocks;

class Relationship extends Base
{
    protected static array $useStatements = [
        'use Filament\Forms\Components\Select;',
        'use Filament\Tables\Columns\TextColumn;',
        'use Filament\Tables\Filters\SelectFilter;',
    ];

    protected string $relatedModel;

    protected string $relationshipType;

    protected ?string $displayColumn;

    public function __construct(
        string $name,
        string $label,
        string $description,
        string $relatedModel,
        string $relationshipType,
        ?string $displayColumn = null,
        bool $nullable = false
    ) {
        parent::__construct($name, $label, $description);
        $this->setNullable($nullable);
        $this->relatedModel = $relatedModel;
        $this->relationshipType = $relationshipType;
        $this->displayColumn = $displayColumn;
    }

    protected function getMigrationType(): string
    {
        return $this->relationshipType === 'belongsTo' ? 'foreignId' : 'id';
    }

    public function formField(): string
    {
        $field = "Select::make('{$this->name}')";
        $field .= "->relationship('{$this->name}', '{$this->displayColumn}')";

        return $this->applyCommonFormFieldAttributes($field);
    }

    public function tableColumn(): string
    {
        return "TextColumn::make('{$this->name}.{$this->displayColumn}')";
    }

    public function tableFilter(): string
    {
        return "SelectFilter::make('{$this->name}')->relationship('{$this->name}', '{$this->displayColumn}')";
    }

    public function migration(): string
    {
        if ($this->relationshipType === 'belongsTo') {
            $migration = parent::migration();
            $migration .= "\$table->foreign('{$this->name}')->references('id')->on('".strtolower($this->relatedModel)."s');";

            return $migration;
        }

        return parent::migration();
    }

    public function modelAttribute(): string
    {
        return "'{$this->name}'";
    }

    public function modelCast(): string
    {
        return '';
    }
}
