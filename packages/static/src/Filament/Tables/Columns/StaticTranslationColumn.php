<?php

declare(strict_types=1);

namespace Moox\Static\Filament\Tables\Columns;

use Filament\Tables\Columns\TextColumn;
use Moox\Data\Models\StaticLanguage;
use Moox\Localization\Models\Localization;

class StaticTranslationColumn extends TextColumn
{
    protected string $view = 'static::filament.tables.columns.translations';

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('localization::fields.language'))
            ->toggleable()
            ->searchable()
            ->state(function ($record): array {
                $translations = $record->translations()->get();

                return $translations->map(function ($translation): array {
                    $localization = Localization::query()->where('locale_variant', $translation->locale)->first();

                    if ($localization) {
                        $flagClass = $localization->display_flag;
                    } else {
                        $languageCode = explode('_', $translation->locale)[0];
                        $language = StaticLanguage::query()->where('alpha2', $languageCode)->first();
                        $flagClass = $language ? $language->flag_icon : 'heroicon-o-flag';
                    }

                    return [
                        'flag' => $flagClass,
                        'locale' => $translation->locale,
                    ];
                })->toArray();
            });
    }
}
