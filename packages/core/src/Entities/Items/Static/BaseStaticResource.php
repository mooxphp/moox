<?php

declare(strict_types=1);

namespace Moox\Core\Entities\Items\Static;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Actions;
use Moox\Core\Entities\BaseResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;

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

                if (method_exists($record, 'translations')) {
                    $translation = $record->translations()->where('locale', $currentLang)->first();

                    return $translation ? __('core::core.edit') : __('core::core.create');
                }

                return __('core::core.edit');
            })
            ->icon(function ($record, $livewire) {
                $currentLang = $livewire->lang ?? request()->query('lang') ?? app()->getLocale();

                if (method_exists($record, 'translations')) {
                    $translation = $record->translations()->where('locale', $currentLang)->first();

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

                return $record->translations()->where('locale', $currentLang)->doesntExist();
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

                return $livewire->record->translations()->where('locale', $currentLang)->exists();
            });
    }
}
