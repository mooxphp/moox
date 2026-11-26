<?php

declare(strict_types=1);

namespace Moox\Support\Publish\Forms;

use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Moox\Support\Filament\FeatureFields;

trait HasPublishForm
{
    protected static function publishSection(): Section
    {
        $translation = static::featureTranslation();

        return Section::make(FeatureFields::label($translation, 'publish', 'publish'))
            ->schema([
                FeatureFields::locked($translation, 'published_at'),
                FeatureFields::locked($translation, 'published_by_id'),
                FeatureFields::locked($translation, 'published_by_type'),
                FeatureFields::locked($translation, 'unpublished_at'),
                FeatureFields::locked($translation, 'unpublished_by_id'),
                FeatureFields::locked($translation, 'unpublished_by_type'),
            ])
            ->columns(2)
            ->hidden(fn (?Model $record): bool => $record === null);
    }
}
