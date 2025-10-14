<?php

declare(strict_types=1);

namespace Moox\Localization\Filament\Tables\Columns;

use Filament\Tables\Columns\TextColumn;
use Moox\Data\Models\StaticLanguage;
use Moox\Localization\Models\Localization;

class TranslationColumn extends TextColumn
{
    protected string $view = 'localization::filament.tables.columns.translations';

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('localization::fields.language'))
            ->toggleable()
            ->alignCenter()
            ->searchable()
            ->state(function ($record) {
                $translations = $record->translations()->withTrashed()->get();

                $flags = $translations->map(function ($translation) {
                    $localization = Localization::where('locale_variant', $translation->locale)->first();

                    if ($localization) {
                        $flagClass = $localization->display_flag;
                    } else {
                        $languageCode = explode('_', $translation->locale)[0];
                        $locale = StaticLanguage::where('alpha2', $languageCode)->first();
                        $flagClass = $locale ? $locale->flag_icon : 'heroicon-o-flag';
                    }

                    if ($translation->trashed()) {
                        $flagClass .= ' trashed';
                    }

                    return $flagClass;
                })->toArray();

                return $flags;
            });
    }
}
