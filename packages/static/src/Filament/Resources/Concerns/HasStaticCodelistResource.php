<?php

declare(strict_types=1);

namespace Moox\Static\Filament\Resources\Concerns;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Entities\Items\Static\BaseStaticModel;
use Moox\Localization\Models\Localization;
use Moox\Static\Filament\Tables\Columns\StaticTranslationColumn;

trait HasStaticCodelistResource
{
    /**
     * @return list<Component>
     */
    protected static function staticCodelistFormFields(): array
    {
        return [
            TextInput::make('common_name')
                ->label(__('data::fields.common_name'))
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->label(__('data::fields.description'))
                ->columnSpanFull(),
        ];
    }

    /**
     * @param  list<TextColumn>  $extraColumns
     * @return list<TextColumn|StaticTranslationColumn>
     */
    protected static function staticCodelistTableColumns(array $extraColumns = []): array
    {
        return [
            TextColumn::make('code')
                ->label(__('data::fields.code'))
                ->sortable()
                ->searchable(),
            ...$extraColumns,
            static::getCommonNameColumn(),
            StaticTranslationColumn::make('translations.locale'),
        ];
    }

    protected static function getCommonNameColumn(): TextColumn
    {
        return TextColumn::make('common_name')
            ->label(__('data::fields.common_name'))
            ->searchable(true, function (Builder $query, string $search, $livewire): void {
                $translationLocale = static::resolveTranslationLocaleForLivewire($livewire);
                $query->whereHas('translations', function (Builder $query) use ($search, $translationLocale): void {
                    $query->where('locale', $translationLocale)
                        ->where('common_name', 'like', '%'.$search.'%');
                });
            })
            ->extraAttributes(function ($record, $livewire): array {
                $translationLocale = static::resolveTranslationLocaleForLivewire($livewire);

                return [
                    'style' => $record->translations()->where('locale', $translationLocale)->whereNotNull('common_name')->exists()
                        ? ''
                        : 'color: var(--gray-500);',
                ];
            })
            ->getStateUsing(function ($record, $livewire): string {
                $currentLang = static::resolveCurrentLang($livewire);
                $translationLocale = BaseStaticModel::resolveTranslationLocale($currentLang);

                $translation = $record->translations()->where('locale', $translationLocale)->first();
                if ($translation && $translation->common_name) {
                    return $translation->common_name;
                }

                $defaultLocalization = Localization::query()->where('is_default', true)->first();
                $defaultLang = $defaultLocalization->locale_variant ?? app()->getLocale();
                $defaultTranslationLocale = BaseStaticModel::resolveTranslationLocale($defaultLang);
                $fallbackTranslation = $record->translations()->where('locale', $defaultTranslationLocale)->first();

                if ($fallbackTranslation && $fallbackTranslation->common_name) {
                    return $fallbackTranslation->common_name.' ('.$defaultLang.')';
                }

                $anyTranslation = $record->translations()->whereNotNull('common_name')->first();
                if ($anyTranslation && $anyTranslation->common_name) {
                    return $anyTranslation->common_name.' ('.$anyTranslation->locale.')';
                }

                return __('core::core.no_title_available');
            });
    }

    protected static function staticCodelistCommonNameFilter(): Filter
    {
        return Filter::make('common_name')
            ->schema([
                TextInput::make('common_name')
                    ->label(__('data::fields.common_name'))
                    ->placeholder(__('core::core.search')),
            ])
            ->query(function (Builder $query, array $data, $livewire): Builder {
                $value = $data['common_name'] ?? null;

                if (! is_string($value) || $value === '') {
                    return $query;
                }

                $currentLang = static::resolveCurrentLang($livewire);
                $translationLocale = BaseStaticModel::resolveTranslationLocale($currentLang);

                return $query->whereHas('translations', function (Builder $query) use ($value, $translationLocale): void {
                    $query->where('locale', $translationLocale)
                        ->where('common_name', 'like', '%'.$value.'%');
                });
            })
            ->indicateUsing(function (array $data): ?string {
                if (empty($data['common_name'])) {
                    return null;
                }

                return __('data::fields.common_name').': '.$data['common_name'];
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
