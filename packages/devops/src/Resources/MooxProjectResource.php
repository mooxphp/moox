<?php

namespace Moox\Devops\Resources;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Moox\Devops\Jobs\DeployProjectJob;
use Moox\Devops\Models\MooxProject;
use Moox\Devops\Resources\MooxProjectResource\Pages\ListPage;
use Moox\Devops\Resources\MooxProjectResource\Widgets\MooxProjectWidgets;

class MooxProjectResource extends Resource
{
    protected static ?string $model = MooxProject::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNot('is_failover', true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->maxLength(255)
                    ->required(),
                TextInput::make('url')
                    ->maxLength(255)
                    ->required(),
                TextInput::make('server_id')
                    ->maxLength(255)
                    ->required(),
                TextInput::make('site_id')
                    ->maxLength(255)
                    ->required(),
                TextInput::make('behind')
                    ->maxLength(255),
                DateTimePicker::make('last_deployment'),
                TextInput::make('last_commit')
                    ->maxLength(255),
                TextInput::make('commit_message')
                    ->maxLength(255),
                TextInput::make('commit_author')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('3s')
            ->columns([
                IconColumn::make('deployment_status')
                    ->label('')
                    ->sortable()
                    ->icon(function ($record) {
                        $status = $record->deployment_status;

                        return match ($status) {
                            'success' => 'heroicon-o-check-circle',
                            'running' => 'heroicon-o-play-circle',
                            'failed' => 'heroicon-o-exclamation-circle',
                            default => 'heroicon-o-x-circle',
                        };
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'failed' => 'danger',
                        'running' => 'warning',
                        'success' => 'success',
                        default => 'gray',
                    })
                    ->extraAttributes(fn ($record) => $record->deployment_status === 'running' ? ['class' => 'animate-pulse'] : [])
                    ->tooltip(fn ($record) => __('devops::translations.'.($record->deployment_status ?? 'unknown'))),
                TextColumn::make('name')
                    ->label(__('devops::translations.name'))
                    ->sortable()
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('server.name')
                    ->label(__('devops::translations.server'))
                    ->sortable()
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('site_id')
                    ->label(__('devops::translations.site_id'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('commits_behind')
                    ->label(__('devops::translations.commits_behind'))
                    ->sortable()
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('last_deployment')
                    ->label(__('devops::translations.last_deployment'))
                    ->sortable()
                    ->toggleable()
                    ->since()
                    ->searchable(),
                TextColumn::make('last_commit_message')
                    ->label(__('devops::translations.last_commit_message'))
                    ->sortable()
                    ->toggleable()
                    ->searchable()
                    ->limit(30),
                TextColumn::make('last_commit_author')
                    ->label(__('devops::translations.last_commit_author'))
                    ->sortable()
                    ->toggleable()
                    ->searchable(),
            ])
            ->defaultSort('last_deployment', 'desc')
            ->filters([
                SelectFilter::make('deployment_status')
                    ->options([
                        'success' => 'Deployed successfully',
                        'running' => 'Deploying',
                        'failed' => 'Deployment failed',
                        'never' => 'Never deployed',
                    ]),
                SelectFilter::make('server')
                    ->relationship('server', 'name'),
                SelectFilter::make('last_commit_author')
                    ->label('Author')
                    ->options(MooxProject::getMooxProjectAuthorOptions()),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('deploy')
                    ->label('Deploy')
                    ->action(function ($record) {
                        DeployProjectJob::dispatch($record, auth()->user());
                        Notification::make()
                            ->title('Deploying project '.$record->name)
                            ->success()
                            ->send()
                            ->broadcast(auth()->user());
                    }),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                BulkAction::make('deploy')
                    ->requiresConfirmation()
                    ->action(
                        fn (Collection $records) => $records->each(
                            fn ($record) => DeployProjectJob::dispatch(MooxProject::find($record->getKey()), auth()->user()))),
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
            'index' => ListPage::route('/'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            // MooxProjectWidgets::class,
        ];
    }

    public static function getModelLabel(): string
    {
        return __('devops::translations.project');
    }

    public static function getPluralModelLabel(): string
    {
        return __('devops::translations.projects');
    }

    public static function getNavigationLabel(): string
    {
        return __('devops::translations.forge_projects');
    }

    public static function getBreadcrumb(): string
    {
        return __('devops::translations.projects');
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
        return __('devops::translations.navigation_group');
    }
}
