<?php

namespace Moox\Bpmn\Resources\Bpmns\Tables;


use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;

class BpmnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                    TextColumn::make('title')
                    ->searchable(),
                    TextColumn::make('description')
                    ->searchable(),
                    TextColumn::make('status')
                    ->searchable(),
                    TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),#
                    
                    
                //
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
