<?php

namespace Moox\Data\Filament\Resources\StaticTimezoneResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StaticCountriesRelationManager extends RelationManager
{
    protected static string $relationship = 'countries';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Components\TextInput::make('alpha2')
                            ->label(__('data::fields.alpha2'))
                            ->maxLength(3)->required(),
                        Components\TextInput::make('alpha3_b')
                            ->label(__('data::fields.alpha3_b'))
                            ->maxLength(3)->nullable(),
                        Components\TextInput::make('alpha3_t')
                            ->label(__('data::fields.alpha3_t'))
                            ->maxLength(3)->nullable(),
                        Components\TextInput::make('common_name')
                            ->label(__('data::fields.common_name'))
                            ->maxLength(255)->required(),
                        Components\TextInput::make('native_name')
                            ->label(__('data::fields.native_name'))
                            ->maxLength(255)->nullable(),
                        Components\Textarea::make('exonyms')
                            ->label(__('data::fields.exonyms'))
                            ->afterStateHydrated(function (Components\Textarea $component, $state) {
                                if (is_array($state) || is_object($state)) {
                                    $state = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                                }

                                $component->state($state);
                            })
                            ->rule('json'),
                        Components\TextInput::make('calling_code')
                            ->label(__('data::fields.calling_code'))
                            ->numeric()->maxValue(100),
                        Components\TextInput::make('capital')
                            ->label(__('data::fields.capital'))
                            ->maxLength(255)->nullable(),
                        Components\TextInput::make('population')
                            ->label(__('data::fields.population'))
                            ->integer()
                            ->nullable(),
                        Components\TextInput::make('area')
                            ->label(__('data::fields.area'))
                            ->maxLength(255)->nullable(),
                        Components\Textarea::make('links')
                            ->label(__('data::fields.links')),
                        Components\Textarea::make('tlds')
                            ->rows(4)
                            ->label(__('data::fields.tlds')),
                        Components\Textarea::make('membership')
                            ->rows(7)
                            ->label(__('data::fields.membership')),
                        Components\TextInput::make('embargo_data')
                            ->label(__('data::fields.embargo_data')),
                        Components\TextInput::make('address_format')
                            ->label(__('data::fields.address_format')),
                        Components\TextInput::make('postal_code_regex')
                            ->label(__('data::fields.postal_code_regex'))
                            ->maxLength(255)->nullable(),
                        Components\TextInput::make('dialing_prefix')
                            ->label(__('data::fields.dialing_prefix'))
                            ->maxLength(10)->nullable(),
                        Components\TextInput::make('phone_number_formatting')
                            ->label(__('data::fields.phone_number_formatting')),
                        Components\TextInput::make('date_format')
                            ->label(__('data::fields.date_format'))
                            ->maxLength(10)->required(),
                        Components\TextInput::make('currency_format')
                            ->label(__('data::fields.currency_format')),

                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('alpha3_b')->label(__('data::fields.alpha3_b')),
                Tables\Columns\TextColumn::make('common_name')->label(__('data::fields.common_name')),
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
