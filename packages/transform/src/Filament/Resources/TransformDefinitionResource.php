<?php

declare(strict_types=1);

namespace Moox\Transform\Filament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
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
use Moox\Transform\Filament\Resources\TransformDefinitionResource\Pages;
use Moox\Transform\Filament\Resources\TransformDefinitionResource\RelationManagers\TransformRecordsRelationManager;
use Moox\Transform\Jobs\RunTransformRecordJob;
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Models\TransformRecord;

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
                                                ->options([
                                                    'db_table' => 'Database table',
                                                    'file_json' => 'JSON file',
                                                    'file_csv' => 'CSV file',
                                                    'api' => 'API endpoint',
                                                ])
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(function (Set $set): void {
                                                    $set('connection', TransformDefinition::defaultConnectionName());
                                                    $set('table', null);
                                                    $set('columns', []);
                                                    $set('key_column', null);
                                                    $set('row_key', null);
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
                                                ->required(fn (Get $get): bool => $get('source_type') === 'file_csv')
                                                ->visible(fn (Get $get): bool => in_array((string) $get('source_type'), ['db_table', 'file_csv'], true)),
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
                                                ->helperText('Dot notation for nested payload selection.'),
                                            TextInput::make('url')
                                                ->label(__('transform::fields.url'))
                                                ->required(fn (Get $get): bool => $get('source_type') === 'api')
                                                ->visible(fn (Get $get): bool => $get('source_type') === 'api')
                                                ->url(),
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
                                        ->helperText('Optional: define unique match keys for update-or-create behavior.')
                                        ->collapsible()
                                        ->schema([
                                            TextInput::make('destination_field')
                                                ->label(__('transform::fields.destination_field'))
                                                ->datalist(fn (Get $get): array => array_values(TransformDefinition::discoverDestinationFieldOptions((string) $get('../../../destination_model'))))
                                                ->placeholder('email')
                                                ->required(),
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
                Action::make('run')
                    ->label('Run')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->action(function (TransformDefinition $record): void {
                        $transformRecord = TransformRecord::query()->create([
                            'transform_definition_id' => $record->getKey(),
                            'source_references' => $record->source_references,
                            'status' => 'pending',
                            'validation_status' => 'pending',
                        ]);

                        RunTransformRecordJob::dispatch((int) $transformRecord->getKey());

                        Notification::make()
                            ->title('Transform queued')
                            ->body('A new transform record was created and dispatched.')
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

        foreach (File::allFiles(app_path('Models')) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $class = static::resolveModelClassFromFile($file->getPathname());
            if ($class !== null && class_exists($class) && is_subclass_of($class, Model::class)) {
                $models[$class] = $class;
            }
        }

        $packageRoot = base_path('packages');
        if (! File::isDirectory($packageRoot)) {
            ksort($models);

            return $models;
        }

        foreach (File::directories($packageRoot) as $packagePath) {
            $modelsPath = $packagePath.'/src/Models';
            if (! File::isDirectory($modelsPath)) {
                continue;
            }

            foreach (File::allFiles($modelsPath) as $modelFile) {
                if ($modelFile->getExtension() !== 'php') {
                    continue;
                }

                $class = static::resolveModelClassFromFile($modelFile->getPathname());
                if ($class !== null && class_exists($class) && is_subclass_of($class, Model::class)) {
                    $models[$class] = $class;
                }
            }
        }

        ksort($models);

        return $models;
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
}
