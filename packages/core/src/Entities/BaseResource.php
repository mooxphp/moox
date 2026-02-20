<?php

namespace Moox\Core\Entities;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\MorphToSelect;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Moox\Clipboard\Forms\Components\CopyableField;

abstract class BaseResource extends Resource
{
    protected static function modifyEloquentQuery(Builder $query): Builder
    {
        if (method_exists(static::class, 'addTaxonomyRelationsToQuery')) {
            $query = static::addTaxonomyRelationsToQuery($query);
        }

        return $query;
    }

    public static function getEloquentQuery(): Builder
    {
        $model = static::getModel();
        $query = parent::getEloquentQuery();

        if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        if (method_exists(static::class, 'applySoftDeleteQuery')) {
            $query = static::applySoftDeleteQuery($query);
        }

        if (($currentTab = request()->query('tab')) && method_exists(static::class, 'applyTabQuery')) {
            $query = static::applyTabQuery($query, $currentTab);
        }

        $methods = array_filter(get_class_methods(static::class), fn ($method): bool => str_ends_with($method, 'ModifyTableQuery')
            && ! in_array($method, ['applySoftDeleteQuery', 'applyTabQuery']));

        foreach ($methods as $method) {
            $query = static::$method($query);
        }

        return static::modifyEloquentQuery($query);
    }

    public static function getTableQuery(): Builder
    {
        $model = static::getModel();

        if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
            $query = $model::query()->withoutGlobalScope(SoftDeletingScope::class);
        } else {
            $query = method_exists(parent::class, 'getTableQuery')
                ? parent::getTableQuery()
                : static::getModel()::query();
        }

        if (method_exists(static::class, 'applySoftDeleteQuery')) {
            $query = static::applySoftDeleteQuery($query);
        }

        if (($currentTab = request()->query('tab')) && method_exists(static::class, 'applyTabQuery')) {
            $query = static::applyTabQuery($query, $currentTab);
        }

        $methods = array_filter(get_class_methods(static::class), fn ($method): bool => str_ends_with($method, 'ModifyTableQuery')
            && ! in_array($method, ['applySoftDeleteQuery', 'applyTabQuery']));

        foreach ($methods as $method) {
            $query = static::$method($query);
        }

