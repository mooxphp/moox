<?php

declare(strict_types=1);

namespace Moox\Support\Filament;

use Filament\Forms\Components\TextInput;

final class FeatureFields
{
    public static function label(string $translation, string $key): string
    {
        return __($translation.'.'.$key);
    }

    public static function locked(string $translation, string $column): TextInput
    {
        return TextInput::make($column)
            ->label(self::label($translation, $column))
            ->disabled()
            ->dehydrated(false);
    }
}
