<?php

declare(strict_types=1);

namespace Moox\Localization\Filament\Tables\Columns;

use Filament\Tables\Columns\TextColumn;
use Moox\Data\Models\StaticLanguage;

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
                $flags = $record->translations->map(function ($translation) {
                    $languageCode = explode('_', $translation->locale)[0];
                    $locale = StaticLanguage::where('alpha2', $languageCode)->first();

                    return 'flag-'.strtolower($locale->alpha2);
                })->toArray();

                return $flags;
            });
    }
}