        return static::modifyEloquentQuery($query);
    }

    public static function getEditTableAction(): EditAction
    {
        return EditAction::make('edit')
            ->label(function ($record, $livewire) {
                $currentLang = $livewire->lang ?? request()->query('lang') ?? app()->getLocale();

                if (method_exists($record, 'translations')) {
                    $translation = $record->translations()->withTrashed()->where('locale', $currentLang)->first();

                    if ($translation && $translation->trashed()) {
                        return __('core::core.restore');
                    }

                    return $translation ? __('core::core.edit') : __('core::core.create');
                }

                return __('core::core.edit');
            })
            ->icon(function ($record, $livewire) {
                $currentLang = $livewire->lang ?? request()->query('lang') ?? app()->getLocale();

                if (method_exists($record, 'translations')) {
                    $translation = $record->translations()->withTrashed()->where('locale', $currentLang)->first();

                    if ($translation && $translation->trashed()) {
                        return 'heroicon-o-arrow-path';
                    }

                    return $translation ? 'heroicon-o-pencil-square' : 'heroicon-o-plus';
                }

                return 'heroicon-o-pencil-square';
            })
            ->color(function ($record, $livewire) {
                $currentLang = $livewire->lang ?? request()->query('lang') ?? app()->getLocale();

                if (method_exists($record, 'translations')) {
                    $translation = $record->translations()->withTrashed()->where('locale', $currentLang)->first();

                    if ($translation && $translation->trashed()) {
                        return 'success';
                    }
                }

                return 'primary';
            })
            ->url(function ($record, $livewire) {
                $editParams = ['record' => $record];

                if (method_exists($record, 'translations')) {
                    $currentLang = $livewire->lang ?? request()->query('lang') ?? app()->getLocale();
                    $editParams['lang'] = $currentLang;
                }

                return static::getUrl('edit', $editParams);
            })
            ->hidden(fn ($livewire) => $livewire->activeTab === 'deleted');
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
                if (method_exists($record, 'translations')) {
                    $currentLang = $livewire->lang ?? request()->query('lang') ?? app()->getLocale();
                    $translation = $record->translations()->where('locale', $currentLang)->first();

                    return ! $translation;
                }

                return false;
            });
    }

    public static function getRestoreTableAction(): RestoreAction
    {
        return RestoreAction::make('restore')
            ->label(__('core::core.restore'))
            ->color('success')
            ->action(function (\Illuminate\Database\Eloquent\Model $record, $livewire) {
                if (method_exists($record, 'translations')) {
                    DB::table($record->getTable())
                        ->where($record->getKeyName(), $record->getKey())
                        ->update(['deleted_at' => null]);

                    $translations = $record->translations()->withTrashed()->get();
                    foreach ($translations as $translation) {
                        if ($translation->trashed()) {
                            $translation->restored_at = now();
                            if (method_exists($translation, 'deletedBy')) {
                                $translation->deletedBy()->dissociate();
                            }
                            $translation->translation_status = 'draft';
                            if (auth()->check()) {
                                $translation->restoredBy()->associate(auth()->user());
                            }
                            $translation->restore();
                        }
                    }
                } else {
                    if (method_exists($record, 'restore')) {
                        if (auth()->check()) {
                            if (property_exists($record, 'restored_at') || $record->isFillable('restored_at')) {
                                $record->setAttribute('restored_at', now());
                            }
                            if (method_exists($record, 'restoredBy')) {
                                $record->restoredBy()->associate(auth()->user());
                            }
                            if (method_exists($record, 'deletedBy')) {
                                $record->deletedBy()->dissociate();
                            }
                        }

                        $record->restore();
                    }
                }

                $livewire->redirect(static::getUrl('index'));
            })
            ->visible(fn ($livewire): bool => isset($livewire->activeTab) && in_array($livewire->activeTab, ['trash', 'deleted']));
    }

    public static function getRestoreBulkAction(): RestoreBulkAction
    {
        return RestoreBulkAction::make()
            ->visible(fn ($livewire): bool => isset($livewire->activeTab) && in_array($livewire->activeTab, ['trash', 'deleted']))
            ->action(function (array $records, $livewire): void {
                /** @var \Illuminate\Database\Eloquent\Model $record */
                foreach ($records as $record) {
                    if (method_exists($record, 'translations')) {
                        DB::table($record->getTable())
                            ->where($record->getKeyName(), $record->getKey())
                            ->update(['deleted_at' => null]);

                        $translations = $record->translations()->withTrashed()->get();
                        foreach ($translations as $translation) {
                            if ($translation->trashed()) {
                                $translation->restored_at = now();
                                if (method_exists($translation, 'deletedBy')) {
                                    $translation->deletedBy()->dissociate();
                                }
                                $translation->translation_status = 'draft';
                                if (auth()->check()) {
                                    $translation->restoredBy()->associate(auth()->user());
                                }
                                $translation->restore();
                            }
                        }
                    } else {
                        if (method_exists($record, 'restore')) {
                            if (auth()->check()) {
                                if (property_exists($record, 'restored_at') || $record->isFillable('restored_at')) {
                                    $record->setAttribute('restored_at', now());
                                }
                                if (method_exists($record, 'restoredBy')) {
                                    $record->restoredBy()->associate(auth()->user());
                                }
                                if (method_exists($record, 'deletedBy')) {
                                    $record->deletedBy()->dissociate();
                                }
                            }

                            $record->restore();
                        }
                    }
                }

                $livewire->redirect(static::getUrl('index'));
            });
    }

    public static function getDeleteBulkAction(): BulkAction
    {
        return BulkAction::make('delete')
            ->label(function ($livewire) {
                if (isset($livewire->activeTab) && in_array($livewire->activeTab, ['trash', 'deleted'])) {
                    return __('core::core.selected_records_delete_permanently');
                }

                return __('core::core.selected_records_delete');
            })
            ->requiresConfirmation()
            ->color('danger')
            ->action(function ($records, $livewire): void {
                $isTrashedTab = isset($livewire->activeTab) && in_array($livewire->activeTab, ['trash', 'deleted']);

                if ($isTrashedTab) {
                    foreach ($records as $record) {
                        $record->forceDelete();
                    }

                    Notification::make()
                        ->title(__('core::core.records_permanently_deleted'))
                        ->success()
                        ->send();

                    $livewire->redirect(static::getUrl('index', ['tab' => 'deleted']));
                } else {
                    $hasSoftDeletes = false;
                    $hasTranslations = false;

                    foreach ($records as $record) {
                        if (method_exists($record, 'translations')) {
                            $hasTranslations = true;
                            if (auth()->check()) {
                                $translations = $record->translations()->withTrashed()->get();
                                foreach ($translations as $translation) {
                                    $translation->deletedBy()->associate(auth()->user());
                                    $translation->translation_status = 'deleted';

                                    if (isset($translation->restored_at)) {
                                        $translation->restored_at = null;
                                    }
                                    if (method_exists($translation, 'restoredBy')) {
                                        $translation->restoredBy()->dissociate();
                                    }

                                    $translation->save();
                                }
                            }
                        } else {
                            if (method_exists($record, 'trashed')) {
                                $hasSoftDeletes = true;
                                if (auth()->check() && is_object($record)) {
                                    $record->setAttribute('deleted_by_id', auth()->id());
                                    $record->save();
                                }
                            }
                        }

                        $record->delete();
                    }

                    if ($hasTranslations || $hasSoftDeletes) {
                        Notification::make()
                            ->title(__('core::core.records_moved_to_trash'))
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title(__('core::core.deleted'))
                            ->success()
                            ->send();
                    }
                }

                $livewire->redirect(static::getUrl('index'));
            });
    }

    public static function getSaveAction(): Action
    {
        return Action::make('save')
            ->label(__('core::core.save'))
            ->keyBindings(['command+s', 'ctrl+s'])
            ->color('success')
            ->action(function ($livewire): void {
                $livewire instanceof CreateRecord ? $livewire->create() : $livewire->save();

                $redirectParams = ['record' => $livewire->record];

                if (method_exists($livewire->record, 'translations')) {
                    $redirectParams['lang'] = $livewire->lang;
                }

                $livewire->redirect(static::getUrl('edit', $redirectParams));
            })
            ->visible(fn ($livewire): bool => $livewire instanceof CreateRecord || $livewire instanceof EditRecord)
            ->hidden(fn ($livewire): bool => $livewire instanceof EditRecord
                && $livewire->record
                && method_exists($livewire->record, 'trashed')
                && $livewire->record->trashed());
    }

    public static function getPublishAction(): Action
    {
        return Action::make('publish')
            ->label(__('core::core.publish'))
            ->keyBindings(['command+p', 'ctrl+p'])
            ->color('secondary')
            ->form(function ($livewire) {
                if ($livewire->record && method_exists($livewire->record, 'translations')) {
                    $config = config('core.draft_publish_logic', [
                        'auto_publish_single' => true,
                        'prompt_when_all_published' => true,
                        'prompt_when_any_published' => false,
                    ]);

                    $allTranslations = $livewire->record->translations()->get();
                    $translationCount = $allTranslations->count();

                    if ($translationCount > 1) {
                        $currentLocale = $livewire->lang ?? app()->getLocale();
                        $publishedCount = $allTranslations->where('translation_status', 'published')->count();

                        $currentTranslation = $allTranslations->where('locale', $currentLocale)->first();
                        if ($currentTranslation && $currentTranslation->translation_status !== 'published') {
                            $publishedCount++; // This one will be published
                        }

                        $shouldAsk = false;

                        if ($config['prompt_when_all_published'] && $publishedCount === $translationCount && is_object($livewire->record) && $livewire->record->status !== 'published') {
                            $shouldAsk = true;
                        }

                        if ($config['prompt_when_any_published'] && $publishedCount > 0 && is_object($livewire->record) && $livewire->record->status !== 'published') {
                            $shouldAsk = true;
                        }

                        if ($shouldAsk) {
                            return [
                                Checkbox::make('publish_main_entry')
                                    ->label(__('core::core.publish_main_entry'))
                                    ->helperText(__('core::core.publish_main_entry_description'))
                                    ->default(true),
                            ];
                        }
                    }
                }

                return [];
            })
            ->action(function ($livewire, array $data): void {
                $livewire->data['translation_status'] = 'published';
                $livewire->save();

                if (method_exists($livewire->record, 'translateOrNew')) {
                    $locale = $livewire->lang ?? app()->getLocale();
                    $translation = $livewire->record->translateOrNew($locale);
                    $translation->published_at = now();
                    $translation->publishedBy()->associate(auth()->user());
                    $translation->to_publish_at = null;
                    $translation->unpublished_at = null;
                    $translation->to_unpublish_at = null;
                    $translation->save();
                }

                if (isset($data['publish_main_entry']) && $data['publish_main_entry'] && $livewire->record) {
                    $livewire->record->status = 'published';
                    $livewire->record->save();
                }

                $url = static::getUrl('view', ['record' => $livewire->record]);
                if (isset($livewire->lang)) {
                    $url .= '?lang='.$livewire->lang;
                }
                $livewire->redirect($url);
            })
            ->visible(fn ($livewire): bool => $livewire instanceof EditRecord)
            ->hidden(fn ($get, $livewire) => $get('translation_status') === 'published'
                || ($livewire instanceof EditRecord
                    && $livewire->record
                    && method_exists($livewire->record, 'trashed')
                    && $livewire->record->trashed()))
            ->requiresConfirmation()
            ->modalDescription(__('core::core.publish_modal_description'));
    }

    public static function getSaveAndCreateAnotherAction(): Action
    {
        return Action::make('saveAndCreateAnother')
            ->label(__('core::core.save_and_create_another'))
            ->color('secondary')
            ->button()
            ->action(function ($livewire): void {
                $livewire instanceof CreateRecord ? $livewire->create() : $livewire->save();

                $url = static::getUrl('create');
                if (isset($livewire->lang)) {
                    $url .= '?lang='.$livewire->lang;
                }
                $livewire->redirect($url);
            })
            ->visible(fn ($livewire): bool => $livewire instanceof CreateRecord);
    }

    public static function getCancelAction(): Action
    {
        return Action::make('cancel')
            ->label(__('core::core.cancel'))
            ->keyBindings(['escape'])
            ->color('secondary')
            ->outlined()
            ->url(function ($livewire) {
                if ($livewire instanceof EditRecord) {
                    $viewParams = ['record' => $livewire->record];

                    if (method_exists($livewire->record, 'translations')) {
                        $currentLang = $livewire->lang ?? request()->query('lang') ?? app()->getLocale();
                        $translation = $livewire->record->translations()->where('locale', $currentLang)->first();

                        if ($translation) {
                            $viewParams['lang'] = $currentLang;
                        } else {
                            $viewParams['lang'] = app()->getLocale();
                        }
                    }

                    return static::getUrl('view', $viewParams);
                }

                return static::getUrl('index');
            });
    }

    public static function getDeleteAction(): Action
    {
        return Action::make('delete')
            ->label(function ($livewire) {
                $currentLang = $livewire->lang ?? request()->query('lang') ?? app()->getLocale();

                if (method_exists($livewire->record, 'translations')) {
                    $translation = $livewire->record->translations()->withTrashed()->where('locale', $currentLang)->first();
                    if ($translation && $translation->trashed()) {
                        return __('core::core.delete_permanently');
                    }
                } elseif (
                    $livewire->record
                    && method_exists($livewire->record, 'trashed')
                    && $livewire->record->trashed()
                ) {
                    return __('core::core.delete_permanently');
                }

                return __('core::core.delete');
            })
            ->color('danger')
            ->outlined()
            ->action(function ($livewire): void {
                $currentLang = $livewire->lang ?? request()->query('lang') ?? app()->getLocale();

                if ($currentLang && method_exists($livewire->record, 'translations')) {
                    $translation = $livewire->record->translations()->withTrashed()->where('locale', $currentLang)->first();

                    if ($translation && $translation->trashed()) {
                        $remainingTranslations = $livewire->record->translations()->withTrashed()->where('locale', '!=', $currentLang)->count();

                        if ($remainingTranslations === 0 && is_object($livewire->record)) {
                            $livewire->record->forceDelete();
                        } else {
                            $translation->forceDelete();
                        }

                        Notification::make()
                            ->title(__('core::core.record_permanently_deleted'))
                            ->success()
                            ->send();

                        $livewire->redirect(static::getUrl('index'));

                        return;
                    }
                }

                if (is_object($livewire->record) && method_exists($livewire->record, 'trashed') && $livewire->record->trashed()) {
                    $livewire->record->forceDelete();

                    Notification::make()
                        ->title(__('core::core.record_permanently_deleted'))
                        ->success()
                        ->send();

                    $livewire->redirect(static::getUrl('index'));

                    return;
                }

                if (method_exists($livewire->record, 'translations')) {
                    $translation = $livewire->record->translations()->where('locale', $currentLang)->first();

                    if ($translation) {
                        if (auth()->check()) {
                            $translation->deletedBy()->associate(auth()->user());
                            $translation->translation_status = 'deleted';

                            if (isset($translation->restored_at)) {
                                $translation->restored_at = null;
                            }
                            if (method_exists($translation, 'restoredBy')) {
                                $translation->restoredBy()->dissociate();
                            }

                            $translation->save();
                        }
                        $translation->delete();

                        if (is_object($livewire->record)) {
                            $livewire->record->checkAndDeleteIfAllTranslationsDeleted();
                        }

                        Notification::make()
                            ->title(__('core::core.record_moved_to_trash'))
                            ->success()
                            ->send();

                        $livewire->redirect(static::getUrl('index'));
                    }
                } else {
                    if (method_exists($livewire->record, 'trashed')) {
                        if (auth()->check() && is_object($livewire->record)) {
                            $livewire->record->deletedBy()->associate(auth()->user());
                            $livewire->record->save();
                        }

                        if (is_object($livewire->record)) {
                            $livewire->record->delete();
                        }

                        Notification::make()
                            ->title(__('core::core.record_moved_to_trash'))
                            ->success()
                            ->send();
                    } else {
                        if (is_object($livewire->record)) {
                            $livewire->record->delete();
                        }

                        Notification::make()
                            ->title(__('core::core.deleted'))
                            ->success()
                            ->send();
                    }

                    $livewire->redirect(static::getUrl('index'));
                }
            })
            ->visible(function ($livewire): bool {
                if ($livewire instanceof EditRecord) {
                    $currentLang = $livewire->lang ?? request()->query('lang') ?? app()->getLocale();

                    if (method_exists($livewire->record, 'translations')) {
                        $translation = $livewire->record->translations()->withTrashed()->where('locale', $currentLang)->first();

                        return $translation && ! $translation->trashed();
                    }

                    return method_exists($livewire->record, 'trashed') ? ! $livewire->record->trashed() : true;
                }

                if ($livewire instanceof ViewRecord) {
                    $currentLang = $livewire->lang ?? request()->query('lang') ?? app()->getLocale();

                    if (method_exists($livewire->record, 'translations')) {
                        $translation = $livewire->record->translations()->withTrashed()->where('locale', $currentLang)->first();

                        return $translation && $translation->trashed();
                    }

                    return method_exists($livewire->record, 'trashed') ? $livewire->record->trashed() : false;
                }

                return false;
            })
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

                $currentLang = $livewire->lang ?? request()->query('lang') ?? app()->getLocale();

                if (method_exists($livewire->record, 'translations')) {
                    $translation = $livewire->record->translations()->withTrashed()->where('locale', $currentLang)->first();

                    return $translation && ! $translation->trashed();
                }

                return method_exists($livewire->record, 'trashed') ? ! $livewire->record->trashed() : true;
            });
    }

    public static function getRestoreAction(): RestoreAction
    {
        return RestoreAction::make('restore')
            ->label(__('core::core.restore'))
            ->color('success')
            ->action(function ($record, $livewire) {
                $currentLang = $livewire->lang ?? request()->query('lang') ?? app()->getLocale();

                if ($currentLang && method_exists($record, 'translations')) {
                    $translation = $record->translations()->withTrashed()->where('locale', $currentLang)->first();

                    if ($translation && $translation->trashed()) {
                        $translation->restored_at = now();
                        if (method_exists($translation, 'deletedBy')) {
                            $translation->deletedBy()->dissociate();
                        }
                        $translation->translation_status = 'draft';
                        if (auth()->check()) {
                            $translation->restoredBy()->associate(auth()->user());
                        }
                        $translation->restore();

                        if (is_object($record)) {
                            $isMainModelTrashed = DB::table($record->getTable())
                                ->where('id', $record->id)
                                ->whereNotNull('deleted_at')
                                ->exists();

                            if ($isMainModelTrashed) {
                                DB::table($record->getTable())
                                    ->where('id', $record->id)
                                    ->update(['deleted_at' => null]);
                            }
                        }

                        $livewire->redirect(static::getUrl('index'));
                    }
                } else {
                    if (method_exists($record, 'restore')) {
                        if (auth()->check() && is_object($record)) {
                            if (property_exists($record, 'restored_at') || $record->isFillable('restored_at')) {
                                $record->setAttribute('restored_at', now());
                            }
                            if (method_exists($record, 'restoredBy')) {
                                $record->restoredBy()->associate(auth()->user());
                            }
                            if (method_exists($record, 'deletedBy')) {
                                $record->deletedBy()->dissociate();
                            }
                        }

                        if (is_object($record)) {
                            $record->restore();
                        }
                    }
                    $livewire->redirect(static::getUrl('index'));
                }
            })
            ->visible(function ($livewire) {
                if (! $livewire instanceof ViewRecord || ! $livewire->record) {
                    return false;
                }

                $currentLang = $livewire->lang ?? request()->query('lang') ?? app()->getLocale();

                if (method_exists($livewire->record, 'translations')) {
                    $translation = $livewire->record->translations()->withTrashed()->where('locale', $currentLang)->first();

                    return $translation && $translation->trashed();
                }

                return method_exists($livewire->record, 'trashed') ? $livewire->record->trashed() : false;
            });
    }

    public static function getAuthorSelect(): MorphToSelect
    {
        return MorphToSelect::make('author')
            ->label(__('core::core.author'))
            ->types(static::getAuthorTypes())
            ->searchable()
            ->preload();
    }

    /**
     * Get available author types for MorphToSelect from config
     * Override this method in your resource to customize available user types
     */
    protected static function getAuthorTypes(): array
    {
        $types = [];

        if (method_exists(static::class, 'getEntityType')) {
            $entityType = static::getEntityType();
            $userModels = config("{$entityType}.user_models", []);

            foreach ($userModels as $userModel => $config) {
                if (class_exists($userModel)) {
                    $titleAttribute = $config['title_attribute'] ?? 'name';

                    $types[] = MorphToSelect\Type::make($userModel)
                        ->titleAttribute($titleAttribute)
                        ->label($config['label'] ?? class_basename($userModel))
                        ->getOptionLabelUsing(fn ($record): string => (string) ($record->{$titleAttribute} ?? 'Unknown'))
                        ->getSearchResultsUsing(
                            fn (string $search) => $userModel::query()
                                ->where($titleAttribute, 'like', "%{$search}%")
                                ->whereNotNull($titleAttribute)
                                ->where($titleAttribute, '!=', '')
                                ->limit(50)
                                ->pluck($titleAttribute, 'id')
                                ->toArray()
                        )
                        ->getOptionsUsing(
                            fn () => $userModel::query()
                                ->whereNotNull($titleAttribute)
                                ->where($titleAttribute, '!=', '')
                                ->limit(50)
                                ->pluck($titleAttribute, 'id')
                                ->toArray()
                        );
                }
            }
        }

        if (empty($types)) {
            $types[] = MorphToSelect\Type::make(\App\Models\User::class)
                ->titleAttribute('name')
                ->label('User')
                ->getOptionLabelUsing(fn ($record): string => (string) ($record->name ?? 'Unknown'))
                ->getSearchResultsUsing(
                    fn (string $search) => \App\Models\User::query()
                        ->where('name', 'like', "%{$search}%")
                        ->whereNotNull('name')
                        ->where('name', '!=', '')
                        ->limit(50)
                        ->pluck('name', 'id')
                        ->toArray()
                )
                ->getOptionsUsing(
                    fn () => \App\Models\User::query()
                        ->whereNotNull('name')
                        ->where('name', '!=', '')
                        ->limit(50)
                        ->pluck('name', 'id')
                        ->toArray()
                );
        }

        return $types;
    }

    /**
     * Get the User model class for this resource
     * Override this method in your resource to use a different User model
     */
    protected static function getUserModelClass(): string
    {
        if (method_exists(static::class, 'getEntityType')) {
            $entityType = static::getEntityType();
            if ($entityType && config("{$entityType}.user_model")) {
                return config("{$entityType}.user_model");
            }
        }

        return config('auth.providers.users.model', \App\Models\User::class);
    }

    /**
     * Get ID copyable field
     */
    public static function getIdCopyableField(): CopyableField
    {
        return CopyableField::make('id')
            ->label('ID')
            ->defaultValue(fn ($record): string => $record->id ?? '');
    }

    /**
     * Get UUID copyable field
     */
    public static function getUuidCopyableField(): CopyableField
    {
        return CopyableField::make('uuid')
            ->label('UUID')
            ->defaultValue(fn ($record): string => $record->uuid ?? '');
    }

    /**
     * Get ULID copyable field
     */
    public static function getUlidCopyableField(): CopyableField
    {
        return CopyableField::make('ulid')
            ->label('ULID')
            ->defaultValue(fn ($record): string => $record->ulid ?? '');
    }

    /**
     * Get standard copyable fields
     */
    public static function getStandardCopyableFields(): array
    {
        return [
            static::getIdCopyableField(),
            static::getUuidCopyableField(),
            static::getUlidCopyableField(),
        ];
    }

    /**
     * Get created at text entry
     */
    public static function getCreatedAtTextEntry(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('core::core.created_at'))
            ->state(fn ($record): string => $record->created_at ?
                $record->created_at.' - '.$record->created_at->diffForHumans() : '');
    }

    /**
     * Get updated at text entry
     */
    public static function getUpdatedAtTextEntry(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('core::core.updated_at'))
            ->state(fn ($record): string => $record->updated_at ?
                $record->updated_at.' - '.$record->updated_at->diffForHumans() : '');
    }
}
