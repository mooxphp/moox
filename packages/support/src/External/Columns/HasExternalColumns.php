<?php

declare(strict_types=1);

namespace Moox\Support\External\Columns;

use Filament\Tables\Columns\TextColumn;
use Moox\Support\Filament\FeatureFields;

trait HasExternalColumns
{
    protected static function externalReferenceColumn(): TextColumn
    {
        return TextColumn::make('external_reference')
            ->label(FeatureFields::label(static::featureTranslation(), 'external_reference', 'external', 'columns'))
            ->toggleable(isToggledHiddenByDefault: true);
    }
}
