<?php

namespace Moox\Data\Filament\Resources\StaticTimezoneResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Forms\Components;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StaticCountriesRelationManager extends RelationManager
{
    protected static string $relationship = 'countries';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('alpha2')
                            ->label(__('data::fields.alpha2'))
                            ->maxLength(3)->required(),
                        TextInput::make('alpha3_b')
                            ->label(__('data::fields.alpha3_b'))
                            ->maxLength(3)->nullable(),
                        TextInput::make('alpha3_t')
                            ->label(__('data::fields.alpha3_t'))
                            ->maxLength(3)->nullable(),
                        TextInput::make('common_name')
                            ->label(__('data::fields.common_name'))
                            ->maxLength(255)->required(),
                        TextInput::make('native_name')
                            ->label(__('data::fields.native_name'))
                            ->maxLength(255)->nullable(),
                        Textarea::make('exonyms')
                            ->label(__('data::fields.exonyms'))
                            ->afterStateHydrated(function (Textarea $component, $state) {
                                if (is_array($state) || is_object($state)) {
                                    $state = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                                }

                                $component->state($state);
                            })
                            ->rule('json'),
                        TextInput::make('calling_code')
                            ->label(__('data::fields.calling_code'))
                            ->numeric()->maxValue(100),
                        TextInput::make('capital')
                            ->label(__('data::fields.capital'))
                            ->maxLength(255)->nullable(),
                        TextInput::make('population')
                            ->label(__('data::fields.population'))
                            ->integer()
                            ->nullable(),
                        TextInput::make('area')
                            ->label(__('data::fields.area'))
                            ->maxLength(255)->nullable(),
                        Textarea::make('links')
                            ->label(__('data::fields.links')),
                        Textarea::make('tlds')
                            ->rows(4)
                            ->label(__('data::fields.tlds')),
                        Textarea::make('membership')
                            ->rows(7)
                            ->label(__('data::fields.membership')),
                        TextInput::make('embargo_data')
                            ->label(__('data::fields.embargo_data')),
                        TextInput::make('address_format')
                            ->label(__('data::fields.address_format')),
                        TextInput::make('postal_code_regex')
                            ->label(__('data::fields.postal_code_regex'))
                            ->maxLength(255)->nullable(),
                        TextInput::make('dialing_prefix')
                            ->label(__('data::fields.dialing_prefix'))
                            ->maxLength(10)->nullable(),
                        TextInput::make('phone_number_formatting')
                            ->label(__('data::fields.phone_number_formatting')),
                        TextInput::make('date_format')
                            ->label(__('data::fields.date_format'))
                            ->maxLength(10)->required(),
                        TextInput::make('currency_format')
                            ->label(__('data::fields.currency_format')),

                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('alpha3_b')->label(__('data::fields.alpha3_b')),
                TextColumn::make('common_name')->label(__('data::fields.common_name')),
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
