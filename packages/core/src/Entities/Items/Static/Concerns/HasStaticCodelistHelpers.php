<?php

declare(strict_types=1);

namespace Moox\Core\Entities\Items\Static\Concerns;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Entities\Items\Static\BaseStaticModel;
use Moox\Localization\Filament\Tables\Columns\TranslationColumn;
use Moox\Localization\Models\Localization;

trait HasStaticCodelistHelpers
{
    /**
     * Translated attribute used for list headings and common-name columns.
     */
    protected static function getPrimaryTranslationAttribute(): string
    {
        return 'common_name';
    }

    /**
     * @return list<Component>
     */
    protected static function staticCodelistFormFields(): array
    {
        return [
            TextInput::make('common_name')
                ->label(__('core::fields.common_name'))
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->label(__('core::fields.description'))
                ->columnSpanFull(),
        ];
    }

    /**
     * @param  list<TextColumn>  $extraColumns
     * @return list<TextColumn|TranslationColumn>
     */
    protected static function staticCodelistTableColumns(array $extraColumns = []): array
    {
        return [
            TextColumn::make('code')
                ->label(__('core::fields.code'))
                ->sortable()
                ->searchable(),
            ...$extraColumns,
            static::getCommonNameColumn(),
            TranslationColumn::make('translations.locale'),
        ];
    }

    public static function getCommonNameColumn(): TextColumn
    {
        $commonNameAttribute = static::getPrimaryTranslationAttribute();

        return TextColumn::make($commonNameAttribute)
            ->label(__('core::fields.common_name'))
            ->searchable(true, function (Builder $query, string $search, $livewire) use ($commonNameAttribute): void {
                $translationLocale = static::resolveTranslationLocaleForLivewire($livewire);
                $query->whereHas('translations', function (Builder $query) use ($search, $translationLocale, $commonNameAttribute): void {
                    $query->where('locale', $translationLocale)
                        ->where($commonNameAttribute, 'like', '%'.$search.'%');
                });
            })
            ->extraAttributes(function ($record, $livewire) use ($commonNameAttribute): array {
                $translationLocale = static::resolveTranslationLocaleForLivewire($livewire);

                return [
                    'style' => $record->translations()->where('locale', $translationLocale)->whereNotNull($commonNameAttribute)->exists()
                        ? ''
                        : 'color: var(--gray-500);',
                ];
            })
            ->getStateUsing(fn ($record, $livewire): string => static::resolveCommonNameState($record, $livewire, $commonNameAttribute));
    }

    protected static function resolveCommonNameState($record, $livewire, string $commonNameAttribute): string
    {
        $currentLang = static::resolveCurrentLang($livewire);
        $translationLocale = BaseStaticModel::resolveTranslationLocale($currentLang);

        $translation = $record->translations()->where('locale', $translationLocale)->first();
        if ($translation && $translation->{$commonNameAttribute}) {
            return $translation->{$commonNameAttribute};
        }

        $defaultLocalization = Localization::query()->where('is_default', true)->first();
        $defaultLang = $defaultLocalization->locale_variant ?? app()->getLocale();
        $defaultTranslationLocale = BaseStaticModel::resolveTranslationLocale($defaultLang);
        $fallbackTranslation = $record->translations()->where('locale', $defaultTranslationLocale)->first();

        if ($fallbackTranslation && $fallbackTranslation->{$commonNameAttribute}) {
            return $fallbackTranslation->{$commonNameAttribute}.' ('.$defaultLang.')';
        }

        $anyTranslation = $record->translations()->whereNotNull($commonNameAttribute)->first();
        if ($anyTranslation && $anyTranslation->{$commonNameAttribute}) {
            return $anyTranslation->{$commonNameAttribute}.' ('.$anyTranslation->locale.')';
        }

        return __('core::core.no_title_available');
    }

    protected static function staticCodelistCommonNameFilter(): Filter
    {
        $commonNameAttribute = static::getPrimaryTranslationAttribute();

        return Filter::make($commonNameAttribute)
            ->schema([
                TextInput::make($commonNameAttribute)
                    ->label(__('core::fields.common_name'))
                    ->placeholder(__('core::core.search')),
            ])
            ->query(function (Builder $query, array $data, $livewire) use ($commonNameAttribute): Builder {
                $value = $data[$commonNameAttribute] ?? null;

                if (! is_string($value) || $value === '') {
                    return $query;
                }

                $currentLang = static::resolveCurrentLang($livewire);
                $translationLocale = BaseStaticModel::resolveTranslationLocale($currentLang);

                return $query->whereHas('translations', function (Builder $query) use ($value, $translationLocale, $commonNameAttribute): void {
                    $query->where('locale', $translationLocale)
                        ->where($commonNameAttribute, 'like', '%'.$value.'%');
                });
            })
            ->indicateUsing(function (array $data) use ($commonNameAttribute): ?string {
                if (empty($data[$commonNameAttribute])) {
                    return null;
                }

                return __('core::fields.common_name').': '.$data[$commonNameAttribute];
            });
    }

    protected static function resolveCurrentLang($livewire = null): string
    {
        if ($livewire && property_exists($livewire, 'lang') && $livewire->lang) {
            return (string) $livewire->lang;
        }

        if ($livewire && property_exists($livewire, 'tableFilters') && ! empty($livewire->tableFilters['locale']['value'] ?? null)) {
            return (string) $livewire->tableFilters['locale']['value'];
        }

        $requestLang = request()->query('lang') ?? request()->input('lang');

        if (is_string($requestLang) && $requestLang !== '') {
            return $requestLang;
        }

        $defaultLocalization = Localization::query()->where('is_default', true)->first();

        return $defaultLocalization->locale_variant ?? app()->getLocale();
    }

    protected static function resolveTranslationLocaleForLivewire($livewire = null): string
    {
        return BaseStaticModel::resolveTranslationLocale(static::resolveCurrentLang($livewire));
    }
}
