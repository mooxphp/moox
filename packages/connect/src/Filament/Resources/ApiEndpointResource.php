<?php

declare(strict_types=1);

namespace Moox\Connect\Filament\Resources;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Connect\Filament\Resources\ApiEndpointResource\Pages;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Simple\SingleSimpleInResource;

class ApiEndpointResource extends Resource
{
    use BaseInResource, SingleSimpleInResource;

    protected static ?string $model = \Moox\Connect\Models\ApiEndpoint::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getModelLabel(): string
    {
        return config('api-endpoint.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('api-endpoint.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('api-endpoint.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('api-endpoint.single');
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
                                        ->label(__('connect::fields.api_endpoint_name'))
                                        ->maxLength(255)->required(),
                                    Select::make('api_connection_id')
                                        ->label(__('connect::fields.api_connection_name'))
                                        ->relationship('apiConnection', 'name')
                                        ->searchable()
                                        ->preload()->required(),
                                    TextInput::make('path')
                                        ->label(__('connect::fields.path'))
                                        ->maxLength(255)->required(),
                                    TextInput::make('method')
                                        ->label(__('connect::fields.method'))
                                        ->maxLength(255)->required(),
                                    Toggle::make('direct_access')
                                        ->label(__('connect::fields.direct_access'))->required(),
                                    KeyValue::make('variables')
                                        ->label(__('connect::fields.variables')),
                                    KeyValue::make('response_map')
                                        ->label(__('connect::fields.response_map')),
                                    KeyValue::make('expected_response')
                                        ->label(__('connect::fields.expected_response'))->required(),
                                    KeyValue::make('field_mappings')
                                        ->label(__('connect::fields.field_mappings')),
                                    KeyValue::make('transformers')
                                        ->label(__('connect::fields.transformers')),
                                    TextInput::make('lang_override')
                                        ->label(__('connect::fields.lang_override'))
                                        ->maxLength(255)->nullable(),
                                    TextInput::make('rate_limit')
                                        ->label(__('connect::fields.rate_limit'))
                                        ->numeric(),
                                    TextInput::make('rate_window')
                                        ->label(__('connect::fields.rate_window'))
                                        ->numeric(),
                                    TextInput::make('timeout')
                                        ->label(__('connect::fields.timeout'))
                                        ->numeric()->required(),
                                ]),
                        ])
                        ->columnSpan(['lg' => 2]),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    static::getFormActions(),
                                ]),
                            Section::make('')
                                ->schema([
                                    Select::make('status')
                                        ->label(__('connect::fields.status'))
                                        ->placeholder(__('core::core.type'))
                                        ->options(__('connect::enums/status'))
                                        ->required(),
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
                TextColumn::make('name')->label(__('connect::fields.api_endpoint_name')),
                TextColumn::make('apiConnection.name')
                    ->sortable()->label(__('connect::fields.api_connection_name')),
                TextColumn::make('path')->label(__('connect::fields.path')),
                TextColumn::make('method')->label(__('connect::fields.method')),
                IconColumn::make('direct_access')
                    ->boolean()->label(__('connect::fields.direct_access')),
                TextColumn::make('variables')->label(__('connect::fields.variables')),
                TextColumn::make('response_map')->label(__('connect::fields.response_map')),
                TextColumn::make('expected_response')->label(__('connect::fields.expected_response')),
                TextColumn::make('field_mappings')->label(__('connect::fields.field_mappings')),
                TextColumn::make('transformers')->label(__('connect::fields.transformers')),
                TextColumn::make('lang_override')->label(__('connect::fields.lang_override')),
                TextColumn::make('rate_limit')
                    ->numeric(0)->label(__('connect::fields.rate_limit')),
                TextColumn::make('rate_window')
                    ->numeric(0)->label(__('connect::fields.rate_window')),
                TextColumn::make('status')->sortable()->searchable()->toggleable()->label(__('connect::fields.status')),
                TextColumn::make('timeout')
                    ->numeric(0)->label(__('connect::fields.timeout')),
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
                SelectFilter::make('apiConnection.name'),
                Filter::make('path')
                    ->form([
                        TextInput::make('path')
                            ->label(__('connect::fields.path'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['path'],
                            fn (Builder $query, $value): Builder => $query->where('path', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['path']) {
                            return null;
                        }

                        return 'Path: '.$data['path'];
                    }),
                Filter::make('method')
                    ->form([
                        TextInput::make('method')
                            ->label(__('connect::fields.method'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['method'],
                            fn (Builder $query, $value): Builder => $query->where('method', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['method']) {
                            return null;
                        }

                        return 'HTTP Method: '.$data['method'];
                    }),
                Filter::make('has_variables')
                    ->query(fn ($query) => $query->whereNotNull('variables')),
                Filter::make('has_response_map')
                    ->query(fn ($query) => $query->whereNotNull('response_map')),
                Filter::make('has_expected_response')
                    ->query(fn ($query) => $query->whereNotNull('expected_response')),
                Filter::make('has_field_mappings')
                    ->query(fn ($query) => $query->whereNotNull('field_mappings')),
                Filter::make('has_transformers')
                    ->query(fn ($query) => $query->whereNotNull('transformers')),
                Filter::make('lang_override')
                    ->form([
                        TextInput::make('lang_override')
                            ->label(__('connect::fields.lang_override'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['lang_override'],
                            fn (Builder $query, $value): Builder => $query->where('lang_override', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['lang_override']) {
                            return null;
                        }

                        return 'Language Override: '.$data['lang_override'];
                    }),
                SelectFilter::make('status')
                    ->label(__('core::core.status'))
                    ->placeholder(__('core::core.filter').' Status')
                    ->options(__('connect::enums/status')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiEndpoints::route('/'),
            'create' => Pages\CreateApiEndpoint::route('/create'),
            'edit' => Pages\EditApiEndpoint::route('/{record}/edit'),
            'view' => Pages\ViewApiEndpoint::route('/{record}'),
        ];
    }
}
