<?php

declare(strict_types=1);

namespace Moox\Support\Archive;

use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Moox\Support\Filament\FeatureFields;

trait HasArchiveForm
{
    protected static function archiveSection(): Section
    {
        $translation = static::featureTranslation();

        return Section::make(FeatureFields::label($translation, 'archive'))
            ->schema([
                FeatureFields::locked($translation, 'archived_at'),
                FeatureFields::locked($translation, 'archived_by_id'),
                FeatureFields::locked($translation, 'archived_by_type'),
            ])
            ->columns(2)
            ->hidden(fn (?Model $record): bool => $record === null);
    }
}
