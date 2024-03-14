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
use Moox\Jobs\Models\Job;
use Moox\Jobs\Resources\JobsWaitingResource\Pages\ListJobsWaiting;
use Moox\Jobs\Resources\JobsWaitingResource\Widgets\JobsWaitingOverview;

class JobsWaitingResource extends Resource
{
    protected static ?string $model = Job::class;

    protected static ?string $navigationIcon = 'heroicon-o-pause';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('job_id')
                    ->required()
                    ->maxLength(255)
                    ->label(__('jobs::translations.id')),
                TextInput::make('name')
                    ->maxLength(255)
                    ->label(__('jobs::translations.name')),
                TextInput::make('queue')
                    ->maxLength(255),
                DateTimePicker::make('started_at')
                    ->label(__('jobs::translations.started_at')),
                DateTimePicker::make('finished_at')
                    ->label(__('jobs::translations.finished_at')),
                Toggle::make('failed')
                    ->required()
                    ->label(__('jobs::translations.failed')),
                TextInput::make('attempt')
                    ->required(),
                Textarea::make('exception_message')
                    ->maxLength(65535)
                    ->label(__('jobs::translations.exception_message')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('status')
                    ->badge()
                    ->label(__('jobs::translations.status'))
                    ->formatStateUsing(fn (string $state): string => __("jobs::translations.{$state}"))
                    ->color(fn (string $state): string => match ($state) {
                        'running' => 'primary',
                        'waiting' => 'success',
                        'failed' => 'danger',
                        default => 'secondary',
                    }),
                TextColumn::make('display_name')
                    ->label(__('jobs::translations.name'))
                    ->sortable(),
                TextColumn::make('queue')
                    ->label(__('jobs::translations.queue'))
                    ->sortable(),
                TextColumn::make('attempts')
                    ->label(__('jobs::translations.attempts'))
                    ->sortable(),
                TextColumn::make('reserved_at')
                    ->label(__('jobs::translations.reserved_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('jobs::translations.created_at'))
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

    public static function getModelLabel(): string
    {
        return __('jobs::translations.jobs_waiting.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('jobs::translations.jobs_waiting.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('jobs::translations.jobs_waiting.navigation_label');
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
        return config('jobs.resources.jobs_waiting.navigation_sort');
    }
}
