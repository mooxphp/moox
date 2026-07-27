<?php

declare(strict_types=1);

namespace Moox\Localization\Filament\Tables\Columns;

use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
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
            ->alignLeft()
            ->extraHeaderAttributes(['class' => 'fi-align-start'])
            ->searchable()
            ->state(function ($record) {
                $translations = $this->translationsQuery($record)->get();

                $flags = $translations->map(function ($translation) {
                    $localization = Localization::query()->where('locale_variant', $translation->locale)->first();

                    if ($localization) {
                        $flagClass = $localization->display_flag;
                    } else {
                        $languageCode = explode('_', $translation->locale)[0];
                        $locale = StaticLanguage::query()->where('alpha2', $languageCode)->first();
                        $flagClass = $locale ? $locale->flag_icon : 'heroicon-o-flag';
                    }

                    if (method_exists($translation, 'trashed') && $translation->trashed()) {
                        $flagClass .= ' trashed';
                    }

                    return [
                        'flag' => $flagClass,
                        'locale' => $translation->locale,
                    ];
                })->toArray();

                return $flags;
            });
    }

    /**
     * Draft translations soft-delete; Static translations do not.
     */
    protected function translationsQuery($record): Relation
    {
        $query = $record->translations();

        if (in_array(SoftDeletes::class, class_uses_recursive($query->getRelated()), true)) {
            return $query->withTrashed();
        }

        return $query;
    }
}
