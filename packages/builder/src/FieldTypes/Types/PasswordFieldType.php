<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\MaxLength;
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
        ];
    }

    public function formComponent(FieldDefinition $field): Component
    {
        $component = TextInput::make($field->name)
            ->label($field->label)
            ->password()
            ->revealable(false)
            ->dehydrated(fn (?string $state): bool => filled($state));

        return $this->applyCapabilitiesAndValidation($component, $field);
    }
}
