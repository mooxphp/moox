<?php

namespace Moox\Jobs\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Moox\Jobs\JobsBatchesPlugin;
use Moox\Jobs\Models\JobBatch;
use Moox\Jobs\Resources\JobBatchesResource\Pages\ListJobBatches;

class JobBatchesResource extends Resource
{
    protected static ?string $model = JobBatch::class;

    public static function getNavigationBadge(): ?string
    {
        return JobsBatchesPlugin::get()->getNavigationCountBadge() ? number_format(static::getModel()::count()) : null;
    }

    public static function getModelLabel(): string
    {
        return JobsBatchesPlugin::get()->getLabel();
    }

    public static function getPluralModelLabel(): string
    {
        return JobsBatchesPlugin::get()->getPluralLabel();
    }

    public static function getNavigationLabel(): string
    {
        return Str::title(static::getPluralModelLabel()) ?? Str::title(static::getModelLabel());
    }

    public static function getNavigationGroup(): ?string
    {
        return JobsBatchesPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return JobsBatchesPlugin::get()->getNavigationSort();
    }

    public static function getBreadcrumb(): string
    {
        return JobsBatchesPlugin::get()->getBreadcrumb();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return JobsBatchesPlugin::get()->shouldRegisterNavigation();
    }

    public static function getNavigationIcon(): string
    {
        return JobsBatchesPlugin::get()->getNavigationIcon();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable()->searchable()->toggleable(),
                TextColumn::make('id')->sortable()->searchable()->toggleable(),
                TextColumn::make('name')->sortable()->searchable()->toggleable(),
                TextColumn::make('cancelled_at')->dateTime()->sortable()->searchable()->toggleable(),
                TextColumn::make('failed_at')->dateTime()->sortable()->searchable()->toggleable(),
                TextColumn::make('finished_at')->dateTime()->sortable()->searchable()->toggleable(),
                TextColumn::make('total_jobs')->sortable()->searchable()->toggleable(),
                TextColumn::make('pending_jobs')->sortable()->searchable()->toggleable(),
                TextColumn::make('failed_jobs')->sortable()->searchable()->toggleable(),
                TextColumn::make('failed_job_ids')->sortable()->searchable()->toggleable(),
            ])
            ->actions([
                DeleteAction::make('Delete'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobBatches::route('/'),
        ];
    }
}
