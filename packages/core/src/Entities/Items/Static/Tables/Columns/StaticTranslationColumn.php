<?php

declare(strict_types=1);

namespace Moox\Core\Entities\Items\Static\Tables\Columns;

use Filament\Tables\Columns\TextColumn;
use Moox\Localization\Models\Localization;

class StaticTranslationColumn extends TextColumn
{
    protected string $view = 'localization::filament.tables.columns.translations';

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('localization::fields.language'))
            ->toggleable()
            ->alignLeft()
            ->extraHeaderAttributes(['class' => 'fi-align-start'])
            ->searchable()
            ->state(function ($record): array {
                $translations = $record->translations()->get();

                return $translations->map(function ($translation): array {
                    $localization = Localization::query()->where('locale_variant', $translation->locale)->first();

                    if ($localization) {
                        $flagClass = $localization->display_flag;
                    } else {
                        $languageCode = explode('_', $translation->locale)[0];
                        $languageClass = 'Moox\\Data\\Models\\StaticLanguage';
                        if (class_exists($languageClass)) {
                            $language = $languageClass::query()->where('alpha2', $languageCode)->first();
                            $flagClass = $language ? $language->flag_icon : 'heroicon-o-flag';
                        } else {
                            $flagClass = 'heroicon-o-flag';
                        }
                    }

                    return [
                        'flag' => $flagClass,
                        'locale' => $translation->locale,
                    ];
                })->toArray();
            });
    }
}
