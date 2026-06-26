<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\Capability;
use Moox\Builder\FieldTypes\Capabilities\GalleryFiles;
use Moox\Builder\FieldTypes\Capabilities\HelperText;
use Moox\Builder\FieldTypes\FieldType;
use Moox\Builder\Forms\Components\BuilderMediaPicker;
use Moox\Builder\Support\MediaFieldValueSupport;

class GalleryFieldType extends FieldType
{
    public static function key(): string
    {
        return 'gallery';
    }

    public function castValue(mixed $raw, ?FieldDefinition $field = null): mixed
    {
        return MediaFieldValueSupport::normalizeGallery($raw);
    }

    public function persistValue(mixed $value, ?FieldDefinition $field = null): mixed
    {
        return MediaFieldValueSupport::persistGallery($value);
    }

    public function presentValue(mixed $value, ?FieldDefinition $field = null): mixed
    {
        return MediaFieldValueSupport::presentGallery($value);
    }

    public function normalizeForForm(mixed $stored): mixed
    {
        return MediaFieldValueSupport::normalizeGalleryForForm($stored);
    }

    public function capabilities(): array
    {
        return [
            HelperText::class,
            GalleryFiles::class,
        ];
    }

    public function formComponent(FieldDefinition $field): Component
    {
        $component = BuilderMediaPicker::make($field->name)
            ->label($field->label)
            ->acceptedFileTypes(['image/*'])
            ->multiple(true);

        return Capability::applyAll($this->capabilities(), $component, $field);
    }
}
