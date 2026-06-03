<?php

declare(strict_types=1);

namespace Moox\Transform\Filament\Resources\TransformDefinitionResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransformRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'records';

    protected static ?string $title = 'Records';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('transform::fields.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('validation_status')
                    ->label(__('transform::fields.validation_status'))
                    ->badge()
                    ->sortable(),
                IconColumn::make('degraded')
                    ->label(__('transform::fields.degraded'))
                    ->boolean(),
                TextColumn::make('attempts')
                    ->label(__('transform::fields.attempts'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ]);
    }
}
