<?php

namespace Moox\Jobs\Resources;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Moox\Jobs\Models\FailedJob;
use Moox\Jobs\Resources\JobsFailedResource\Pages\ListFailedJobs;

class JobsFailedResource extends Resource
{
    protected static ?string $model = FailedJob::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('uuid')->disabled()->columnSpan(4)->label(__('jobs::translations.uuid')),
                TextInput::make('failed_at')->disabled()->label(__('jobs::translations.failed_at')),
                TextInput::make('id')->disabled()->label(__('jobs::translations.id')),
                TextInput::make('connection')->disabled()->label(__('jobs::translations.connection')),
                TextInput::make('queue')->disabled()->label(__('jobs::translations.queue')),

                // make text a little bit smaller because often a complete Stack Trace is shown:
                TextArea::make('exception')->disabled()->columnSpan(4)->extraInputAttributes(['style' => 'font-size: 80%;'])->label(__('jobs::translations.connection')),
                JSONEditor::make('payload')->disabled()->columnSpan(4)->label(__('jobs::translations.payload')),
            ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->sortable()->searchable()->toggleable()->label(__('jobs::translations.id')),
                TextColumn::make('failed_at')->sortable()->searchable(false)->toggleable()->label(__('jobs::translations.failed_at')),
                TextColumn::make('exception')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->wrap()
                    ->limit(200)
                    ->tooltip(fn (FailedJob $record) => "{$record->failed_at} UUID: {$record->uuid}; Connection: {$record->connection}; Queue: {$record->queue};")
                    ->label(__('jobs::translations.exception')),
                TextColumn::make('uuid')->sortable()->searchable()->toggleable(isToggledHiddenByDefault: true)->label(__('jobs::translations.uuid')),
                TextColumn::make('connection')->sortable()->searchable()->toggleable(isToggledHiddenByDefault: true)->label(__('jobs::translations.connection')),
                TextColumn::make('queue')->sortable()->searchable()->toggleable(isToggledHiddenByDefault: true)->label(__('jobs::translations.queue')),
            ])
            ->filters([])
            ->bulkActions([
                BulkAction::make('retry')
                    ->label(__('jobs::translations.retry'))
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        foreach ($records as $record) {
                            Artisan::call("queue:retry {$record->uuid}");
                        }
                        Notification::make()
                            ->title($records->count().__('jobs::translations.pushed_back_notification'))
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                DeleteAction::make('Delete')->label(__('jobs::translations.delete')),
                ViewAction::make('View'),
                Action::make('retry')
                    ->label(__('jobs::translations.retry'))
                    ->requiresConfirmation()
                    ->action(function (FailedJob $record): void {
                        Artisan::call("queue:retry {$record->uuid}");
                        Notification::make()
                            ->title(__('jobs::translations.jobs.single')." {$record->uuid} ".__('jobs::translations.job_pushed_back_notification'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFailedJobs::route('/'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            //
        ];
    }

    public static function getModelLabel(): string
    {
        return __('jobs::translations.jobs_failed.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('jobs::translations.jobs_failed.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('jobs::translations.jobs_failed.navigation_label');
    }

    public static function getBreadcrumb(): string
    {
        return __('jobs::translations.breadcrumb');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationBadge(): ?string
    {
        return number_format(static::getModel()::count());
    }

    public static function getNavigationGroup(): ?string
    {
        return __('jobs::translations.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('jobs.resources.failed_jobs.navigation_sort');
    }
}
