<?php

declare(strict_types=1);

namespace Moox\Core\Entities\Items\Static;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Entities\BaseResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Localization\Filament\Tables\Columns\TranslationColumn;
use Moox\Localization\Models\Localization;

/**
 * Lean Filament resource base for static reference data with astrotomic translations.
 * No draft/publishing or soft-deleted translation workflow — avoids BaseResource::withTrashed() paths.
 */
abstract class BaseStaticResource extends BaseResource
{
    use HasResourceTabs;

    protected static function getEntityType(): string
    {
        return 'static';
    }

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
            ->getStateUsing(function ($record, $livewire) use ($commonNameAttribute): string {
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
            });
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

    protected static function getReadonlyConfig(): bool
    {
        return (bool) config('static.readonly', false);
    }

    public static function enableCreate(): bool
    {
        return ! static::getReadonlyConfig();
    }

    public static function enableEdit(): bool
    {
        return ! static::getReadonlyConfig();
    }

    public static function enableView(): bool
    {
        return true;
    }

    public static function enableDelete(): bool
    {
        return ! static::getReadonlyConfig();
    }

    public static function enableRestore(): bool
    {
        return false;
    }

    /**
     * @return mixed[]
     */
    public static function getTableActions(): array
    {
        $actions = [];

        if (static::enableEdit()) {
            $actions[] = static::getEditTableAction();
        }

        if (static::enableView()) {
            $actions[] = static::getViewTableAction();
        }

        return $actions;
    }

    /**
     * @return mixed[]
     */
    public static function getBulkActions(): array
    {
        $actions = [];

        if (method_exists(static::class, 'getAssignScopeBulkAction')) {
            $actions[] = static::getAssignScopeBulkAction();
        }

        if (static::enableDelete()) {
            $actions[] = static::getDeleteBulkAction();
        }

        return $actions;
    }

    public static function getFormActions(): Actions
    {
        $actions = [
            static::getSaveAction()->extraAttributes(attributes: ['style' => 'width: 100%;']),
            static::getCancelAction()->extraAttributes(attributes: ['style' => 'width: 100%;']),
        ];

        if (static::enableCreate()) {
            $actions[] = static::getSaveAndCreateAnotherAction()->extraAttributes(attributes: ['style' => 'width: 100%;']);
        }

        if (static::enableDelete()) {
            $actions[] = static::getDeleteAction()->extraAttributes(attributes: ['style' => 'width: 100%;']);
        }

        if (static::enableEdit()) {
            $actions[] = static::getEditAction()->extraAttributes(attributes: ['style' => 'width: 100%;']);
        }

        return Actions::make($actions);
    }

    public static function getEditTableAction(): EditAction
    {
        return EditAction::make('edit')
            ->label(function ($record, $livewire) {
                $currentLang = $livewire->lang ?? request()->query('lang') ?? app()->getLocale();
                $translationLocale = BaseStaticModel::resolveTranslationLocale($currentLang);

                if (method_exists($record, 'translations')) {
                    $translation = $record->translations()->where('locale', $translationLocale)->first();

                    return $translation ? __('core::core.edit') : __('core::core.create');
                }

                return __('core::core.edit');
            })
            ->icon(function ($record, $livewire) {
                $currentLang = $livewire->lang ?? request()->query('lang') ?? app()->getLocale();
                $translationLocale = BaseStaticModel::resolveTranslationLocale($currentLang);

                if (method_exists($record, 'translations')) {
                    $translation = $record->translations()->where('locale', $translationLocale)->first();

                    return $translation ? 'heroicon-o-pencil-square' : 'heroicon-o-plus';
                }

                return 'heroicon-o-pencil-square';
            })
            ->color('primary')
            ->url(function ($record, $livewire) {
                $editParams = ['record' => $record];

                if (method_exists($record, 'translations')) {
                    $currentLang = $livewire->lang ?? request()->query('lang') ?? app()->getLocale();
                    $editParams['lang'] = $currentLang;
                }

                return static::getUrl('edit', $editParams);
            })
            ->hidden(fn ($livewire) => isset($livewire->activeTab) && $livewire->activeTab === 'deleted');
    }

    public static function getViewTableAction(): ViewAction
    {
        return ViewAction::make('view')
            ->color('secondary')
            ->url(function ($record, $livewire) {
                $viewParams = ['record' => $record];

                if (method_exists($record, 'translations')) {
                    $currentLang = $livewire->lang ?? request()->query('lang') ?? app()->getLocale();
                    $viewParams['lang'] = $currentLang;
                }

                return static::getUrl('view', $viewParams);
            })
            ->hidden(function ($record, $livewire) {
                if (! method_exists($record, 'translations')) {
                    return false;
                }

                $currentLang = $livewire->lang ?? request()->query('lang') ?? app()->getLocale();
                $translationLocale = BaseStaticModel::resolveTranslationLocale($currentLang);

                return $record->translations()->where('locale', $translationLocale)->doesntExist();
            });
    }

    public static function getDeleteBulkAction(): BulkAction
    {
        return BulkAction::make('delete')
            ->label(__('core::core.selected_records_delete'))
            ->requiresConfirmation()
            ->color('danger')
            ->action(function ($records, $livewire): void {
                foreach ($records as $record) {
                    $record->delete();
                }

                Notification::make()
                    ->title(__('core::core.deleted'))
                    ->success()
                    ->send();

                $livewire->redirect(static::getUrl('index'));
            });
    }

    public static function getDeleteAction(): Action
    {
        return Action::make('delete')
            ->label(__('core::core.delete'))
            ->color('danger')
            ->outlined()
            ->action(function ($livewire): void {
                if (is_object($livewire->record)) {
                    $livewire->record->delete();
                }

                Notification::make()
                    ->title(__('core::core.deleted'))
                    ->success()
                    ->send();

                $livewire->redirect(static::getUrl('index'));
            })
            ->visible(fn ($livewire): bool => $livewire instanceof EditRecord && $livewire->record !== null)
            ->requiresConfirmation();
    }

    public static function getEditAction(): EditAction
    {
        return EditAction::make('edit')
            ->label(__('core::core.edit'))
            ->color('primary')
            ->keyBindings(['command+e', 'ctrl+e'])
            ->url(function ($record, $livewire) {
                $editParams = ['record' => $livewire->record];

                if (method_exists($livewire->record, 'translations')) {
                    $currentLang = $livewire->lang ?? request()->query('lang') ?? app()->getLocale();
                    $editParams['lang'] = $currentLang;
                }

                return static::getUrl('edit', $editParams);
            })
            ->visible(function ($livewire) {
                if (! $livewire instanceof ViewRecord || ! $livewire->record) {
                    return false;
                }

                if (! method_exists($livewire->record, 'translations')) {
                    return true;
                }

                $currentLang = $livewire->lang ?? request()->query('lang') ?? app()->getLocale();
                $translationLocale = BaseStaticModel::resolveTranslationLocale($currentLang);

                return $livewire->record->translations()->where('locale', $translationLocale)->exists();
            });
    }
}
