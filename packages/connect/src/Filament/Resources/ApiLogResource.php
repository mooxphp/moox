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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Connect\Filament\Resources\ApiLogResource\Pages;
use Moox\Connect\Models\ApiLog;
use Moox\Core\Entities\Items\Item\BaseItemResource;

class ApiLogResource extends BaseItemResource
{
    protected static ?string $model = ApiLog::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getModelLabel(): string
    {
        return config('api-log.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('api-log.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('api-log.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('api-log.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('api-log.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('connect.navigation_sort') + 1;
    }

    public static function form(Schema $form): Schema
    {
        return $form->components([
            Grid::make()
                ->schema([
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextInput::make('api_connection_id')
                                        ->label(__('connect::fields.api_connection_name')),
                                    TextInput::make('endpoint_id')
                                        ->label(__('connect::fields.api_endpoint_name'))
                                        ->numeric(),
                                    KeyValue::make('request_data')
                                        ->label(__('connect::fields.request_data'))->required(),
                                    // Textarea::make('response_data')
                                    //     ->label(__('connect::fields.response_data'))
                                    //     ->rows(10)
                                    //     ->maxLength(1000)
                                    //     ->formatStateUsing(fn ($state) => $state === null ? null : json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                                    //     ->dehydrated(false)
                                    //     ->visible(fn ($record) => !empty($record?->response_data)),
                                    TextInput::make('status_code')
                                        ->label(__('connect::fields.status_code'))
                                        ->maxLength(255)->required(),
                                    TextInput::make('error_message')
                                        ->label(__('connect::fields.error_message'))
                                        ->maxLength(255)->nullable(),
                                ]),
                        ])->columns(1)
                        ->columnSpan(2),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    static::getFormActions(),
                                ]),
                            Section::make('')
                                ->schema([
                                    Select::make('trigger')
                                        ->label(__('connect::fields.trigger'))
                                        ->placeholder(__('core::core.type'))
                                        ->options(__('connect::enums/trigger'))
                                        ->required(),
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
                TextColumn::make('apiConnection.name')
                    ->sortable()->label(__('connect::fields.api_connection_name')),
                TextColumn::make('status_code')->label(__('connect::fields.status_code')),
                TextColumn::make('error_message')->label(__('connect::fields.error_message')),
                TextColumn::make('endpoint.name')
                    ->numeric(0)->label(__('connect::fields.endpoint_id')),
                TextColumn::make('trigger')->sortable()->searchable()->toggleable()->label(__('connect::fields.trigger')),
                TextColumn::make('request_data')->label(__('connect::fields.request_data')),
                TextColumn::make('response_data')->label(__('connect::fields.response_data')),
            ])
            ->defaultSort('id', 'desc')
            ->actions([...static::getTableActions()])
            ->bulkActions([...static::getBulkActions()])
            ->filters([
                SelectFilter::make('api_connection_id')
                    ->relationship('apiConnection', 'name')->label(__('connect::fields.api_connection_id')),
                SelectFilter::make('trigger')
                    ->label(__('connect::fields.trigger'))
                    ->placeholder(__('core::core.filter').' Trigger')
                    ->options(__('connect::enums/trigger')),
                Filter::make('has_request_data')
                    ->query(fn ($query) => $query->whereNotNull('request_data')),
                Filter::make('has_response_data')
                    ->query(fn ($query) => $query->whereNotNull('response_data')),
                Filter::make('status_code')
                    ->form([
                        TextInput::make('status_code')
                            ->label(__('connect::fields.status_code'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['status_code'],
                            fn (Builder $query, $value): Builder => $query->where('status_code', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['status_code']) {
                            return null;
                        }

                        return 'Status Code: '.$data['status_code'];
                    }),
                Filter::make('error_message')
                    ->form([
                        TextInput::make('error_message')
                            ->label(__('connect::fields.error_message'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['error_message'],
                            fn (Builder $query, $value): Builder => $query->where('error_message', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['error_message']) {
                            return null;
                        }

                        return 'Error Message: '.$data['error_message'];
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiLogs::route('/'),
            'create' => Pages\CreateApiLog::route('/create'),
            'edit' => Pages\EditApiLog::route('/{record}/edit'),
            'view' => Pages\ViewApiLog::route('/{record}'),
        ];
    }
}
