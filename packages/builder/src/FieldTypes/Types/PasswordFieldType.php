<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Facades\Hash;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\HelperText;
use Moox\Builder\FieldTypes\Capabilities\MaxLength;
use Moox\Builder\FieldTypes\Capabilities\Placeholder;
use Moox\Builder\FieldTypes\Capabilities\PrefixSuffix;
use Moox\Builder\FieldTypes\FieldType;
use Moox\Builder\FieldTypes\Value\StoredPassword;

class PasswordFieldType extends FieldType
{
    public static function key(): string
    {
        return 'password';
    }

    public function capabilities(): array
    {
        return [
            MaxLength::class,
            Placeholder::class,
            PrefixSuffix::class,
            HelperText::class,
        ];
    }

    public function formComponent(FieldDefinition $field): Component
    {
        $component = TextInput::make($field->name)
            ->label($field->label)
            ->password()
            ->revealable();

        $component = $this->applyCapabilitiesAndValidation($component, $field);

        if (! filled($field->config['helperText'] ?? null)) {
            $component->helperText(__('builder::builder.password.helper'));
        }

        return $component;
    }

    public function castValue(mixed $raw, ?FieldDefinition $field = null): mixed
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        return StoredPassword::instance();
    }

    public function persistValue(mixed $value, ?FieldDefinition $field = null): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (string) $value;

        if (Hash::isHashed($value)) {
            return $value;
        }

        return Hash::make($value);
    }

    public function normalizeForForm(mixed $stored): mixed
    {
        if ($stored === null || $stored === '') {
            return null;
        }

        return null;
    }

    public function presentValue(mixed $value, ?FieldDefinition $field = null): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return ['has_value' => true];
    }
}
