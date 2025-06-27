<?php

namespace Moox\Data\Filament\Resources\StaticCountryResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StaticTimezoneRealtionManager extends RelationManager
{
    protected static string $relationship = 'timezones';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('data::fields.name'))
                    ->maxLength(255)
                    ->required(),
                TextInput::make('offset_standard')
                    ->label(__('data::fields.offset_standard'))
                    ->maxLength(6)->required(),
                Toggle::make('dst')
                    ->label(__('data::fields.dst'))->required(),
                DateTimePicker::make('dst_start')
                    ->label(__('data::fields.dst_start')),
                DateTimePicker::make('dst_end')
                    ->label(__('data::fields.dst_end')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('data::fields.name')),
                TextColumn::make('offset_standard')
                    ->label(__('data::fields.offset_standard')),
                IconColumn::make('dst')
                    ->label(__('data::fields.dst'))
                    ->boolean(),
                TextColumn::make('dst_start')
                    ->label(__('data::fields.dst_start'))
                    ->datetime(),
                TextColumn::make('dst_end')
                    ->label(__('data::fields.dst_end'))
                    ->datetime(),
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
