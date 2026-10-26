<?php

declare(strict_types=1);

namespace Moox\Support\External;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Moox\Support\Filament\FeatureFields;

trait HasExternalForm
{
    protected static function externalSection(): Section
    {
        $translation = static::featureTranslation();

        return Section::make(FeatureFields::label($translation, 'external'))
            ->schema([
                TextInput::make('external_reference')
                    ->label(FeatureFields::label($translation, 'external_reference'))
                    ->maxLength(255)
                    ->rules(static::featureRules('external_reference')),
                TextInput::make('external_info')
                    ->label(FeatureFields::label($translation, 'external_info'))
                    ->maxLength(255)
                    ->rules(static::featureRules('external_info')),
                Select::make('external_status')
                    ->label(FeatureFields::label($translation, 'external_status'))
                    ->options([])
                    ->nullable()
                    ->helperText(FeatureFields::label($translation, 'external_status_undefined'))
                    ->rules(static::featureRules('external_status')),
            ])
            ->columns(2);
    }
}
