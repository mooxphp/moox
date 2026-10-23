<?php

declare(strict_types=1);

namespace Moox\Support\Audit;

use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Moox\Support\Filament\FeatureFields;

trait HasAuditForm
{
    protected static function auditSection(): Section
    {
        $translation = static::featureTranslation();

        return Section::make(FeatureFields::label($translation, 'audit'))
            ->schema([
                FeatureFields::locked($translation, 'created_at'),
                FeatureFields::locked($translation, 'created_by_id'),
                FeatureFields::locked($translation, 'created_by_type'),
                FeatureFields::locked($translation, 'updated_at'),
                FeatureFields::locked($translation, 'updated_by_id'),
                FeatureFields::locked($translation, 'updated_by_type'),
            ])
            ->columns(2)
            ->hidden(fn (?Model $record): bool => $record === null);
    }
}
