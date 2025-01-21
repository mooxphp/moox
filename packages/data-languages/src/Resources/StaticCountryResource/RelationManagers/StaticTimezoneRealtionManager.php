<?php

namespace Moox\DataLanguages\Resources\StaticCountryResource\RelationManagers;

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
                    ->label(__('data-languages::static-timezone.name'))
                    ->maxLength(255)
                    ->required(),
                Forms\Components\TextInput::make('offset_standard')
                    ->label(__('data-languages::static-timezone.offset_standard'))
                    ->maxLength(6)->required(),
                Forms\Components\Toggle::make('dst')
                    ->label(__('data-languages::static-timezone.dst'))->required(),
                Forms\Components\DateTimePicker::make('dst_start')
                    ->label(__('data-languages::static-timezone.dst_start')),
                Forms\Components\DateTimePicker::make('dst_end')
                    ->label(__('data-languages::static-timezone.dst_end')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('data-languages::static-timezone.name')),
                Tables\Columns\TextColumn::make('offset_standard')
                    ->label(__('data-languages::static-timezone.offset_standard')),
                Tables\Columns\IconColumn::make('dst')
                    ->label(__('data-languages::static-timezone.dst'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('dst_start')
                    ->label(__('data-languages::static-timezone.dst_start'))
                    ->datetime(),
                Tables\Columns\TextColumn::make('dst_end')
                    ->label(__('data-languages::static-timezone.dst_end'))
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
