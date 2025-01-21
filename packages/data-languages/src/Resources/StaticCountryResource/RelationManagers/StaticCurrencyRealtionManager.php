<?php

namespace Moox\DataLanguages\Resources\StaticCountryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Moox\DataLanguages\Models\StaticCountriesStaticCurrencies;
use Moox\DataLanguages\Models\StaticCountry;
use Moox\DataLanguages\Models\StaticCurrency;

class StaticCurrencyRealtionManager extends RelationManager
{
    protected static string $relationship = 'currencies';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label(__('data-languages::static-currency.code'))
                    ->maxLength(3)
                    ->required(),
                Forms\Components\TextInput::make('common_name')
                    ->label(__('data-languages::data-languages.common_name'))
                    ->required(),
                Forms\Components\TextInput::make('symbol')
                    ->label(__('data-languages::static-currency.symbol'))
                    ->maxLength(10)
                    ->nullable(),
                Forms\Components\KeyValue::make('exonyms')
                    ->label(__('data-languages::data-languages.exonyms'))
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\IconColumn::make('is_primary')
                    ->label(__('data-languages::static-countries-static-currencies.is_primary'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('symbol')
                    ->label(__('data-languages::static-countries-static-currencies.currency_symbol'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('common_name')
                    ->label(__('data-languages::static-countries-static-currencies.currency_name'))
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
