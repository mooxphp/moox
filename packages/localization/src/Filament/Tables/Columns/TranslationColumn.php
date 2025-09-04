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
                $translations = $record->translations()->withTrashed()->get();

                $flags = $translations->map(function ($translation) {
                    $languageCode = explode('_', $translation->locale)[0];
                    $locale = StaticLanguage::where('alpha2', $languageCode)->first();

                    if ($locale) {
                        $flagClass = $locale->flag_icon;
                    } else {
                        $flagClass = 'heroicon-o-flag';
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
