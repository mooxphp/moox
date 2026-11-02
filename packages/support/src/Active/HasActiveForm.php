<?php

declare(strict_types=1);

namespace Moox\Support\Active;

use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Moox\Support\Filament\FeatureFields;

trait HasActiveForm
{
    protected static function activeSection(): Section
    {
        $translation = static::featureTranslation();

        return Section::make(FeatureFields::label($translation, 'active'))
            ->schema([
                Toggle::make('is_active')
                    ->label(FeatureFields::label($translation, 'is_active'))
                    ->default(true)
                    ->rules(static::featureRules('is_active')),
            ]);
    }
}
