<?php

declare(strict_types=1);

namespace Moox\Support\Title;

use Filament\Forms\Components\TextInput;

/**
 * Title/slug stay optional — empty values may inherit from a parent record.
 */
trait HasOptionalTitleSlugForm
{
    protected static function titleSlugIsRequired(): bool
    {
        return false;
    }

    protected static function titleTranslationsMinItems(): ?int
    {
        return null;
    }

    /**
     * @return list<TextInput>
     */
    protected static function optionalTitleSlugFields(): array
    {
        return array_map(
            static fn (TextInput $field): TextInput => $field->required(false),
            static::titleSlugFields(),
        );
    }
}
