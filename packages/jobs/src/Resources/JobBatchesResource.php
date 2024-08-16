<?php

namespace Moox\Jobs\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Jobs\Models\JobBatch;
use Moox\Jobs\Resources\JobBatchesResource\Pages\ListJobBatches;

class JobBatchesResource extends Resource
{
    protected static ?string $model = JobBatch::class;

    protected static ?string $navigationIcon = 'gmdi-all-inbox';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->searchable()
                    ->toggleable()->label(__('jobs::translations.created_at')),
                TextColumn::make('id')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->label(__('jobs::translations.id')),
                TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->label(__('jobs::translations.jobs_batches.single')),
                TextColumn::make('cancelled_at')
                    ->dateTime()
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->label(__('jobs::translations.canceled_at')),
                TextColumn::make('failed_at')
                    ->dateTime()
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->label(__('jobs::translations.failed_at')),
                TextColumn::make('finished_at')
                    ->dateTime()
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->label(__('jobs::translations.finished_at')),
                TextColumn::make('total_jobs')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->label(__('jobs::translations.total_jobs')),
                TextColumn::make('pending_jobs')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->label(__('jobs::translations.pending_jobs')),
                TextColumn::make('failed_jobs')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->label(__('jobs::translations.failed_jobs')),
                TextColumn::make('failed_job_ids')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->label(__('jobs::translations.failed_job_id')),
            ])
            ->actions([
                DeleteAction::make('Delete'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return ['index' => ListJobBatches::route('/')];
    }

    public static function getWidgets(): array
    {
        return [
            //
        ];
    }

    public static function getModelLabel(): string
    {
        return __('jobs::translations.jobs_batches.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('jobs::translations.jobs_batches.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('jobs::translations.jobs_batches.navigation_label');
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
        return config('jobs.navigation_sort') + 4;
    }
}
