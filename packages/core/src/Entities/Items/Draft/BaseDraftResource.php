<?php

namespace Moox\Core\Entities\Items\Draft;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Entities\BaseResource;
use Moox\Core\Traits\HasStatusColors;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Draft\Enums\TranslationStatus;

class BaseDraftResource extends BaseResource
{
    use HasResourceTabs, HasStatusColors;

    protected static function getReadonlyConfig(): bool
    {
        $entityType = static::getEntityType();

        return config("{$entityType}.readonly", false);
    }

    protected static function getEntityType(): string
    {
        return 'draft';
    }

    public static function enableCreate(): bool
    {
        if (static::getReadonlyConfig()) {
            return false;
        }

        return true;
    }

    public static function enableEdit(): bool
    {
        if (static::getReadonlyConfig()) {
            return false;
        }

        return true;
    }

    public static function enablePublish(): bool
    {
        if (static::getReadonlyConfig()) {
            return false;
        }

        return true;
    }

    public static function enableView(): bool
    {
        return true;
    }

    public static function enableDelete(): bool
    {
        if (static::getReadonlyConfig()) {
            return false;
        }

        return true;
    }

    public static function enableRestore(): bool
    {
        if (static::getReadonlyConfig()) {
            return false;
        }

        return true;
    }

    /**
     * @return mixed[]
     */
    public static function getTableActions(): array
    {
        $actions = [];

        if (static::enableRestore()) {
            $actions[] = static::getRestoreTableAction();
        }

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

        if (static::enableDelete()) {
            $actions[] = static::getDeleteBulkAction();
        }

        if (static::enableRestore()) {
            $actions[] = static::getRestoreBulkAction();
        }

        return $actions;
    }

    public static function getFormActions(): Actions
    {
        $actions = [
            static::getSaveAction()->extraAttributes(attributes: ['style' => 'width: 100%;']),
            static::getCancelAction()->extraAttributes(attributes: ['style' => 'width: 100%;']),
        ];

        if (static::enableRestore()) {
            $actions[] = static::getRestoreAction()->extraAttributes(attributes: ['style' => 'width: 100%;']);
        }

        if (static::enableCreate()) {
            $actions[] = static::getSaveAndCreateAnotherAction()->extraAttributes(attributes: ['style' => 'width: 100%;']);
        }

        if (static::enableDelete()) {
            $actions[] = static::getDeleteAction()->extraAttributes(attributes: ['style' => 'width: 100%;']);
        }

        if (static::enableEdit()) {
            $actions[] = static::getEditAction()->extraAttributes(attributes: ['style' => 'width: 100%;']);
        }

        if (static::enablePublish()) {
            $actions[] = static::getPublishAction()->extraAttributes(attributes: ['style' => 'width: 100%;']);
        }

        return Actions::make($actions);
    }

    public static function getFooterActions(): Actions
    {
        return Actions::make([
            static::getSaveAction(),
            static::getCancelAction(),
        ]);
    }

