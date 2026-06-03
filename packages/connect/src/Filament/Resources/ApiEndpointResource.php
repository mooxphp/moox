<?php

declare(strict_types=1);

namespace Moox\Connect\Filament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Connect\Filament\Resources\ApiEndpointResource\Pages;
use Moox\Connect\Filament\Resources\ApiEndpointResource\RelationManagers\ApiImportRecordsRelationManager;
use Moox\Connect\Jobs\RunDetailForListJob;
use Moox\Connect\Jobs\RunEndpointJob;
use Moox\Connect\Models\ApiEndpoint;
use Moox\Connect\Models\ApiImportRecord;
use Moox\Core\Entities\Items\Item\BaseItemResource;

class ApiEndpointResource extends BaseItemResource
{
    protected static ?string $model = ApiEndpoint::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';

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
        return config('api-endpoint.navigation_group');
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
                                    Select::make('parent_endpoint_id')
                                        ->label('Parent Endpoint (Liste)')
                                        ->relationship('parentEndpoint', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->helperText('Optional: Dieser Endpoint ist ein Detail-Endpoint und nutzt IDs aus den ImportRecords eines Listen-Endpoints.'),
                                    TextInput::make('list_item_path')
                                        ->label('List Item Pfad')
                                        ->placeholder('data.items')
                                        ->helperText('Für Listen-Endpoints: JSON-Pfad (dot notation), unter dem die einzelnen Items liegen (z.B. data.articlegroups).'),
                                    TextInput::make('list_id_key')
                                        ->label('List Item ID Key')
                                        ->placeholder('id')
                                        ->helperText('Feldname innerhalb eines Items, das die ID enthält (z.B. id, Id, userId).'),
                                    TextInput::make('route_param_key')
                                        ->label('Route Param Key')
                                        ->placeholder('id')
                                        ->helperText('Name des Pfad-Parameters im Detail-Endpoint (z.B. {id} im Pfad /users/{id}).'),
                                    TextInput::make('variable_key')
                                        ->label('Variable Key')
                                        ->placeholder('id')
                                        ->helperText('Optional: Wenn die ID nicht im Pfad, sondern als Query-/Body-Variable gesendet werden soll, hier den Variablennamen eintragen.'),
                                    TextInput::make('external_key_field')
                                        ->label('External Key Field')
                                        ->placeholder('ArticleGroup.Id')
                                        ->helperText('Optional: JSON-Pfad im Listen-Item, der als external_key im ImportRecord gespeichert werden soll (z.B. ArticleGroup.Id).'),
                                    Select::make('sync_mode')
                                        ->label('Sync Mode')
                                        ->options([
                                            'append' => 'Append (nur upsert/insert, keine Deletes)',
                                            'sync' => 'Sync (upsert + prune missing)',
                                        ])
                                        ->default('append')
                                        ->helperText('Wenn "sync": nicht mehr vorhandene Datensätze (pro Scope) werden im Zielmodell gelöscht (SoftDelete falls vorhanden).'),
                                    KeyValue::make('sync_scope_fields')
                                        ->label('Sync Scope Fields')
                                        ->helperText('Optional: Scope fürs Pruning als Mapping internal_field => external_payload_path (z.B. article_group_id => ArticleGroup.Id).'),
                                    KeyValue::make('options')
                                        ->label('Options')
                                        ->helperText('Optionale JSON-Optionen pro Endpoint (z.B. tree.stop_on_http_error => true, sync.purge_after_days => 30).'),
                                    KeyValue::make('variables')
                                        ->label(__('connect::fields.variables'))
                                        ->helperText('Key-Value für Query-/Body-Parameter dieses Endpoints (z.B. ?page=1&limit=50 oder JSON-Body-Felder).'),
                                    KeyValue::make('response_map')
                                        ->label(__('connect::fields.response_map'))
                                        ->helperText('Beschreibt, wie Felder aus der API-Response auf interne Namen gemappt werden (z.B. data.items → items).'),
                                    KeyValue::make('expected_response')
                                        ->label(__('connect::fields.expected_response'))
                                        ->helperText('Optionale Definition, welche Struktur/Felder die Response haben sollte (z.B. zur Validierung oder Doku).'),
                                    KeyValue::make('field_mappings')
                                        ->label(__('connect::fields.field_mappings'))
                                        ->helperText('Mapping von API-Feldern auf Zieldatenbank-Felder (z.B. external_id → products.id).'),
                                    KeyValue::make('transformers')
                                        ->label(__('connect::fields.transformers'))
                                        ->helperText('Optionale Transformationsregeln (z.B. Datumsformat, Typ-Konvertierungen) vor dem Import in deine Modelle.'),
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
                                ])->columns(1)->columnSpan(2),
                        ])
                        ->columnSpan(2),
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
            ->actions([
                ...static::getTableActions(),
                Action::make('run')
                    ->label('Run')
                    ->icon('heroicon-o-play')
                    ->visible(fn (ApiEndpoint $record): bool => static::canRunEndpoint($record))
                    ->action(function (ApiEndpoint $record): void {
                        static::dispatchRunJob($record);

                        Notification::make()
                            ->title('Run gestartet')
                            ->body(static::getRunNotificationBody($record))
                            ->success()
                            ->send();
                    }),
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

    protected static function canRunEndpoint(ApiEndpoint $endpoint): bool
    {
        if (! empty($endpoint->parent_endpoint_id)
            && empty($endpoint->direct_access)) {
            $parentId = (int) $endpoint->parent_endpoint_id;

            // Hide until the parent endpoint has produced at least one successful import record.
            return ApiImportRecord::query()
                ->where('api_endpoint_id', $parentId)
                ->whereIn('status', ['fetched', 'update', 'processed', 'new'])
                ->exists();
        }

        return $endpoint->direct_access
            || ! empty($endpoint->parent_endpoint_id);
    }

    protected static function dispatchRunJob(ApiEndpoint $endpoint): void
    {
        if (! empty($endpoint->parent_endpoint_id)) {
            RunDetailForListJob::dispatch($endpoint->id);

            return;
        }

        RunEndpointJob::dispatch($endpoint->id);
    }

    protected static function getRunNotificationBody(ApiEndpoint $endpoint): string
    {
        if (! empty($endpoint->parent_endpoint_id)) {
            return 'Job zum Ausfuehren der Detail-Requests wurde in die Queue gestellt.';
        }

        return 'Job zum Ausfuehren des Endpoints wurde in die Queue gestellt.';
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

    public static function getRelations(): array
    {
        return [
            ApiImportRecordsRelationManager::class,
        ];
    }
}
