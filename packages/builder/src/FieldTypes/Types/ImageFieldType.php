<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\Capability;
use Moox\Builder\FieldTypes\Capabilities\HelperText;
use Moox\Builder\FieldTypes\FieldType;
use Moox\Builder\Forms\Components\BuilderMediaPicker;
use Moox\Builder\Support\MediaFieldValueSupport;

class ImageFieldType extends FieldType
{
    public static function key(): string
    {
        return 'image';
    }

    public function castValue(mixed $raw, ?FieldDefinition $field = null): mixed
    {
        return MediaFieldValueSupport::normalizeSnapshot($raw);
    }

    public function persistValue(mixed $value, ?FieldDefinition $field = null): mixed
    {
        return MediaFieldValueSupport::persistSingle($value);
    }

    public function presentValue(mixed $value, ?FieldDefinition $field = null): mixed
    {
        return MediaFieldValueSupport::presentSingle($value);
    }

    public function normalizeForForm(mixed $stored): mixed
    {
        $ids = MediaFieldValueSupport::extractIds($stored);

        return $ids === [] ? [] : [$ids[0]];
    }

    public function capabilities(): array
    {
        return [
            HelperText::class,
        ];
    }

    public function formComponent(FieldDefinition $field): Component
    {
        $component = BuilderMediaPicker::make($field->name)
            ->label($field->label)
            ->acceptedFileTypes(['image/*'])
            ->maxFiles(1)
            ->multiple(false);

        return Capability::applyAll($this->capabilities(), $component, $field);
    }
}
