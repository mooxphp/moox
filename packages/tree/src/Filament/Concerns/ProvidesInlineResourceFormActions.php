<?php

declare(strict_types=1);

namespace Moox\Tree\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;
use Moox\Tree\Contracts\HostsInlineResourceForm;

/**
 * Adapts BaseResource form actions for inline tree inspector forms (no redirect after save).
 *
 * Applied automatically via {@see \Moox\Tree\Support\TreeInlineFormResourceAdapter} — do not use on consumer resources.
 */
trait ProvidesInlineResourceFormActions
{
    public static function getSaveAction(): Action
    {
        return Action::make('save')
            ->label(__('core::core.save'))
            ->keyBindings(['command+s', 'ctrl+s'])
            ->color('success')
            ->submit('form')
            ->visible(fn ($livewire): bool => static::inlineFormHostsSaveAction($livewire))
            ->hidden(fn ($livewire): bool => static::inlineFormIsEditingRecord($livewire)
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
                            $publishedCount++;
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

                if ($livewire instanceof HostsInlineResourceForm) {
                    return;
                }

                $url = static::getUrl('view', ['record' => $livewire->record]);
                if (isset($livewire->lang)) {
                    $url .= '?lang='.$livewire->lang;
                }

                $livewire->redirect($url);
            })
            ->visible(fn ($livewire): bool => static::inlineFormIsEditingRecord($livewire))
            ->hidden(fn ($get, $livewire) => $get('translation_status') === 'published'
                || (static::inlineFormIsEditingRecord($livewire)
                    && $livewire->record
                    && method_exists($livewire->record, 'trashed')
                    && $livewire->record->trashed()))
            ->requiresConfirmation()
            ->modalDescription(__('core::core.publish_modal_description'));
    }

    public static function getCancelAction(): Action
    {
        return Action::make('cancel')
            ->label(__('core::core.cancel'))
            ->keyBindings(['escape'])
            ->color('secondary')
            ->outlined()
            ->action(function ($livewire): void {
                if ($livewire instanceof HostsInlineResourceForm) {
                    $livewire->cancelInlineResourceForm();
                }
            })
            ->url(function ($livewire) {
                if ($livewire instanceof HostsInlineResourceForm) {
                    return null;
                }

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

                        static::finishInlineResourceFormAction($livewire, static::getUrl('index'));

                        return;
                    }
                }

                if (is_object($livewire->record) && method_exists($livewire->record, 'trashed') && $livewire->record->trashed()) {
                    $livewire->record->forceDelete();

                    Notification::make()
                        ->title(__('core::core.record_permanently_deleted'))
                        ->success()
                        ->send();

                    static::finishInlineResourceFormAction($livewire, static::getUrl('index'));

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

                        static::finishInlineResourceFormAction($livewire, static::getUrl('index'));
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

                    static::finishInlineResourceFormAction($livewire, static::getUrl('index'));
                }
            })
            ->visible(function ($livewire): bool {
                if (static::inlineFormIsEditingRecord($livewire)) {
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

    protected static function inlineFormHostsSaveAction(mixed $livewire): bool
    {
        return $livewire instanceof CreateRecord
            || $livewire instanceof EditRecord
            || $livewire instanceof HostsInlineResourceForm;
    }

    protected static function inlineFormIsEditingRecord(mixed $livewire): bool
    {
        if ($livewire instanceof EditRecord) {
            return true;
        }

        if ($livewire instanceof HostsInlineResourceForm) {
            return ! $livewire->isCreatingInlineResourceRecord();
        }

        return false;
    }

    protected static function finishInlineResourceFormAction(object $livewire, string $redirectUrl): void
    {
        if ($livewire instanceof HostsInlineResourceForm) {
            $livewire->completeInlineResourceDeletion();

            return;
        }

        $livewire->redirect($redirectUrl);
    }
}
