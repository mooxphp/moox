<?php

declare(strict_types=1);

namespace Moox\Connect\Filament\Resources\ApiEndpointResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ApiImportRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'importRecords';

    protected static ?string $title = 'Import Records';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('external_key')
                    ->label('External Key')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('payload')
                    ->label('Payload')
                    ->toggleable()
                    ->formatStateUsing(function ($state): string {
                        if (empty($state)) {
                            return '';
                        }
                        $json = json_encode($state, JSON_PRETTY_PRINT);
                        if (mb_strlen($json) > 800) {
                            return mb_substr($json, 0, 800).'…';
                        }

                        return $json;
                    }),
                TextColumn::make('sync_batch_id')
                    ->label('Sync Batch')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'new' => 'new',
                        'processed' => 'processed',
                        'failed' => 'failed',
                    ]),
            ]);
    }
}
