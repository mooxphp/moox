<?php

declare(strict_types=1);

namespace Moox\Support\Active\Columns;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Moox\Support\Filament\FeatureFields;

trait HasActiveColumns
{
    protected static function activeColumn(): IconColumn
    {
        return IconColumn::make('is_active')
            ->label(FeatureFields::label(static::featureTranslation(), 'is_active', 'active', 'columns'))
            ->boolean();
    }

    protected static function activeFilter(): TernaryFilter
    {
        return TernaryFilter::make('is_active')
            ->label(FeatureFields::label(static::featureTranslation(), 'is_active', 'active', 'columns'));
    }
}
