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
use Illuminate\Support\Str;
use InvadersXX\FilamentJsoneditor\Forms\JSONEditor;
use Moox\Jobs\JobsFailedPlugin;
use Moox\Jobs\Models\FailedJob;
use Moox\Jobs\Resources\JobsFailedResource\Pages\ListFailedJobs;

class JobsFailedResource extends Resource
{
    protected static ?string $model = FailedJob::class;

    public static function getNavigationBadge(): ?string
    {
        return JobsFailedPlugin::make()->getNavigationCountBadge() ? number_format(static::getModel()::count()) : null;
    }

    public static function getModelLabel(): string
    {
        return JobsFailedPlugin::make()->getLabel();
    }

    public static function getPluralModelLabel(): string
    {
        return JobsFailedPlugin::make()->getPluralLabel();
    }

    public static function getNavigationLabel(): string
    {
        return Str::title(static::getPluralModelLabel());
    }

    public static function getNavigationGroup(): ?string
    {
        return JobsFailedPlugin::make()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return JobsFailedPlugin::make()->getNavigationSort();
    }

    public static function getBreadcrumb(): string
    {
        return JobsFailedPlugin::make()->getBreadcrumb();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return JobsFailedPlugin::make()->shouldRegisterNavigation();
    }

    public static function getNavigationIcon(): string
    {
        return JobsFailedPlugin::make()->getNavigationIcon();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('uuid')->disabled()->columnSpan(4),
                TextInput::make('failed_at')->disabled(),
                TextInput::make('id')->disabled(),
                TextInput::make('connection')->disabled(),
                TextInput::make('queue')->disabled(),

                // make text a little bit smaller because often a complete Stack Trace is shown:
                TextArea::make('exception')->disabled()->columnSpan(4)->extraInputAttributes(['style' => 'font-size: 80%;']),
                JSONEditor::make('payload')->disabled()->columnSpan(4),
            ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->sortable()->searchable()->toggleable(),
                TextColumn::make('failed_at')->sortable()->searchable(false)->toggleable(),
                TextColumn::make('exception')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->wrap()
                    ->limit(200)
                    ->tooltip(fn (FailedJob $record) => "{$record->failed_at} UUID: {$record->uuid}; Connection: {$record->connection}; Queue: {$record->queue};"),
                TextColumn::make('uuid')->sortable()->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('connection')->sortable()->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('queue')->sortable()->searchable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->bulkActions([
                BulkAction::make('retry')
                    ->label('Retry')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        foreach ($records as $record) {
                            Artisan::call("queue:retry {$record->uuid}");
                        }
                        Notification::make()
                            ->title("{$records->count()} jobs have been pushed back onto the queue.")
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                DeleteAction::make('Delete'),
                ViewAction::make('View'),
                Action::make('retry')
                    ->label('Retry')
                    ->requiresConfirmation()
                    ->action(function (FailedJob $record): void {
                        Artisan::call("queue:retry {$record->uuid}");
                        Notification::make()
                            ->title("The job with uuid '{$record->uuid}' has been pushed back onto the queue.")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFailedJobs::route('/'),
        ];
    }
}
