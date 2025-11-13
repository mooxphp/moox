<?php

namespace Moox\Data\Filament\Resources\StaticLanguageResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StaticLocalesRelationManager extends RelationManager
{
    protected static string $relationship = 'locales';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('language_id')
                    ->label(__('data::fields.language'))
                    ->relationship('language', 'common_name')
                    ->searchable()
                    ->preload()->required(),
                Select::make('country_id')
                    ->label(__('data::fields.country'))
                    ->relationship('country', 'common_name')
                    ->searchable()
                    ->preload()->required(),
                Toggle::make('is_official_language')
                    ->label(__('data::fields.is_official_language'))
                    ->default(false),
                TextInput::make('locale')
                    ->label(__('data::fields.locale'))
                    ->maxLength(255)->required(),
                TextInput::make('name')
                    ->label(__('data::fields.name'))
                    ->maxLength(255)->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('locale')->label(__('data::fields.locale')),
                TextColumn::make('name')->label(__('data::fields.name'))->sortable()->searchable()->toggleable(),
                IconColumn::make('is_official_language')
                    ->label(__('data::fields.is_official_language'))
                    ->boolean(),
                TextColumn::make('language.common_name')
                    ->label(__('data::fields.common_language_name'))
                    ->sortable(),
                TextColumn::make('country.common_name')
                    ->label(__('data::fields.common_country_name'))
                    ->sortable(),
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
