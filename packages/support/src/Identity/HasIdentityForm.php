<?php

declare(strict_types=1);

namespace Moox\Support\Identity;

use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Moox\Support\Filament\FeatureFields;

trait HasIdentityForm
{
    protected static function identitySection(): Section
    {
        $translation = static::featureTranslation();

        return Section::make(FeatureFields::label($translation, 'identity'))
            ->schema([
                FeatureFields::locked($translation, 'id'),
                FeatureFields::locked($translation, 'ulid'),
                FeatureFields::locked($translation, 'uuid'),
            ])
            ->columns(2)
            ->hidden(fn (?Model $record): bool => $record === null);
    }
}
