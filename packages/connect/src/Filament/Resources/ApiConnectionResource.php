<?php

declare(strict_types=1);

namespace Moox\Connect\Filament\Resources;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Connect\Filament\Resources\ApiConnectionResource\Pages;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Simple\SingleSimpleInResource;

class ApiConnectionResource extends Resource
{
    use BaseInResource, SingleSimpleInResource;

    protected static ?string $model = \Moox\Connect\Models\ApiConnection::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
        return config('connect.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('connect.navigation_sort') + 1;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(2)
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
                                    KeyValue::make('auth_credentials')
                                        ->label(__('connect::fields.auth_credentials')),
                                    KeyValue::make('headers')
                                        ->label(__('connect::fields.headers')),
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
                                        ->options(__('connect::enums/api-type'))
                                        ->required(),
                                    Select::make('auth_type')
                                        ->label(__('connect::fields.auth_type'))
                                        ->placeholder(__('core::core.type'))
                                        ->options(__('connect::enums/auth-type'))
                                        ->required(),
                                    Select::make('status')
                                        ->label(__('connect::fields.status'))
                                        ->placeholder(__('core::core.type'))
                                        ->options(__('connect::enums/status'))
                                        ->required(),
                                    Select::make('notify_on_failure')
                                        ->label(__('connect::fields.notify_on_failure'))
                                        ->placeholder(__('core::core.type'))
                                        ->options(__('connect::enums/notify-on-failure'))
                                        ->required(),
                                ]),
                        ])
                        ->columnSpan(['lg' => 2]),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    static::getFormActions(),
                                ]),

                        ])
                        ->columnSpan(['lg' => 1]),
                ])
                ->columns(['lg' => 3]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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
                TextColumn::make('auth_credentials')
                    ->label(__('connect::fields.auth_credentials')),
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
            ->actions([...static::getTableActions()])
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
