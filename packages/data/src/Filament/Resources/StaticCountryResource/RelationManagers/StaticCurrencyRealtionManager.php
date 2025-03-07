<?php

namespace Moox\Data\Filament\Resources\StaticCountryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StaticCurrencyRealtionManager extends RelationManager
{
    protected static string $relationship = 'currencies';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label(__('data::fields.code'))
                    ->maxLength(3)
                    ->required(),
                Forms\Components\TextInput::make('common_name')
                    ->label(__('data::fields.common_name'))
                    ->required(),
                Forms\Components\TextInput::make('symbol')
                    ->label(__('data::fields.symbol'))
                    ->maxLength(10)
                    ->nullable(),
                Forms\Components\KeyValue::make('exonyms')
                    ->label(__('data::fields.exonyms'))
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\IconColumn::make('is_primary')
                    ->label(__('data::fields.is_primary'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('symbol')
                    ->label(__('data::fields.currency_symbol'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('common_name')
                    ->label(__('data::fields.currency_name'))
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
