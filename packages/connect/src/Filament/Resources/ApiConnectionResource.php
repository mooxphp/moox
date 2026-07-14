<?php

declare(strict_types=1);

namespace Moox\Connect\Filament\Resources;

use BackedEnum;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Connect\Filament\Resources\ApiConnectionResource\Pages;
use Moox\Connect\Models\ApiConnection;
use Moox\Core\Entities\Items\Item\BaseItemResource;
use UnitEnum;

class ApiConnectionResource extends BaseItemResource
{
    protected static ?string $model = ApiConnection::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static UnitEnum|string|null $navigationGroup;

    public static function getModelLabel(): string
    {
        return config('api-connection.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('api-connection.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('api-connection.single');
    }

    public static function getBreadcrumb(): string
    {
        return config('api-connection.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('api-connection.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('connect.navigation_sort') + 1;
    }

    public static function form(Schema $form): Schema
    {
        $authFields = config('connect.auth_fields', []);
        $basicFields = $authFields['basic'] ?? [];
        $bearerFields = $authFields['bearer'] ?? [];
        $jwtFields = $authFields['jwt'] ?? [];

        $basicUsernameKey = $basicFields['username'] ?? 'username';
        $basicPasswordKey = $basicFields['password'] ?? 'password';
        $bearerTokenKey = $bearerFields['token'] ?? 'token';
        $jwtAccessTokenKey = $jwtFields['access_token'] ?? 'access_token';

        return $form->components([
            Grid::make()
                ->schema([
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextInput::make('name')
                                        ->label(__('connect::fields.api_connection_name'))
                                        ->maxLength(255)->required(),
                                    TextInput::make('base_url')
                                        ->label(__('connect::fields.base_url'))
                                        ->maxLength(255)->required(),
                                    TextInput::make('health_path')
                                        ->label('Health Endpoint')
                                        ->placeholder('/health')
                                        ->maxLength(255)
                                        ->nullable(),
                                    Select::make('login_method')
                                        ->label('Login-Methode')
                                        ->options([
                                            'none' => 'Kein Login (Token direkt oder keine Auth)',
                                            'direct_token' => 'Direktes Token (JWT eintragen)',
                                            'rest_login' => 'REST-Login (Username/Password)',
                                            'graphql_login' => 'GraphQL-Login (Query + Variablen)',
                                        ])
                                        ->default('none')
                                        ->helperText('Wie soll das Auth-Token geholt werden?')
                                        ->visible(fn ($get) => $get('auth_type') === 'JWT')
                                        ->live(),

                                    TextInput::make('auth_credentials.'.$bearerTokenKey)
                                        ->label('Bearer Token')
                                        ->helperText('Wird verschlüsselt gespeichert.')
                                        ->visible(fn ($get) => $get('auth_type') === 'Bearer'),

                                    TextInput::make('auth_credentials.'.$jwtAccessTokenKey)
                                        ->label('JWT Access Token')
                                        ->helperText('Wird verschlüsselt gespeichert. Bei Login-Methoden wird dieses Feld automatisch befüllt.')
                                        ->visible(fn ($get) => $get('auth_type') === 'JWT')
                                        ->disabled(fn ($get) => in_array($get('login_method'), ['rest_login', 'graphql_login'], true)),

                                    TextInput::make('auth_credentials.login_path')
                                        ->label('JWT Login Path')
                                        ->placeholder('/auth/login oder /graphql')
                                        ->helperText('Endpoint, um JWT zu holen (REST oder GraphQL).')
                                        ->visible(fn ($get) => $get('auth_type') === 'JWT' && in_array($get('login_method'), ['rest_login', 'graphql_login'], true)),

                                    TextInput::make('auth_credentials.token_path')
                                        ->label('JWT Token Pfad')
                                        ->placeholder('access_token oder data.login.token')
                                        ->helperText('Dot-Notation zum Token im Login-Response.')
                                        ->visible(fn ($get) => $get('auth_type') === 'JWT' && in_array($get('login_method'), ['rest_login', 'graphql_login'], true)),

                                    Textarea::make('auth_credentials.graphql_query')
                                        ->label('JWT GraphQL Login Query')
                                        ->rows(4)
                                        ->placeholder("mutation Login(\$email: String!, \$password: String!) {\n  login(email: \$email, password: \$password) {\n    token\n  }\n}")
                                        ->helperText('Nur bei GraphQL-Login: Wenn gesetzt, wird der JWT-Login als GraphQL-Request (query + variables) ausgeführt.')
                                        ->visible(fn ($get) => $get('auth_type') === 'JWT' && $get('login_method') === 'graphql_login'),

                                    TextInput::make('auth_credentials.basic_username_key')
                                        ->label('Login Username Feldname')
                                        ->placeholder($basicUsernameKey)
                                        ->helperText('Optional: Payload-Key für Username im Login-Request, z.B. email oder user.')
                                        ->visible(fn ($get) => $get('auth_type') === 'JWT' && in_array($get('login_method'), ['rest_login', 'graphql_login'], true)),

                                    TextInput::make('auth_credentials.basic_password_key')
                                        ->label('Login Password Feldname')
                                        ->placeholder($basicPasswordKey)
                                        ->helperText('Optional: Payload-Key für Passwort im Login-Request.')
                                        ->visible(fn ($get) => $get('auth_type') === 'JWT' && in_array($get('login_method'), ['rest_login', 'graphql_login'], true)),

                                    TextInput::make('auth_credentials.'.$basicUsernameKey)
                                        ->label('Login Username')
                                        ->helperText('Wird verschlüsselt gespeichert. Für Basic-Auth oder REST/GraphQL-Login mit Username.')
                                        ->visible(fn ($get) => $get('auth_type') === 'Basic' || ($get('auth_type') === 'JWT' && in_array($get('login_method'), ['rest_login', 'graphql_login'], true))),

                                    TextInput::make('auth_credentials.'.$basicPasswordKey)
                                        ->label('Login Password')
                                        ->password()
                                        ->helperText('Wird verschlüsselt gespeichert. Für Basic-Auth oder REST/GraphQL-Login mit Password.')
                                        ->visible(fn ($get) => $get('auth_type') === 'Basic' || ($get('auth_type') === 'JWT' && in_array($get('login_method'), ['rest_login', 'graphql_login'], true))),
                                    KeyValue::make('headers')
                                        ->label(__('connect::fields.headers'))
                                        ->helperText('Optionale zusätzliche Header für alle Requests dieser Connection (z.B. Accept, X-Custom-Header).'),
                                    KeyValue::make('options')
                                        ->label('Options')
                                        ->helperText('Optionale JSON-Optionen pro Connection. Queue: queue, queue.tries, queue.timeout, queue.max_exceptions, queue.backoff, queue.retry_until_minutes, queue.overlap.release_after, queue.overlap.expire_buffer, queue.overlap.expire_min, queue.deadlock_retry.attempts, queue.deadlock_retry.delays_ms (gilt für alle Endpoints dieser Connection, sofern nicht am Endpoint überschrieben).'),
                                    TextInput::make('rate_limit')
                                        ->label(__('connect::fields.rate_limit'))
                                        ->numeric(),
                                    TextInput::make('lang_param')
                                        ->label(__('connect::fields.lang_param'))
                                        ->maxLength(255)->nullable(),
                                    TextInput::make('default_locale')
                                        ->label(__('connect::fields.default_locale'))
                                        ->maxLength(255)->nullable(),
                                    Select::make('api_type')
                                        ->label(__('connect::fields.api_type'))
                                        ->placeholder(__('core::core.type'))
                                        ->options([
                                            'REST' => 'REST',
                                            'GraphQL' => 'GraphQL',
                                        ])
                                        ->required(),
                                    Select::make('auth_type')
                                        ->label(__('connect::fields.auth_type'))
                                        ->placeholder(__('core::core.type'))
                                        ->options([
                                            'Bearer' => 'Bearer',
                                            'Basic' => 'Basic',
                                            'OAuth' => 'OAuth',
                                            'None' => 'None',
                                            'JWT' => 'JWT',
                                        ])
                                        ->required()
                                        ->live(),
                                    Select::make('status')
                                        ->label(__('connect::fields.status'))
                                        ->placeholder(__('core::core.type'))
                                        ->options([
                                            'New' => 'New',
                                            'Unused' => 'Unused',
                                            'Active' => 'Active',
                                            'Error' => 'Error',
                                            'Disabled' => 'Disabled',
                                        ])
                                        ->required(),
                                    Select::make('notify_on_failure')
                                        ->label(__('connect::fields.notify_on_failure'))
                                        ->placeholder(__('core::core.type'))
                                        ->options(__('connect::enums/notify-on-failure'))
                                        ->required(),
                                ])->columns(1)->columnSpan(2),
                        ])
                        ->columnSpan(2),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    static::getFormActions(),
                                ]),
                        ])
                        ->columns(1)
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('health_path')
                    ->label('Health Endpoint')
                    ->sortable()
                    ->icon(fn ($record) => strtolower($record->status) === 'active' ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color(fn ($record) => strtolower($record->status) === 'active' ? 'success' : 'danger')
                    ->alignCenter(),
                TextColumn::make('name')
                    ->label(__('connect::fields.api_connection_name')),
                TextColumn::make('base_url')
                    ->label(__('connect::fields.base_url')),
                TextColumn::make('api_type')
                    ->label(__('connect::fields.api_type'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('auth_type')
                    ->label(__('connect::fields.auth_type'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('headers')
                    ->label(__('connect::fields.headers')),
                TextColumn::make('rate_limit')
                    ->label(__('connect::fields.rate_limit')),
                TextColumn::make('lang_param')
                    ->label(__('connect::fields.lang_param')),
                TextColumn::make('default_locale')
                    ->label(__('connect::fields.default_locale')),
                TextColumn::make('status')
                    ->label(__('connect::fields.status'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('notify_on_failure')
                    ->label(__('connect::fields.notify_on_failure'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                ...static::getTableActions(),
            ])
            ->bulkActions([...static::getBulkActions()])
            ->filters([
                Filter::make('name')
                    ->form([
                        TextInput::make('name')
                            ->label(__('connect::fields.name'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['name'],
                            fn (Builder $query, $value): Builder => $query->where('name', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['name']) {
                            return null;
                        }

                        return 'Name: '.$data['name'];
                    }),
                Filter::make('base_url')
                    ->form([
                        TextInput::make('base_url')
                            ->label(__('connect::fields.base_url'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['base_url'],
                            fn (Builder $query, $value): Builder => $query->where('base_url', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['base_url']) {
                            return null;
                        }

                        return 'Base URL: '.$data['base_url'];
                    }),
                SelectFilter::make('api_type')
                    ->label(__('connect::fields.api_type'))
                    ->placeholder(__('core::core.filter').' API Type')
                    ->options(__('connect::enums/api-type')),
                SelectFilter::make('auth_type')
                    ->label(__('connect::fields.auth_type'))
                    ->placeholder(__('core::core.filter').' Authentication Type')
                    ->options(__('connect::enums/auth-type')),
                Filter::make('has_auth_credentials')
                    ->query(fn ($query) => $query->whereNotNull('auth_credentials')),
                Filter::make('has_headers')
                    ->query(fn ($query) => $query->whereNotNull('headers')),
                Filter::make('lang_param')
                    ->form([
                        TextInput::make('lang_param')
                            ->label(__('connect::fields.lang_param'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['lang_param'],
                            fn (Builder $query, $value): Builder => $query->where('lang_param', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['lang_param']) {
                            return null;
                        }

                        return 'Language Parameter: '.$data['lang_param'];
                    }),
                Filter::make('default_locale')
                    ->form([
                        TextInput::make('default_locale')
                            ->label(__('connect::fields.default_locale'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['default_locale'],
                            fn (Builder $query, $value): Builder => $query->where('default_locale', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['default_locale']) {
                            return null;
                        }

                        return 'Default Locale: '.$data['default_locale'];
                    }),
                SelectFilter::make('status')
                    ->label(__('connect::fields.status'))
                    ->placeholder(__('core::core.filter').' Status')
                    ->options(__('connect::enums/status')),
                SelectFilter::make('notify_on_failure')
                    ->label(__('connect::fields.notify_on_failure'))
                    ->placeholder(__('core::core.filter').' Notify on Failure')
                    ->options(['1' => '1', '' => '']),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiConnections::route('/'),
            'create' => Pages\CreateApiConnection::route('/create'),
            'edit' => Pages\EditApiConnection::route('/{record}/edit'),
            'view' => Pages\ViewApiConnection::route('/{record}'),
        ];
    }
}
