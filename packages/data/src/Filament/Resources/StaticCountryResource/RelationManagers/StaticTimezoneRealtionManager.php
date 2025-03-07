<?php

namespace Moox\Data\Filament\Resources\StaticCountryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StaticTimezoneRealtionManager extends RelationManager
{
    protected static string $relationship = 'timezones';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('data::fields.name'))
                    ->maxLength(255)
                    ->required(),
                Forms\Components\TextInput::make('offset_standard')
                    ->label(__('data::fields.offset_standard'))
                    ->maxLength(6)->required(),
                Forms\Components\Toggle::make('dst')
                    ->label(__('data::fields.dst'))->required(),
                Forms\Components\DateTimePicker::make('dst_start')
                    ->label(__('data::fields.dst_start')),
                Forms\Components\DateTimePicker::make('dst_end')
                    ->label(__('data::fields.dst_end')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('data::fields.name')),
                Tables\Columns\TextColumn::make('offset_standard')
                    ->label(__('data::fields.offset_standard')),
                Tables\Columns\IconColumn::make('dst')
                    ->label(__('data::fields.dst'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('dst_start')
                    ->label(__('data::fields.dst_start'))
                    ->datetime(),
                Tables\Columns\TextColumn::make('dst_end')
                    ->label(__('data::fields.dst_end'))
                    ->datetime(),
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