    public static function query(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function modifyEloquentQuery(Builder $query): Builder
    {
        $query = parent::modifyEloquentQuery($query);

        if (method_exists(static::getModel(), 'translations')) {
            $query->with([
                'translations' => function ($query) {
                    $query->withTrashed();
                },
            ]);
        }

        return $query;
    }

    /**
     * Get a title column with fallback to app locale when translation is missing
     */
    public static function getTitleColumn(): TextColumn
    {
        return TextColumn::make('title')
            ->label('Title')
            ->searchable(true, function ($query, $search, $livewire) {
                $currentLang = $livewire->lang;
                $query->whereHas('translations', function ($query) use ($search, $currentLang) {
                    $query->where('locale', $currentLang)
                        ->where('title', 'like', '%'.$search.'%');
                });
            })
            ->sortable()
            ->extraAttributes(fn ($record) => [
                'style' => $record->translations()->where('locale', request()->get('lang', app()->getLocale()))->withTrashed()->whereNotNull('title')->exists()
                    ? ''
                    : 'color: var(--gray-500);',
            ])
            ->getStateUsing(function ($record, $livewire) {
                $currentLang = $livewire->lang;

                $translation = $record->translations()->withTrashed()->where('locale', $currentLang)->first();

                if ($translation && $translation->title) {
                    return $translation->title;
                }

                $fallbackTranslation = $record->translations()->where('locale', app()->getLocale())->first();

                if ($fallbackTranslation && $fallbackTranslation->title) {
                    return $fallbackTranslation->title.' ('.app()->getLocale().')';
                }

                return 'No title available';
            });
    }

    public static function getSlugColumn(): TextColumn
    {
        return TextColumn::make('slug')
            ->label('Slug')
            ->searchable(true, function ($query, $search) {
                $currentLang = request()->get('lang', app()->getLocale());
                $query->whereHas('translations', function ($query) use ($search, $currentLang) {
                    $query->where('locale', $currentLang)
                        ->where('slug', 'like', '%'.$search.'%');
                });
            })
            ->sortable();
    }

    public static function getTranslationStatusSelect(): Select
    {
        return Select::make('translation_status')
            ->label('Status')
            ->reactive()
            ->default(TranslationStatus::DRAFT->value)
            ->selectablePlaceholder(false)
            ->options(static::getEditableTranslationStatusOptions());
    }

    /**
     * Get editable translation status options (without not_translated)
     */
    public static function getEditableTranslationStatusOptions(): array
    {
        return collect(TranslationStatus::cases())
            ->filter(fn ($case) => ! in_array($case, [TranslationStatus::NOT_TRANSLATED, TranslationStatus::DELETED]))
            ->mapWithKeys(fn ($case) => [$case->value => ucfirst($case->value)])
            ->toArray();
    }

    protected static function getCurrentTranslationStatus($record): string
    {
        if (! $record) {
            return TranslationStatus::DRAFT->value;
        }

        $currentLang = request()->get('lang', app()->getLocale());
        $translation = $record->translations()->where('locale', $currentLang)->first();

        if (! $translation) {
            return TranslationStatus::NOT_TRANSLATED->value;
        }

        if ($translation->trashed()) {
            return TranslationStatus::DELETED->value;
        }

        return $translation->translation_status?->value ?? TranslationStatus::DRAFT->value;
    }

    protected static function getDefaultStatus(): string
    {
        return TranslationStatus::DRAFT->value;
    }

    /**
     * Get available translation status options
     */
    public static function getTranslationStatusOptions(): array
    {
        return collect(TranslationStatus::cases())
            ->mapWithKeys(fn ($case) => [$case->value => ucfirst($case->value)])
            ->toArray();
    }

    /**
     * Get type select field
     */
    public static function getTypeSelect(): Select
    {
        return Select::make('type')
            ->label(__('core::core.type'))
            ->options(['Post' => 'Post', 'Page' => 'Page']);
    }

    /**
     * Get publish date field
     */
    public static function getPublishDateField(): DateTimePicker
    {
        return DateTimePicker::make('to_publish_at')
            ->label(__('core::core.to_publish_at'))
            ->placeholder(__('core::core.to_publish_at'))
            ->minDate(now())
            ->hidden(fn ($get) => $get('translation_status') !== 'scheduled')
            ->dehydrateStateUsing(fn ($state, $get) => $get('translation_status') === 'scheduled' ? $state : null);
    }

    /**
     * Get unpublish date field
     */
    public static function getUnpublishDateField(): DateTimePicker
    {
        return DateTimePicker::make('to_unpublish_at')
            ->label(__('core::core.to_unpublish_at'))
            ->placeholder(__('core::core.to_unpublish_at'))
            ->minDate(now())
            ->hidden(fn ($get) => ! in_array($get('translation_status'), ['scheduled', 'published']))
            ->dehydrateStateUsing(fn ($state, $get) => in_array($get('translation_status'), ['scheduled', 'published']) ? $state : null);
    }

    /**
     * Get published at text entry
     */
    public static function getPublishedAtTextEntry(): TextEntry
    {
        return TextEntry::make('published_at')
            ->label(__('core::core.published_at'))
            ->state(function ($record): string {
                $translation = $record->translations()->withTrashed()->first();
                if (! $translation || ! $translation->published_at) {
                    return '';
                }

                $publishedBy = '';
                if ($translation->published_by_id && $translation->published_by_type) {
                    $user = app($translation->published_by_type)->find($translation->published_by_id);
                    $publishedBy = $user ? ' '.__('core::core.by').' '.$user->name : '';
                }

                return $translation->published_at.' - '.$translation->published_at->diffForHumans().$publishedBy;
            })
            ->hidden(fn ($record) => ! $record->published_at);
    }

    /**
     * Get to unpublish at text entry
     */
    public static function getToUnpublishAtTextEntry(): TextEntry
    {
        return TextEntry::make('to_unpublish_at')
            ->label(__('core::core.to_unpublish_at'))
            ->state(fn ($record): string => $record->to_unpublish_at ?
                $record->to_unpublish_at.' - '.$record->to_unpublish_at->diffForHumans() : '')
            ->hidden(fn ($record) => ! $record->to_unpublish_at);
    }

    /**
     * Get standard timestamp fields
     */
    public static function getStandardTimestampFields(): array
    {
        return [
            static::getCreatedAtTextEntry(),
            static::getUpdatedAtTextEntry(),
            static::getPublishedAtTextEntry(),
            static::getToUnpublishAtTextEntry(),
        ];
    }

    public static function getTranslationStatusFilter(): SelectFilter
    {
        return SelectFilter::make('translation_status')
            ->label('Status')
            ->options(static::getTranslationStatusOptions())
            ->query(function (Builder $query, array $data): Builder {
                return $query->when(
                    $data['value'] ?? null,
                    function (Builder $query, $value): Builder {
                        $currentLang = request()->query('lang') ?? request()->get('lang') ?? app()->getLocale();

                        if (! $value) {
                            return $query;
                        }

                        if ($value === 'not_translated') {
                            return $query->whereDoesntHave('translations', function ($query) use ($currentLang) {
                                $query->where('locale', $currentLang);
                            });
                        }

                        if ($value === 'deleted') {
                            return $query->whereHas('translations', function ($query) use ($currentLang) {
                                $query->where('locale', $currentLang)
                                    ->where('translation_status', 'deleted')
                                    ->withTrashed();
                            });
                        }

                        return $query->whereHas('translations', function ($query) use ($value, $currentLang) {
                            $query->where('locale', $currentLang)
                                ->where('translation_status', $value);
                        });
                    }
                );
            });
    }

    /**
     * Get status badge column for translation status
     */
    public static function getStatusColumn(): TextColumn
    {
        return TextColumn::make('translation_status')
            ->label('Status')
            ->sortable()
            ->toggleable()
            ->badge()
            ->formatStateUsing(function ($state) {
                if ($state instanceof \BackedEnum) {
                    return ucfirst($state->value);
                }

                return ucfirst((string) $state);
            })
            ->color(function ($state): string {
                $value = $state instanceof \BackedEnum ? $state->value : (string) $state;

                return static::getStatusColor(strtolower($value));
            })
            ->getStateUsing(function ($record) {
                $currentLang = request()->get('lang', app()->getLocale());

                $translation = $record->translations()->withTrashed()->where('locale', $currentLang)->first();

                if (! $translation) {
                    return TranslationStatus::NOT_TRANSLATED;
                }

                if ($translation->trashed()) {
                    return TranslationStatus::DELETED;
                }

                return $translation->translation_status ?? TranslationStatus::DRAFT;
            });
    }
}
