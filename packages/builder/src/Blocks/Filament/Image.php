<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Filament;

class Image extends FileUpload
{
    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        bool $multiple = false,
        string $directory = 'images',
        protected int $maxWidth = 2000,
        protected int $maxHeight = 2000,
    ) {
        parent::__construct(
            $name,
            $label,
            $description,
            $nullable,
            $multiple,
            $directory,
            ['image/jpeg', 'image/png', 'image/webp'],
            5120
        );

        $this->formFields['resource'] = [
            "FileUpload::make('{$this->name}')
                ->label('{$this->label}')
                ->directory('{$this->directory}')
                ->image()
                ->imageResizeMode('contain')
                ->imageResizeTargetWidth({$this->maxWidth})
                ->imageResizeTargetHeight({$this->maxHeight})"
                .($this->multiple ? '->multiple()' : '')
                .($this->nullable ? '' : '->required()'),
        ];
    }
}
