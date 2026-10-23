<?php

declare(strict_types=1);

namespace Moox\Support\Title;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;
use Moox\Support\Filament\FeatureFields;

trait HasTitleSlugForm
{
    /**
     * @return list<TextInput>
     */
    protected static function titleSlugFields(): array
    {
        $translation = static::featureTranslation();

        return [
            TextInput::make('title')
                ->label(FeatureFields::label($translation, 'title'))
                ->required()
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
                ->label(FeatureFields::label($translation, 'slug'))
                ->required()
                ->maxLength(255)
                ->rules(static::featureRules('slug')),
        ];
    }
}
