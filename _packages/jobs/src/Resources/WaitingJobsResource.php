<?php

namespace Adrolli\FilamentJobManager\Resources;

use Adrolli\FilamentJobManager\FilamentWaitingJobsPlugin;
use Adrolli\FilamentJobManager\Models\Job;
use Adrolli\FilamentJobManager\Resources\WaitingJobsResource\Pages\ListJobsWaiting;
use Adrolli\FilamentJobManager\Resources\WaitingJobsResource\Widgets\JobsWaitingOverview;
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

class WaitingJobsResource extends Resource
{
    protected static ?string $model = Job::class;

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
            ->columns([
                TextColumn::make('status')
                    ->badge()
                    ->label(__('filament-job-manager::translations.status'))
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => __("filament-job-manager::translations.{$state}"))
                    ->color(fn (string $state): string => match ($state) {
                        'running' => 'primary',
                        'waiting' => 'success',
                        'failed' => 'danger',
                    }),
                TextColumn::make('display_name')
                    ->label(__('filament-job-manager::translations.name'))
                    ->sortable(),
                TextColumn::make('queue')
                    ->label(__('filament-job-manager::translations.queue'))
                    ->sortable(),
                TextColumn::make('attempts')
                    ->label(__('filament-job-manager::translations.attempts'))
                    ->sortable(),
                TextColumn::make('reserved_at')
                    ->label(__('filament-job-manager::translations.created_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('filament-job-manager::translations.created_at'))
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('id', 'asc')
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
            'index' => ListJobsWaiting::route('/'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            JobsWaitingOverview::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return FilamentWaitingJobsPlugin::get()->getNavigationCountBadge() ? number_format(static::getModel()::count()) : null;
    }

    public static function getModelLabel(): string
    {
        return FilamentWaitingJobsPlugin::get()->getLabel();
    }

    public static function getPluralModelLabel(): string
    {
        return FilamentWaitingJobsPlugin::get()->getPluralLabel();
    }

    public static function getNavigationLabel(): string
    {
        return Str::title(static::getPluralModelLabel()) ?? Str::title(static::getModelLabel());
    }

    public static function getNavigationGroup(): ?string
    {
        return FilamentWaitingJobsPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return FilamentWaitingJobsPlugin::get()->getNavigationSort();
    }

    public static function getBreadcrumb(): string
    {
        return FilamentWaitingJobsPlugin::get()->getBreadcrumb();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return FilamentWaitingJobsPlugin::get()->shouldRegisterNavigation();
    }

    public static function getNavigationIcon(): string
    {
        return FilamentWaitingJobsPlugin::get()->getNavigationIcon();
    }
}
