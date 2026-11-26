<?php

declare(strict_types=1);

namespace Moox\Support\Title\Forms;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;
use Moox\Support\Filament\FeatureFields;

trait HasTitleSlugForm
{
    protected static function titleSlugIsRequired(): bool
    {
        return ! in_array(
            HasOptionalTitleSlugForm::class,
            class_uses_recursive(static::class),
            true,
        );
    }

    /**
     * @return list<TextInput>
     */
    protected static function titleSlugFields(): array
    {
        $translation = static::featureTranslation();
        $required = static::titleSlugIsRequired();

        return [
            TextInput::make('title')
                ->label(FeatureFields::label($translation, 'title', 'title'))
                ->required($required)
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function (?string $state, Set $set): void {
                    if (! filled($state)) {
                        return;
                    }

                    $set('slug', Str::slug($state));
                })
                ->rules(static::featureRules('title')),

            TextInput::make('slug')
                ->label(FeatureFields::label($translation, 'slug', 'title'))
                ->required($required)
                ->maxLength(255)
                ->rules(static::featureRules('slug')),
        ];
    }
}
