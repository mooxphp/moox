<?php

namespace Moox\Builder\Builder\Blocks;

class MarkdownEditor extends Base
{
    protected bool $searchable;

    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        bool $searchable = false
    ) {
        parent::__construct($name, $label, $description);
        $this->setNullable($nullable);
        $this->searchable = $searchable;
    }

    protected function getMigrationType(): string
    {
        return 'text';
    }

    public function formField(): string
    {
        $field = "MarkdownEditor::make('{$this->name}')";

        return $this->applyCommonFormFieldAttributes($field);
    }

    public function tableColumn(): string
    {
        $column = "TextColumn::make('{$this->name}')->markdown()";
        if ($this->searchable) {
            $column .= '->searchable()';
        }

        return $column;
    }

    public function tableFilter(): string
    {
        return "TextFilter::make('{$this->name}')";
    }

    public function modelCast(): string
    {
        return "'{$this->name}' => 'string'";
    }
}
