<?php

declare(strict_types=1);

namespace Moox\Transform\Filament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Moox\Core\Entities\Items\Item\BaseItemResource;
use Moox\Transform\Enums\TransformExecutionMode;
use Moox\Transform\Filament\Resources\TransformDefinitionResource\Pages;
use Moox\Transform\Filament\Resources\TransformDefinitionResource\RelationManagers\TransformRecordsRelationManager;
use Moox\Transform\Jobs\DispatchTransformDefinitionForEndpointJob;
use Moox\Transform\Jobs\RunTransformRecordJob;
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Models\TransformRecord;
use Moox\Transform\Support\ConfiguredImportRecordProjectionEnricher;
use Moox\Transform\Support\ImportRecordSelectOptionBuilder;

class TransformDefinitionResource extends BaseItemResource
{
    protected static ?string $model = TransformDefinition::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-arrows-right-left';

    public static function getModelLabel(): string
    {
        return (string) config('transform-definition.single', 'Transform Definition');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) config('transform-definition.plural', 'Transform Definitions');
    }

    public static function getNavigationLabel(): string
    {
        return (string) config('transform-definition.plural', 'Transform Definitions');
    }

    public static function getBreadcrumb(): string
    {
        return (string) config('transform-definition.single', 'Transform Definition');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('transform-definition.navigation_group', 'Transform');
    }

    public static function getNavigationSort(): ?int
    {
        return (int) config('transform.navigation_sort', 200);
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
                                        ->label(__('transform::fields.name'))
                                        ->required()
                                        ->maxLength(255),
                                    Select::make('destination_model')
                                        ->label(__('transform::fields.destination_model'))
                                        ->searchable()
                                        ->options(static::discoverModelOptions())
                                        ->required()
                                        ->helperText('Auto-detected Eloquent models from app and packages.'),
                                    Repeater::make('source_references')
                                        ->label(__('transform::fields.source_references'))
                                        ->required()
                                        ->collapsible()
                                        ->schema([
                                            TextInput::make('alias')
                                                ->label(__('transform::fields.alias'))
                                                ->maxLength(255),
                                            Select::make('source_type')
                                                ->label(__('transform::fields.source_type'))
                                                ->options(static::sourceTypeOptions())
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(function (Set $set): void {
                                                    $set('connection', TransformDefinition::defaultConnectionName());
                                                    $set('table', null);
                                                    $set('columns', []);
                                                    $set('key_column', null);
                                                    $set('row_key', null);
                                                    $set('row_key_from', null);
                                                    $set('where', []);
                                                    $set('path', null);
                                                    $set('url', null);
                                                    $set('query', []);
                                                    $set('record_id', null);
                                                    $set('item_key', null);
                                                    $set('selector', null);
                                                    $set('data', null);
                                                }),
                                            Select::make('connection')
                                                ->label(__('transform::fields.connection'))
                                                ->options(TransformDefinition::discoverConnectionOptions())
                                                ->searchable()
                                                ->required(fn (Get $get): bool => $get('source_type') === 'db_table')
                                                ->visible(fn (Get $get): bool => $get('source_type') === 'db_table')
                                                ->default(fn (): string => TransformDefinition::defaultConnectionName())
                                                ->afterStateHydrated(function (Get $get, Set $set, mixed $state): void {
                                                    if ($get('source_type') !== 'db_table') {
                                                        return;
                                                    }

                                                    if (! is_string($state) || $state === '') {
                                                        $set('connection', TransformDefinition::defaultConnectionName());
                                                    }
                                                })
                                                ->live()
                                                ->afterStateUpdated(function (Set $set): void {
                                                    $set('table', null);
                                                    $set('columns', []);
                                                    $set('key_column', null);
                                                    $set('row_key', null);
                                                }),
                                            Select::make('table')
                                                ->label(__('transform::fields.table'))
                                                ->options(fn (Get $get): array => TransformDefinition::discoverTableOptions((string) $get('connection')))
                                                ->searchable()
                                                ->required(fn (Get $get): bool => $get('source_type') === 'db_table')
                                                ->visible(fn (Get $get): bool => $get('source_type') === 'db_table')
                                                ->live()
                                                ->afterStateUpdated(function (Set $set): void {
                                                    $set('columns', []);
                                                    $set('key_column', null);
                                                    $set('row_key', null);
                                                }),
                                            Select::make('key_column')
                                                ->label(__('transform::fields.key_column'))
                                                ->options(fn (Get $get): array => TransformDefinition::discoverColumnOptions((string) $get('connection'), (string) $get('table')))
                                                ->searchable()
                                                ->required(fn (Get $get): bool => in_array((string) $get('source_type'), ['db_table', 'file_csv'], true))
                                                ->visible(fn (Get $get): bool => in_array((string) $get('source_type'), ['db_table', 'file_csv'], true)),
                                            TextInput::make('row_key')
                                                ->label(__('transform::fields.row_key'))
                                                ->helperText(__('transform::fields.row_key_help'))
                                                ->required(fn (Get $get): bool => $get('source_type') === 'file_csv')
                                                ->visible(fn (Get $get): bool => static::isSourceType($get, ['db_table', 'file_csv', 'api_import_record'])),
                                            TextInput::make('row_key_from')
                                                ->label(__('transform::fields.row_key_from'))
                                                ->helperText(__('transform::fields.row_key_from_help'))
                                                ->visible(fn (Get $get): bool => static::isSourceType($get, ['db_table', 'file_csv', 'api_import_record'])),
                                            Repeater::make('where')
                                                ->label(__('transform::fields.where'))
                                                ->helperText(__('transform::fields.where_help'))
                                                ->schema([
                                                    Select::make('column')
                                                        ->label(__('transform::fields.where_column'))
                                                        ->options(fn (Get $get): array => TransformDefinition::discoverColumnOptions((string) $get('../../connection'), (string) $get('../../table')))
                                                        ->searchable()
                                                        ->required(),
                                                    Select::make('operator')
                                                        ->label(__('transform::fields.where_operator'))
                                                        ->options(static::whereOperatorOptions())
                                                        ->default('=')
                                                        ->required()
                                                        ->live(),
                                                    Textarea::make('value')
                                                        ->label(__('transform::fields.where_value'))
                                                        ->helperText(__('transform::fields.where_value_help'))
                                                        ->rows(2)
                                                        ->visible(fn (Get $get): bool => in_array((string) $get('operator'), ['=', '!=', '<', '>', '<=', '>=', 'in'], true))
                                                        ->dehydrateStateUsing(static function (mixed $state, Get $get): mixed {
                                                            $operator = (string) $get('operator');

                                                            if ($operator === 'in') {
                                                                $decoded = json_decode(is_string($state) ? $state : '[]', true);

                                                                return is_array($decoded) ? $decoded : [];
                                                            }

                                                            if (! is_string($state) || trim($state) === '') {
                                                                return null;
                                                            }

                                                            if (strtolower(trim($state)) === 'null') {
                                                                return null;
                                                            }

                                                            if (is_numeric($state)) {
                                                                return str_contains($state, '.') ? (float) $state : (int) $state;
                                                            }

                                                            return $state;
                                                        })
                                                        ->formatStateUsing(static function (mixed $state, Get $get): string {
                                                            if ((string) $get('operator') === 'in' && is_array($state)) {
                                                                return (string) json_encode($state, JSON_UNESCAPED_UNICODE);
                                                            }

                                                            if ($state === null) {
                                                                return 'null';
                                                            }

                                                            return is_scalar($state) ? (string) $state : '';
                                                        }),
                                                ])
                                                ->visible(fn (Get $get): bool => $get('source_type') === 'db_table')
                                                ->default([]),
                                            Select::make('columns')
                                                ->label(__('transform::fields.columns'))
                                                ->helperText('Optional subset. If empty, all columns can be used.')
                                                ->multiple()
                                                ->searchable()
                                                ->options(fn (Get $get): array => TransformDefinition::discoverColumnOptions((string) $get('connection'), (string) $get('table')))
                                                ->visible(fn (Get $get): bool => $get('source_type') === 'db_table')
                                                ->default([]),
                                            TextInput::make('path')
                                                ->label(__('transform::fields.path'))
                                                ->required(fn (Get $get): bool => in_array((string) $get('source_type'), ['file_json', 'file_csv'], true))
                                                ->visible(fn (Get $get): bool => in_array((string) $get('source_type'), ['file_json', 'file_csv'], true)),
                                            TextInput::make('selector')
                                                ->label(__('transform::fields.selector'))
                                                ->helperText(__('transform::fields.selector_help'))
                                                ->visible(fn (Get $get): bool => static::isSourceType($get, ['file_json', 'file_csv', 'api', 'api_import_record'])),
                                            TextInput::make('url')
                                                ->label(__('transform::fields.url'))
                                                ->required(fn (Get $get): bool => $get('source_type') === 'api')
                                                ->visible(fn (Get $get): bool => $get('source_type') === 'api')
                                                ->url(),
                                            KeyValue::make('query')
                                                ->label(__('transform::fields.query'))
                                                ->helperText(__('transform::fields.query_help'))
                                                ->visible(fn (Get $get): bool => $get('source_type') === 'api')
                                                ->default([]),
                                            TextInput::make('record_id')
                                                ->label(__('transform::fields.record_id'))
                                                ->helperText(__('transform::fields.record_id_help'))
                                                ->default(fn (): string => (string) config('transform.default_import_record_id_template', '{{context.import_record_id}}'))
                                                ->required(fn (Get $get): bool => $get('source_type') === 'api_import_record')
                                                ->visible(fn (Get $get): bool => $get('source_type') === 'api_import_record'),
                                            TextInput::make('item_key')
                                                ->label(__('transform::fields.item_key'))
                                                ->helperText(__('transform::fields.item_key_help'))
                                                ->visible(fn (Get $get): bool => $get('source_type') === 'api_import_record'),
                                            Textarea::make('data')
                                                ->label(__('transform::fields.static_data'))
                                                ->helperText(__('transform::fields.static_data_help'))
                                                ->rows(6)
                                                ->required(fn (Get $get): bool => $get('source_type') === 'static')
                                                ->visible(fn (Get $get): bool => $get('source_type') === 'static')
                                                ->dehydrateStateUsing(static function (mixed $state): array {
                                                    $decoded = json_decode(is_string($state) ? $state : '{}', true);

                                                    return is_array($decoded) ? $decoded : [];
                                                })
                                                ->formatStateUsing(static function (mixed $state): string {
                                                    if (! is_array($state) || $state === []) {
                                                        return '';
                                                    }

                                                    return (string) json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                                }),
                                        ])
                                        ->live(),
                                    Repeater::make('field_map')
                                        ->label(__('transform::fields.field_map'))
                                        ->helperText('Select destination field and source path from discovered options.')
                                        ->required()
                                        ->minItems(1)
                                        ->collapsible()
                                        ->schema([
                                            TextInput::make('destination_field')
                                                ->label(__('transform::fields.destination_field'))
                                                ->datalist(fn (Get $get): array => array_values(TransformDefinition::discoverDestinationFieldOptions((string) $get('../../../destination_model'))))
                                                ->placeholder('name')
                                                ->required(),
                                            TextInput::make('source_path')
                                                ->label(__('transform::fields.source_path'))
                                                ->datalist(fn (Get $get): array => array_values(TransformDefinition::discoverSourcePathOptions(is_array($get('../../../source_references')) ? $get('../../../source_references') : [])))
                                                ->placeholder('title')
                                                ->required(),
                                        ])
                                        ->mutateDehydratedStateUsing(static function (mixed $state): array {
                                            if (! is_array($state)) {
                                                return [];
                                            }

                                            $mapped = [];
                                            foreach ($state as $row) {
                                                if (! is_array($row)) {
                                                    continue;
                                                }

                                                $destinationField = $row['destination_field'] ?? null;
                                                $sourcePath = $row['source_path'] ?? null;
                                                if (! is_string($destinationField) || $destinationField === '' || ! is_string($sourcePath) || $sourcePath === '') {
                                                    continue;
                                                }

                                                $mapped[$destinationField] = $sourcePath;
                                            }

                                            return $mapped;
                                        })
                                        ->formatStateUsing(static function (mixed $state): array {
                                            if (! is_array($state)) {
                                                return [];
                                            }

                                            $rows = [];
                                            foreach ($state as $destinationField => $sourcePath) {
                                                if (! is_string($destinationField) || $destinationField === '' || ! is_string($sourcePath) || $sourcePath === '') {
                                                    continue;
                                                }

                                                $rows[] = [
                                                    'destination_field' => $destinationField,
                                                    'source_path' => $sourcePath,
                                                ];
                                            }

                                            return $rows;
                                        }),
                                    Repeater::make('destination_match')
                                        ->label(__('transform::fields.destination_match'))
                                        ->helperText(__('transform::fields.destination_match_help'))
                                        ->collapsible()
                                        ->schema([
                                            TextInput::make('destination_field')
                                                ->label(__('transform::fields.destination_field'))
                                                ->datalist(fn (Get $get): array => array_values(TransformDefinition::discoverDestinationFieldOptions((string) $get('../../../destination_model'))))
                                                ->placeholder('external_reference')
                                                ->required()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                                                    if (! is_string($state) || $state === '' || filled($get('source_path'))) {
                                                        return;
                                                    }

                                                    $fieldMapRows = $get('../../../field_map');
                                                    if (! is_array($fieldMapRows)) {
                                                        return;
                                                    }

                                                    foreach ($fieldMapRows as $row) {
                                                        if (! is_array($row)) {
                                                            continue;
                                                        }

                                                        if (($row['destination_field'] ?? null) !== $state) {
                                                            continue;
                                                        }

                                                        $sourcePath = $row['source_path'] ?? null;
                                                        if (is_string($sourcePath) && $sourcePath !== '') {
                                                            $set('source_path', $sourcePath);
                                                        }

                                                        break;
                                                    }
                                                }),
                                            TextInput::make('source_path')
                                                ->label(__('transform::fields.source_path'))
                                                ->datalist(fn (Get $get): array => array_values(TransformDefinition::discoverSourcePathOptions(is_array($get('../../../source_references')) ? $get('../../../source_references') : [])))
                                                ->placeholder('source.email')
                                                ->required(),
                                        ])
                                        ->mutateDehydratedStateUsing(static function (mixed $state): array {
                                            if (! is_array($state)) {
                                                return [];
                                            }

                                            $mapped = [];
                                            foreach ($state as $row) {
                                                if (! is_array($row)) {
                                                    continue;
                                                }

                                                $destinationField = $row['destination_field'] ?? null;
                                                $sourcePath = $row['source_path'] ?? null;
                                                if (! is_string($destinationField) || $destinationField === '' || ! is_string($sourcePath) || $sourcePath === '') {
                                                    continue;
                                                }

                                                $mapped[$destinationField] = $sourcePath;
                                            }

                                            return $mapped;
                                        })
                                        ->formatStateUsing(static function (mixed $state): array {
                                            if (! is_array($state)) {
                                                return [];
                                            }

                                            $rows = [];
                                            foreach ($state as $destinationField => $sourcePath) {
                                                if (! is_string($destinationField) || $destinationField === '' || ! is_string($sourcePath) || $sourcePath === '') {
                                                    continue;
                                                }

                                                $rows[] = [
                                                    'destination_field' => $destinationField,
                                                    'source_path' => $sourcePath,
                                                ];
                                            }

                                            return $rows;
                                        }),
                                    Repeater::make('validation_rules')
                                        ->label(__('transform::fields.validation_rules'))
                                        ->helperText('Optional extra rules on top of model-based validation.')
                                        ->collapsible()
                                        ->schema([
                                            TextInput::make('validation_field')
                                                ->label(__('transform::fields.validation_field'))
                                                ->datalist(fn (Get $get): array => array_values(TransformDefinition::discoverDestinationFieldOptions((string) $get('../../../destination_model'))))
                                                ->placeholder('email')
                                                ->required(),
                                            TextInput::make('validation_rule')
                                                ->label(__('transform::fields.validation_rule'))
                                                ->required()
                                                ->placeholder('required|string|min:2'),
                                        ])
                                        ->mutateDehydratedStateUsing(static function (mixed $state): array {
                                            if (! is_array($state)) {
                                                return [];
                                            }

                                            $rules = [];
                                            foreach ($state as $row) {
                                                if (! is_array($row)) {
                                                    continue;
                                                }

                                                $field = $row['validation_field'] ?? null;
                                                $ruleString = $row['validation_rule'] ?? null;
                                                if (! is_string($field) || $field === '' || ! is_string($ruleString) || $ruleString === '') {
                                                    continue;
                                                }

                                                $rules[$field] = array_values(array_filter(array_map('trim', explode('|', $ruleString))));
                                            }

                                            return $rules;
                                        })
                                        ->formatStateUsing(static function (mixed $state): array {
                                            if (! is_array($state)) {
                                                return [];
                                            }

                                            $rows = [];
                                            foreach ($state as $field => $rules) {
                                                if (! is_string($field) || $field === '') {
                                                    continue;
                                                }

                                                if (is_array($rules)) {
                                                    $ruleString = implode('|', array_values(array_filter(array_map(static fn (mixed $rule): string => is_string($rule) ? $rule : '', $rules))));
                                                } elseif (is_string($rules)) {
                                                    $ruleString = $rules;
                                                } else {
                                                    continue;
                                                }

                                                if ($ruleString === '') {
                                                    continue;
                                                }

                                                $rows[] = [
                                                    'validation_field' => $field,
                                                    'validation_rule' => $ruleString,
                                                ];
                                            }

                                            return $rows;
                                        }),
                                    Section::make(__('transform::fields.section_execution'))
                                        ->schema([
                                            Select::make('execution_mode')
                                                ->label(__('transform::fields.execution_mode'))
                                                ->options(static::executionModeOptions())
                                                ->default(TransformExecutionMode::Single->value)
                                                ->required()
                                                ->live(),
                                            Section::make(__('transform::fields.expand'))
                                                ->schema([
                                                    TextInput::make('expand.dedupe_by')
                                                        ->label(__('transform::fields.expand_dedupe_by')),
                                                    Repeater::make('expand.prefer')
                                                        ->label(__('transform::fields.expand_prefer'))
                                                        ->schema([
                                                            TextInput::make('path')
                                                                ->label(__('transform::fields.expand_prefer_path'))
                                                                ->required(),
                                                            TextInput::make('equals')
                                                                ->label(__('transform::fields.expand_prefer_equals'))
                                                                ->required(),
                                                        ])
                                                        ->default([]),
                                                    Section::make(__('transform::fields.expand_locales'))
                                                        ->schema([
                                                            TextInput::make('expand.locales.source')
                                                                ->label(__('transform::fields.expand_locales_source')),
                                                            TextInput::make('expand.locales.language_key')
                                                                ->label(__('transform::fields.expand_locales_language_key'))
                                                                ->default('language'),
                                                            TextInput::make('expand.locales.alias')
                                                                ->label(__('transform::fields.expand_locales_alias'))
                                                                ->default('lang'),
                                                            TextInput::make('expand.locales.locale_field')
                                                                ->label(__('transform::fields.expand_locales_locale_field'))
                                                                ->default('locale'),
                                                            TextInput::make('expand.locales.only')
                                                                ->label(__('transform::fields.expand_locales_only'))
                                                                ->helperText(__('transform::fields.expand_locales_only_help'))
                                                                ->dehydrateStateUsing(static function (mixed $state): ?array {
                                                                    if (! is_string($state) || trim($state) === '') {
                                                                        return null;
                                                                    }

                                                                    return array_values(array_filter(array_map('trim', explode(',', $state))));
                                                                })
                                                                ->formatStateUsing(static function (mixed $state): string {
                                                                    if (! is_array($state)) {
                                                                        return '';
                                                                    }

                                                                    return implode(', ', array_map(strval(...), $state));
                                                                }),
                                                        ])
                                                        ->columns(2),
                                                    Section::make(__('transform::fields.expand_nested'))
                                                        ->schema([
                                                            TextInput::make('expand.nested.path')
                                                                ->label(__('transform::fields.expand_nested_path')),
                                                            TextInput::make('expand.nested.alias')
                                                                ->label(__('transform::fields.expand_nested_alias'))
                                                                ->default('nested'),
                                                            TextInput::make('expand.nested.dedupe_by')
                                                                ->label(__('transform::fields.expand_nested_dedupe_by')),
                                                        ])
                                                        ->columns(2),
                                                ])
                                                ->visible(fn (Get $get): bool => in_array((string) $get('execution_mode'), [TransformExecutionMode::Expand->value, TransformExecutionMode::Bulk->value], true)),
                                            Section::make(__('transform::fields.bulk'))
                                                ->schema([
                                                    TextInput::make('bulk.chunk_size')
                                                        ->label(__('transform::fields.bulk_chunk_size'))
                                                        ->numeric()
                                                        ->integer()
                                                        ->default((int) config('transform.bulk.chunk_size', 100))
                                                        ->minValue(1),
                                                    Toggle::make('bulk.persist_children')
                                                        ->label(__('transform::fields.bulk_persist_children'))
                                                        ->default((bool) config('transform.bulk.persist_children', true)),
                                                    Select::make('bulk.write_strategy')
                                                        ->label(__('transform::fields.bulk_write_strategy'))
                                                        ->options([
                                                            'row' => __('transform::fields.bulk_write_strategy_row'),
                                                            'batch' => __('transform::fields.bulk_write_strategy_batch'),
                                                        ])
                                                        ->default((string) config('transform.bulk.write_strategy', 'row')),
                                                ])
                                                ->visible(fn (Get $get): bool => $get('execution_mode') === TransformExecutionMode::Bulk->value),
                                        ]),
                                    Toggle::make('is_active')
                                        ->label(__('transform::fields.is_active'))
                                        ->default(true)
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
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('destination_model')
                    ->label(__('transform::fields.destination_model'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('execution_mode')
                    ->label(__('transform::fields.execution_mode'))
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('records_count')
                    ->counts('records')
                    ->label(__('transform::fields.records_count'))
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                ...static::getTableActions(),
                Action::make('runAllForEndpoint')
                    ->label(__('transform::fields.run_all_for_endpoint'))
                    ->icon('heroicon-o-play-circle')
                    ->color('warning')
                    ->visible(fn (TransformDefinition $record): bool => static::requiresImportRecordContext($record)
                        && ImportRecordSelectOptionBuilder::fromConfig() instanceof ImportRecordSelectOptionBuilder)
                    ->form(fn (): array => [
                        Select::make('api_endpoint_id')
                            ->label(__('transform::fields.run_all_endpoint'))
                            ->helperText(__('transform::fields.run_all_for_endpoint_help'))
                            ->options(fn (): array => ImportRecordSelectOptionBuilder::fromConfig()?->endpointSelectOptions() ?? [])
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (TransformDefinition $record, array $data): void {
                        $endpointId = (int) ($data['api_endpoint_id'] ?? 0);
                        $count = static::countImportRecordsForEndpoint($endpointId);

                        DispatchTransformDefinitionForEndpointJob::dispatch(
                            (int) $record->getKey(),
                            $endpointId,
                        );

                        Notification::make()
                            ->title(__('transform::fields.run_all_queued_title'))
                            ->body(__('transform::fields.run_all_background_body', ['count' => $count]))
                            ->success()
                            ->send();
                    }),
                Action::make('run')
                    ->label(__('transform::fields.run'))
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->form(fn (TransformDefinition $record): array => static::runFormSchema($record))
                    ->action(function (TransformDefinition $record, array $data): void {
                        $transformRecord = TransformRecord::query()->create([
                            'transform_definition_id' => $record->getKey(),
                            'source_projection' => static::buildRunSourceProjection($record, $data),
                            'source_references' => $record->source_references,
                            'status' => 'pending',
                            'validation_status' => 'pending',
                        ]);

                        RunTransformRecordJob::dispatch((int) $transformRecord->getKey());

                        Notification::make()
                            ->title(__('transform::fields.run_queued_title'))
                            ->body(__('transform::fields.run_queued_body'))
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([...static::getBulkActions()])
            ->filters([
                SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransformDefinitions::route('/'),
            'create' => Pages\CreateTransformDefinition::route('/create'),
            'edit' => Pages\EditTransformDefinition::route('/{record}/edit'),
            'view' => Pages\ViewTransformDefinition::route('/{record}'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            TransformRecordsRelationManager::class,
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function discoverModelOptions(): array
    {
        $models = [];

        foreach (static::modelScanDirectories() as $directory) {
            if (! File::isDirectory($directory)) {
                continue;
            }

            foreach (File::allFiles($directory) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $class = static::resolveModelClassFromFile($file->getPathname());
                if ($class !== null && class_exists($class) && is_subclass_of($class, Model::class)) {
                    $models[$class] = $class;
                }
            }
        }

        ksort($models);

        return $models;
    }

    /**
     * @return list<string>
     */
    private static function modelScanDirectories(): array
    {
        $directories = [app_path('Models')];

        foreach (config('transform.additional_model_scan_paths', []) as $path) {
            if (is_string($path) && $path !== '') {
                $directories[] = $path;
            }
        }

        return array_values(array_unique($directories));
    }

    private static function resolveModelClassFromFile(string $path): ?string
    {
        $contents = File::get($path);

        if (! preg_match('/namespace\s+([^;]+);/m', $contents, $namespaceMatch)) {
            return null;
        }

        if (! preg_match('/\bclass\s+([A-Za-z_][A-Za-z0-9_]*)\b/m', $contents, $classMatch)) {
            return null;
        }

        $namespace = trim($namespaceMatch[1]);
        $class = trim($classMatch[1]);

        if ($namespace === '' || $class === '') {
            return null;
        }

        return $namespace.'\\'.$class;
    }

    /**
     * @return list<Component>
     */
    private static function runFormSchema(TransformDefinition $definition): array
    {
        if (! static::requiresImportRecordContext($definition)) {
            return [];
        }

        $contextKey = static::importRecordContextKey();
        $optionBuilder = ImportRecordSelectOptionBuilder::fromConfig();
        if ($optionBuilder instanceof ImportRecordSelectOptionBuilder) {
            return [
                Select::make($contextKey)
                    ->label(__('transform::fields.import_record_context'))
                    ->helperText(__('transform::fields.import_record_context_help'))
                    ->options(fn (): array => $optionBuilder->groupedOptions())
                    ->getSearchResultsUsing(fn (string $search): array => $optionBuilder->groupedOptions($search))
                    ->getOptionLabelUsing(fn (mixed $value): string => $optionBuilder->labelForId((int) $value)
                        ?? '#'.(int) $value)
                    ->searchable()
                    ->required(),
            ];
        }

        return [
            TextInput::make($contextKey)
                ->label(__('transform::fields.import_record_id'))
                ->numeric()
                ->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildRunSourceProjection(TransformDefinition $definition, array $data): array
    {
        $configured = config('transform.default_source_projection');
        $projection = is_array($configured) ? $configured : [];

        if (! static::requiresImportRecordContext($definition)) {
            return $projection;
        }

        $contextKey = static::importRecordContextKey();
        $importRecordId = (int) ($data[$contextKey] ?? 0);
        if ($importRecordId <= 0) {
            throw new \InvalidArgumentException("{$contextKey} is required.");
        }

        $context = is_array($projection['context'] ?? null) ? $projection['context'] : [];
        $context[$contextKey] = $importRecordId;
        $projection['context'] = $context;

        return app(ConfiguredImportRecordProjectionEnricher::class)->enrich($importRecordId, $projection);
    }

    private static function countImportRecordsForEndpoint(int $endpointId): int
    {
        if ($endpointId <= 0) {
            throw new \InvalidArgumentException('api_endpoint_id is required.');
        }

        $importRecordModel = config('transform.import_record_model');
        if (! is_string($importRecordModel) || $importRecordModel === '' || ! class_exists($importRecordModel) || ! is_subclass_of($importRecordModel, Model::class)) {
            throw new \RuntimeException('Import record model is not configured.');
        }

        $foreignKey = config('transform.import_record_endpoint_foreign_key', 'api_endpoint_id');
        if (! is_string($foreignKey) || $foreignKey === '') {
            $foreignKey = 'api_endpoint_id';
        }

        /** @var Model $prototype */
        $prototype = new $importRecordModel;

        return (int) $prototype->newQuery()->where($foreignKey, $endpointId)->count();
    }

    private static function dispatchAllForEndpoint(TransformDefinition $definition, int $endpointId): int
    {
        if ($endpointId <= 0) {
            throw new \InvalidArgumentException('api_endpoint_id is required.');
        }

        $importRecordModel = config('transform.import_record_model');
        if (! is_string($importRecordModel) || $importRecordModel === '' || ! class_exists($importRecordModel) || ! is_subclass_of($importRecordModel, Model::class)) {
            throw new \RuntimeException('Import record model is not configured.');
        }

        $foreignKey = config('transform.import_record_endpoint_foreign_key', 'api_endpoint_id');
        if (! is_string($foreignKey) || $foreignKey === '') {
            $foreignKey = 'api_endpoint_id';
        }

        /** @var Model $prototype */
        $prototype = new $importRecordModel;
        $dispatched = 0;

        $prototype->newQuery()
            ->where($foreignKey, $endpointId)
            ->orderBy($prototype->getKeyName())
            ->chunkById(100, function ($records) use ($definition, &$dispatched): void {
                foreach ($records as $importRecord) {
                    if (! $importRecord instanceof Model) {
                        continue;
                    }

                    $transformRecord = TransformRecord::query()->create([
                        'transform_definition_id' => $definition->getKey(),
                        'source_projection' => static::buildRunSourceProjection($definition, [
                            static::importRecordContextKey() => (int) $importRecord->getKey(),
                        ]),
                        'source_references' => $definition->source_references,
                        'status' => 'pending',
                        'validation_status' => 'pending',
                    ]);

                    RunTransformRecordJob::dispatch((int) $transformRecord->getKey());
                    $dispatched++;
                }
            });

        return $dispatched;
    }

    private static function requiresImportRecordContext(TransformDefinition $definition): bool
    {
        $references = $definition->source_references;
        if (! is_array($references)) {
            return false;
        }

        $template = (string) config('transform.default_import_record_id_template', '{{context.import_record_id}}');
        $contextKey = static::importRecordContextKey();

        foreach ($references as $reference) {
            if (! is_array($reference)) {
                continue;
            }

            if (($reference['source_type'] ?? null) !== 'api_import_record') {
                continue;
            }

            $recordId = $reference['record_id'] ?? null;
            if (is_string($recordId) && str_contains($recordId, "context.{$contextKey}")) {
                return true;
            }

            if (is_string($recordId) && $recordId === $template) {
                return true;
            }
        }

        return false;
    }

    private static function importRecordContextKey(): string
    {
        $contextKey = config('transform.import_record_context_key', 'import_record_id');

        return is_string($contextKey) && $contextKey !== '' ? $contextKey : 'import_record_id';
    }

    /**
     * @return array<string, string>
     */
    private static function sourceTypeOptions(): array
    {
        return [
            'db_table' => __('transform::fields.source_type_db_table'),
            'file_json' => __('transform::fields.source_type_file_json'),
            'file_csv' => __('transform::fields.source_type_file_csv'),
            'api' => __('transform::fields.source_type_api'),
            'api_import_record' => __('transform::fields.source_type_api_import_record'),
            'static' => __('transform::fields.source_type_static'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function executionModeOptions(): array
    {
        return [
            TransformExecutionMode::Single->value => __('transform::fields.execution_mode_single'),
            TransformExecutionMode::Expand->value => __('transform::fields.execution_mode_expand'),
            TransformExecutionMode::Bulk->value => __('transform::fields.execution_mode_bulk'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function whereOperatorOptions(): array
    {
        return [
            '=' => '=',
            '!=' => '!=',
            '<' => '<',
            '>' => '>',
            '<=' => '<=',
            '>=' => '>=',
            'in' => 'in',
            'null' => 'null',
            'not_null' => 'not_null',
        ];
    }

    private static function isSourceType(Get $get, array $types): bool
    {
        return in_array((string) $get('source_type'), $types, true);
    }
}
