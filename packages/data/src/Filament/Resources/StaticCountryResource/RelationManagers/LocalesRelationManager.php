<?php

namespace Moox\Data\Filament\Resources\StaticCountryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LocalesRelationManager extends RelationManager
{
    protected static string $relationship = 'locales';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('language_id')
                    ->label(__('data::fields.language'))
                    ->relationship('language', 'common_name')
                    ->searchable()
                    ->preload()->required(),
                Forms\Components\Toggle::make('is_official_language')
                    ->label(__('data::fields.is_official_language'))
                    ->default(false),
                Forms\Components\TextInput::make('locale')
                    ->label(__('data::fields.locale'))
                    ->maxLength(255)->required(),
                Forms\Components\TextInput::make('name')
                    ->label(__('data::fields.name'))
                    ->maxLength(255)->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('locale')->label(__('data::fields.locale')),
                Tables\Columns\TextColumn::make('name')->label(__('data::fields.name'))->sortable()->searchable()->toggleable(),
                Tables\Columns\IconColumn::make('is_official_language')
                    ->label(__('data::fields.is_official_language'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('language.common_name')
                    ->label(__('data::fields.common_language_name'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('country.common_name')
                    ->label(__('data::fields.common_country_name'))
                    ->sortable(),
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
