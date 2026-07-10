<?php

declare(strict_types=1);

namespace Moox\Static\Filament\Tables\Columns;

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
            ->alignCenter()
            ->searchable()
            ->state(function ($record): array {
                $translations = $record->translations()->get();

                return $translations->map(function ($translation): array {
                    $localization = Localization::query()->where('locale_variant', $translation->locale)->first();
                    $flagClass = $localization?->display_flag ?? 'heroicon-o-flag';

                    return [
                        'flag' => $flagClass,
                        'locale' => $translation->locale,
                    ];
                })->toArray();
            });
    }
}
