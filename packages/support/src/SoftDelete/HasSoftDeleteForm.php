<?php

declare(strict_types=1);

namespace Moox\Support\SoftDelete;

use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Moox\Support\Filament\FeatureFields;

trait HasSoftDeleteForm
{
    protected static function softDeleteSection(): Section
    {
        $translation = static::featureTranslation();

        return Section::make(FeatureFields::label($translation, 'soft_delete'))
            ->schema([
                FeatureFields::locked($translation, 'deleted_at'),
                FeatureFields::locked($translation, 'deleted_by_id'),
                FeatureFields::locked($translation, 'deleted_by_type'),
                FeatureFields::locked($translation, 'restored_at'),
                FeatureFields::locked($translation, 'restored_by_id'),
                FeatureFields::locked($translation, 'restored_by_type'),
            ])
            ->columns(2)
            ->hidden(fn (?Model $record): bool => $record === null);
    }
}
