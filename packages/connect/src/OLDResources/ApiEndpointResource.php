<?php

namespace Moox\Connect\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Moox\Connect\Models\ApiEndpoint;

class ApiEndpointResource extends Resource
{
    protected static ?string $model = ApiEndpoint::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\Repeater::make('transformers')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options([
                                'date' => 'Date Format',
                                'number' => 'Number Format',
                                'json' => 'JSON Path',
                            ])
                            ->required(),
                        Forms\Components\KeyValue::make('options')
                            ->default([]),
                    ])
                    ->orderable()
                    ->defaultItems(0)
                    ->castToArray(),
                Forms\Components\Select::make('api_connection_id')
                    ->relationship('apiConnection')
                    ->required(),
                Forms\Components\TextInput::make('path')
                    ->required(),
                Forms\Components\Select::make('method')
                    ->options([
                        'GET' => 'GET',
                        'POST' => 'POST',
                        'PUT' => 'PUT',
                        'DELETE' => 'DELETE',
                        'PATCH' => 'PATCH',
                    ])
                    ->required(),
                Forms\Components\Toggle::make('direct_access')
                    ->default(false),
                Forms\Components\KeyValue::make('variables')
                    ->castToArray(),
                Forms\Components\KeyValue::make('response_map')
                    ->castToArray(),
                Forms\Components\KeyValue::make('expected_response')
                    ->required()
                    ->castToArray(),
                Forms\Components\KeyValue::make('field_mappings')
                    ->castToArray(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'error' => 'danger',
                        'disabled' => 'warning',
                        default => 'secondary',
                    })
                    ->sortable(),
                TextColumn::make('error_count')
                    ->sortable()
                    ->color('danger')
                    ->visible(fn ($record) => $record->error_count > 0),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'unused' => 'Unused',
                        'active' => 'Active',
                        'error' => 'Error',
                        'disabled' => 'Disabled',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ApiEndpointResource\Pages\ListApiEndpoints::route('/'),
            'create' => ApiEndpointResource\Pages\CreateApiEndpoint::route('/create'),
            'edit' => ApiEndpointResource\Pages\EditApiEndpoint::route('/{record}/edit'),
            'view' => ApiEndpointResource\Pages\ViewApiEndpoint::route('/{record}'),
        ];
    }
}
