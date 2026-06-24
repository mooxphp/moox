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

    public function castValue(mixed $raw): mixed
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $value = (string) $raw;

        if (Hash::isHashed($value)) {
            return null;
        }

        return $value;
    }
}
