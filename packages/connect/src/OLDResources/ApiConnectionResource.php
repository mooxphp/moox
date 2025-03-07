<?php

namespace Moox\Connect\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Moox\Connect\Models\ApiConnection;

class ApiConnectionResource extends Resource
{
    protected static ?string $model = ApiConnection::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('base_url')
                    ->required()
                    ->url(),
                Forms\Components\Select::make('api_type')
                    ->options([
                        'REST' => 'REST',
                        'GraphQL' => 'GraphQL',
                    ])
                    ->required(),
                Forms\Components\Select::make('auth_type')
                    ->options([
                        'bearer' => 'Bearer Token',
                        'basic' => 'Basic Auth',
                        'oauth' => 'OAuth',
                        'jwt' => 'JWT',
                    ])
                    ->reactive()
                    ->required(),
                Forms\Components\Grid::make()
                    ->schema(fn (Forms\Get $get) => match ($get('auth_type')) {
                        'jwt' => [
                            Forms\Components\TextInput::make('auth_credentials.secret_key')
                                ->required()
                                ->password(),
                            Forms\Components\Select::make('auth_credentials.algorithm')
                                ->options([
                                    'HS256' => 'HS256',
                                    'HS384' => 'HS384',
                                    'HS512' => 'HS512',
                                ])
                                ->default('HS256')
                                ->required(),
                            Forms\Components\TextInput::make('auth_credentials.access_token')
                                ->password(),
                            Forms\Components\TextInput::make('auth_credentials.refresh_token')
                                ->password(),
                        ],
                        default => [],
                    }),
                Forms\Components\Toggle::make('notify_on_failure')
                    ->default(true),
                Forms\Components\TextInput::make('notify_email')
                    ->email(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('api_type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'error' => 'danger',
                        'disabled' => 'warning',
                        default => 'secondary',
                    })
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'unused' => 'Unused',
                        'active' => 'Active',
                        'error' => 'Error',
                        'disabled' => 'Disabled',
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ApiConnectionResource\Pages\ListApiConnections::route('/'),
            'create' => ApiConnectionResource\Pages\CreateApiConnection::route('/create'),
            'edit' => ApiConnectionResource\Pages\EditApiConnection::route('/{record}/edit'),
            'view' => ApiConnectionResource\Pages\ViewApiConnection::route('/{record}'),
        ];
    }
}
