<?php

namespace Moox\Restore\Resources\Widgets;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Moox\Restore\Models\RestoreBackup;

class FailedRestoreOverview extends BaseWidget
{
    protected static ?string $heading = 'Failed Restores';

    protected int|string|array $columnSpan = [
        'sm' => 3,
        'md' => 6,
        'xl' => 12,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(RestoreBackup::where('status', 'failed'))
            ->columns([
                IconColumn::make('status')
                    ->label(__('restore::translations.status'))
                    ->icon(fn (string $state): string => match ($state) {
                        'heroicon-o-question-mark-circle',
                        'failed' => 'heroicon-o-x-circle',
                    })
                    ->colors([
                        'secondary',
                        'danger' => 'failed',
                    ]),
                TextColumn::make('restoreDestination.host')->label(__('restore::translations.name')),
                TextColumn::make('message')->label(__('restore::translations.message')),
                TextColumn::make('created_at')->label(__('restore::translations.created-at'))->date()->timezone('UTC'),
            ]);
    }
}
