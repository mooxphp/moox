<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Blocks;

class Image extends AbstractBlock
{
    protected array $useStatements = [
        'resource' => [
            'forms' => ['use Filament\Forms\Components\FileUpload;'],
            'columns' => ['use Filament\Tables\Columns\ImageColumn;'],
            'filters' => ['use Filament\Tables\Filters\Filter;'],
        ],
    ];

    protected ?int $maxSize;

    protected array $allowedTypes;

    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        ?int $maxSize = null,
        array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif']
    ) {
        parent::__construct($name, $label, $description);
        $this->setNullable($nullable);
        $this->maxSize = $maxSize;
        $this->allowedTypes = $allowedTypes;
    }

    public function getMigrationType(): string
    {
        return 'string';
    }

    public function formField(): string
    {
        $field = "FileUpload::make('{$this->name}')->image()";
        if ($this->maxSize) {
            $field .= "->maxSize({$this->maxSize})";
        }
        $allowedTypes = implode("', '", $this->allowedTypes);
        $field .= "->acceptedFileTypes(['{$allowedTypes}'])";

        return $this->applyCommonFormFieldAttributes($field);
    }

    public function tableColumn(): string
    {
        return "ImageColumn::make('{$this->name}')";
    }

    public function tableFilter(): string
    {
        return "Filter::make('has_{$this->name}')->query(fn (\$query) => \$query->whereNotNull('{$this->name}'))";
    }

    public function modelCast(): string
    {
        return "'{$this->name}' => 'string'";
    }
}
