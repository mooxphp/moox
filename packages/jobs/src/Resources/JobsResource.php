<?php

namespace Moox\Jobs\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Moox\Jobs\JobsPlugin;
use Moox\Jobs\Models\JobManager;
use Moox\Jobs\Resources\JobsResource\Pages\ListJobs;
use Moox\Jobs\Resources\JobsResource\Widgets\JobStatsOverview;

class JobsResource extends Resource
{
    protected static ?string $model = JobManager::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('job_id')
                    ->required()
                    ->maxLength(255),
                TextInput::make('name')
                    ->maxLength(255),
                TextInput::make('queue')
                    ->maxLength(255),
                DateTimePicker::make('started_at'),
                DateTimePicker::make('finished_at'),
                Toggle::make('failed')
                    ->required(),
                TextInput::make('attempt')
                    ->required(),
                Textarea::make('exception_message')
                    ->maxLength(65535),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->deferLoading()
            ->columns([
                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->label(__('jobs::translations.status'))
                    ->formatStateUsing(fn (string $state): string => __("jobs::translations.{$state}"))
                    ->color(fn (string $state): string => match ($state) {
                        'running' => 'primary',
                        'succeeded' => 'success',
                        'failed' => 'danger',
                        default => 'secondary',
                    }),
                TextColumn::make('name')
                    ->label(__('jobs::translations.name'))
                    ->sortable(),
                TextColumn::make('queue')
                    ->label(__('jobs::translations.queue'))
                    ->sortable(),
                TextColumn::make('progress')
                    ->label(__('jobs::translations.progress'))
                    ->formatStateUsing(fn (string $state) => "{$state}%")
                    ->sortable(),
                // ProgressColumn::make('progress')->label(__('jobs::translations.progress'))->color('warning'),
                TextColumn::make('started_at')
                    ->label(__('jobs::translations.started_at'))
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->bulkActions([
                DeleteBulkAction::make(),
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
            'index' => ListJobs::route('/'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            JobStatsOverview::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return JobsPlugin::make()->getNavigationCountBadge() ? number_format(static::getModel()::count()) : null;
    }

    public static function getModelLabel(): string
    {
        return JobsPlugin::make()->getLabel();
    }

    public static function getPluralModelLabel(): string
    {
        return JobsPlugin::make()->getPluralLabel();
    }

    public static function getNavigationLabel(): string
    {
        return Str::title(static::getPluralModelLabel());
    }

    public static function getNavigationGroup(): ?string
    {
        return JobsPlugin::make()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return JobsPlugin::make()->getNavigationSort();
    }

    public static function getBreadcrumb(): string
    {
        return JobsPlugin::make()->getBreadcrumb();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return JobsPlugin::make()->shouldRegisterNavigation();
    }

    public static function getNavigationIcon(): string
    {
        return JobsPlugin::make()->getNavigationIcon();
    }
}
