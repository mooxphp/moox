<?php

declare(strict_types=1);

namespace Moox\Localization\Filament\Tables\Columns;

use Filament\Tables\Columns\TextColumn;
use Moox\Data\Models\StaticLocale;

class TranslationColumn extends TextColumn
{
    protected string $view = 'localization::filament.tables.columns.translations';

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('localization::fields.language'))
            ->sortable()
            ->toggleable()
            ->alignCenter()
            ->searchable()
            ->state(function ($record) {
                return $record->translations->map(function ($translation) {
                    $locale = StaticLocale::where('locale', $translation->locale)->first();

                    return $locale->language_flag_icon ?? 'flag-'.$translation->locale;
                })->toArray();
            });
    }
}
