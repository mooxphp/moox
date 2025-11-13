<?php

namespace Moox\Data\Filament\Resources\StaticCountryResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StaticCurrencyRealtionManager extends RelationManager
{
    protected static string $relationship = 'currencies';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label(__('data::fields.code'))
                    ->maxLength(3)
                    ->required(),
                TextInput::make('common_name')
                    ->label(__('data::fields.common_name'))
                    ->required(),
                TextInput::make('symbol')
                    ->label(__('data::fields.symbol'))
                    ->maxLength(10)
                    ->nullable(),
                KeyValue::make('exonyms')
                    ->label(__('data::fields.exonyms'))
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                IconColumn::make('is_primary')
                    ->label(__('data::fields.is_primary'))
                    ->boolean(),
                TextColumn::make('symbol')
                    ->label(__('data::fields.currency_symbol'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('common_name')
                    ->label(__('data::fields.currency_name'))
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
