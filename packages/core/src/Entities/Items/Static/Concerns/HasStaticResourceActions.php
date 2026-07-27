<?php

declare(strict_types=1);

namespace Moox\Core\Entities\Items\Static\Concerns;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Entities\Items\Static\BaseStaticModel;

/**
 * Static-specific action overrides (alpha2 locale resolution, no soft-delete/withTrashed).
 * Table/bulk/form action lists come from BaseItemResource.
 */
trait HasStaticResourceActions
{
    public static function getEditTableAction(): EditAction
    {
        return EditAction::make('edit')
            ->label(fn ($record, $livewire) => static::recordHasTranslationForLivewire($record, $livewire)
                ? __('core::core.edit')
                : (static::recordSupportsTranslations($record) ? __('core::core.create') : __('core::core.edit')))
            ->icon(fn ($record, $livewire) => static::recordHasTranslationForLivewire($record, $livewire)
                ? 'heroicon-o-pencil-square'
                : (static::recordSupportsTranslations($record) ? 'heroicon-o-plus' : 'heroicon-o-pencil-square'))
            ->color('primary')
            ->url(fn ($record, $livewire) => static::getUrl('edit', static::resourceUrlParams($record, $livewire)))
            ->hidden(fn ($livewire) => isset($livewire->activeTab) && $livewire->activeTab === 'deleted');
    }

    public static function getViewTableAction(): ViewAction
    {
        return ViewAction::make('view')
            ->color('secondary')
            ->url(fn ($record, $livewire) => static::getUrl('view', static::resourceUrlParams($record, $livewire)))
            ->hidden(fn ($record, $livewire) => static::recordSupportsTranslations($record)
                && ! static::recordHasTranslationForLivewire($record, $livewire));
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

                static::notifyDeletedAndRedirect($livewire);
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

                static::notifyDeletedAndRedirect($livewire);
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
            ->url(fn ($record, $livewire) => static::getUrl(
                'edit',
                static::resourceUrlParams($livewire->record, $livewire)
            ))
            ->visible(function ($livewire) {
                if (! $livewire instanceof ViewRecord || ! $livewire->record) {
                    return false;
                }

                return ! static::recordSupportsTranslations($livewire->record)
                    || static::recordHasTranslationForLivewire($livewire->record, $livewire);
            });
    }

    protected static function notifyDeletedAndRedirect($livewire): void
    {
        Notification::make()
            ->title(__('core::core.deleted'))
            ->success()
            ->send();

        $livewire->redirect(static::getUrl('index'));
    }

    protected static function livewireLang($livewire): string
    {
        return (string) ($livewire->lang ?? request()->query('lang') ?? app()->getLocale());
    }

    protected static function recordSupportsTranslations($record): bool
    {
        return is_object($record) && method_exists($record, 'translations');
    }

    protected static function recordHasTranslationForLivewire($record, $livewire): bool
    {
        if (! static::recordSupportsTranslations($record)) {
            return false;
        }

        $translationLocale = BaseStaticModel::resolveTranslationLocale(static::livewireLang($livewire));

        return $record->translations()->where('locale', $translationLocale)->exists();
    }

    /**
     * @return array{record: mixed, lang?: string}
     */
    protected static function resourceUrlParams($record, $livewire): array
    {
        $params = ['record' => $record];

        if (static::recordSupportsTranslations($record)) {
            $params['lang'] = static::livewireLang($livewire);
        }

        return $params;
    }
}
