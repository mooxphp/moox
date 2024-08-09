<?php

namespace Moox\Sync\Resources;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Moox\Sync\Models\Platform;
use Moox\Sync\Models\Sync;
use Moox\Sync\Resources\SyncResource\Pages\CreateSync;
use Moox\Sync\Resources\SyncResource\Pages\EditSync;
use Moox\Sync\Resources\SyncResource\Pages\ListSyncs;
use Moox\Sync\Resources\SyncResource\Pages\ViewSync;

class SyncResource extends Resource
{
    protected static ?string $model = Sync::class;

    protected static ?string $navigationIcon = 'gmdi-sync';

    protected static ?string $recordTitleAttribute = 'title';

    private static function generateTitle(callable $get)
    {
        if (! $get('source_platform_id') || ! $get('target_platform_id')) {
            return '';
        }

        $status = $get('status');
        $sourceModel = $get('source_model');
        $sourcePlatform = Platform::find($get('source_platform_id'));
        $targetModel = $get('target_model');
        $targetPlatform = Platform::find($get('target_platform_id'));
        $usePlatformRelations = $get('use_platform_relations');
        $useTransformerClass = $get('use_transformer_class');
        $filterIds = $get('filter_ids');
        $fieldMappings = $get('field_mappings');
        $interval = $get('interval');

        if (! $sourcePlatform || ! $targetPlatform) {
            return '';
        }

        $sync_status = $status ? '' : 'Disabled: ';
        $sync_action = $useTransformerClass ? 'Transform' : 'Sync';

        $title = "{$sync_status}{$sync_action} {$sourcePlatform->domain} ({$sourceModel}) to {$targetPlatform->domain} ({$targetModel})";

        if ($filterIds == 'sync_only_ids') {
            $title .= ' partially';
        } elseif ($filterIds == 'ignore_ids') {
            $title .= ' excluding records';
        }

        if ($usePlatformRelations) {
            $title .= ' by platform';
        }

        if ($fieldMappings) {
            $title .= ' with mapping';
        }

        if ($interval == 1) {
            $title .= ' every minute';
        } else {
            $title .= " every {$interval} minutes";
        }

        return $title;
    }

    private static function updateTitle(callable $set, callable $get)
    {
        $title = self::generateTitle($get);
        $set('title', $title);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    Toggle::make('status')
                        ->label(__('core::common.status'))
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ])
                        ->default(true)
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            self::updateTitle($set, $get);
                        }),

                    Select::make('source_platform_id')
                        ->label(__('core::sync.source_platform_id'))
                        ->rules(['exists:platforms,id'])
                        ->required()
                        ->relationship('sourcePlatform', 'name')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ])
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $sourceModel = $get('source_model');
                            $targetPlatformId = $get('target_platform_id');
                            $targetModel = $get('target_model');
                            if ($state === $targetPlatformId && $sourceModel === $targetModel) {
                                $set('source_platform_id', null);

                                Notification::make()
                                    ->title(__('core::sync.sync_error'))
                                    ->body(__('core::sync.sync_error_platforms'))
                                    ->danger()
                                    ->send();
                            } else {
                                self::updateTitle($set, $get);
                            }
                        }),

                    TextInput::make('source_model')
                        ->label(__('core::sync.source_model'))
                        ->rules(['max:255'])
                        ->required()
                        ->reactive()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ])
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $sourcePlatformId = $get('source_platform_id');
                            $targetPlatformId = $get('target_platform_id');
                            $targetModel = $get('target_model');
                            if ($sourcePlatformId === $targetPlatformId && $state === $targetModel) {
                                $set('source_model', null);

                                Notification::make()
                                    ->title('Sync Error')
                                    ->body(__('core::sync.sync_error_platforms'))
                                    ->danger()
                                    ->send();
                            } else {
                                self::updateTitle($set, $get);
                            }
                        }),

                    Select::make('target_platform_id')
                        ->label(__('core::sync.target_platform_id'))
                        ->rules(['exists:platforms,id'])
                        ->required()
                        ->relationship('targetPlatform', 'name')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ])
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $sourcePlatformId = $get('source_platform_id');
                            $sourceModel = $get('source_model');
                            $targetModel = $get('target_model');
                            if ($state === $sourcePlatformId && $sourceModel === $targetModel) {
                                $set('target_platform_id', null);

                                Notification::make()
                                    ->title('Sync Error')
                                    ->body(__('core::sync.sync_error_platforms'))
                                    ->danger()
                                    ->send();
                            } else {
                                self::updateTitle($set, $get);
                            }
                        }),

                    TextInput::make('target_model')
                        ->label(__('core::sync.target_model'))
                        ->rules(['max:255'])
                        ->required()
                        ->reactive()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ])
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $sourcePlatformId = $get('source_platform_id');
                            $sourceModel = $get('source_model');
                            $targetPlatformId = $get('target_platform_id');
                            if ($targetPlatformId === $sourcePlatformId && $state === $sourceModel) {
                                $set('target_model', null);

                                Notification::make()
                                    ->title('Sync Error')
                                    ->body(__('core::sync.sync_error_platforms'))
                                    ->danger()
                                    ->send();
                            } else {
                                self::updateTitle($set, $get);
                            }
                        }),

                    TextInput::make('interval')
                        ->label(__('core::common.interval'))
                        ->rules(['integer'])
                        ->default(60)
                        ->suffix(fn ($get) => $get('interval') == 1 ? 'minute' : 'minutes')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ])
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            self::updateTitle($set, $get);
                        }),

                    Toggle::make('use_platform_relations')
                        ->label(__('core::sync.use_platform_relations'))
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ])
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            self::updateTitle($set, $get);
                        }),

                    Select::make('if_exists')
                        ->label(__('core::sync.if_exists'))
                        ->options([
                            'update' => 'Update',
                            'skip' => 'Skip',
                            'error' => 'Error',
                        ])
                        ->required()
                        ->default('update')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    Select::make('filter_ids')
                        ->label(__('core::sync.filter_ids'))
                        ->options([
                            'sync_all_records' => 'Sync all records',
                            'sync_only_ids' => 'Sync these IDs only',
                            'ignore_ids' => 'Specify IDs to ignore',
                        ])
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ])
                        ->default('sync_all_records')
                        ->label(__('core::sync.sync_all_records'))
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            self::updateTitle($set, $get);
                        }),

                    TextInput::make('sync_only_ids')
                        ->label(__('core::sync.sync_only_ids'))
                        ->rules(['array'])
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ])
                        ->visible(fn ($get) => $get('filter_ids') === 'sync_only_ids')
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            self::updateTitle($set, $get);
                        }),

                    TextInput::make('ignore_ids')
                        ->label(__('core::sync.ingnore_ids'))
                        ->rules(['array'])
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ])
                        ->visible(fn ($get) => $get('filter_ids') === 'ignore_ids')
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            self::updateTitle($set, $get);
                        }),

                    Toggle::make('sync_all_fields')
                        ->label(__('core::sync.sync_all_fields'))
                        ->default(true)
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ])
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            self::updateTitle($set, $get);
                        }),

                    KeyValue::make('field_mappings')
                        ->label(__('core::sync.field_mappings'))
                        ->rules(['array'])
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ])
                        ->hidden(fn ($get) => $get('sync_all_fields'))
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            self::updateTitle($set, $get);
                        }),

                    TextInput::make('use_transformer_class')
                        ->label(__('core::sync.use_transformer_class'))
                        ->rules(['max:255'])
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ])
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            self::updateTitle($set, $get);
                        }),

                    Toggle::make('has_errors')
                        ->label(__('core::common.has_errors'))
                        ->rules(['boolean'])
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ])
                        ->reactive(),

                    TextInput::make('error_message')
                        ->label(__('core::common.error_message'))
                        ->rules(['max:255'])
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ])
                        ->visible(fn ($get) => $get('has_errors')),

                    TextInput::make('title')
                        ->label(__('core::common.title'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ])
                        ->default(function (callable $get) {
                            return SyncResource::generateTitle($get);
                        })
                        ->reactive(),

                    DatePicker::make('last_sync')
                        ->label(__('core::sync.last_sync'))
                        ->rules(['date'])
                        ->disabled()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('sourcePlatformAndModel')
                    ->label(__('core::sync.source_platform_and_model'))
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        return "{$record->sourcePlatform->name} ({$record->source_model})";
                    })
                    ->limit(50),
                TextColumn::make('targetPlatformAndModel')
                    ->label(__('core::sync.target_platform_and_model'))
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        return "{$record->targetPlatform->name} ({$record->target_model})";
                    })
                    ->limit(50),
                IconColumn::make('use_platform_relations')
                    ->label(__('core::sync.use_platform_relations'))
                    ->toggleable()
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->getStateUsing(fn ($record) => $record->use_platform_relations),
                IconColumn::make('filter_ids')
                    ->label(__('core::sync.filter_ids'))
                    ->toggleable()
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->getStateUsing(fn ($record) => empty($record->field_mappings)),
                IconColumn::make('sync_all_fields')
                    ->label(__('core::sync.sync_all_fields'))
                    ->toggleable()
                    ->boolean()
                    ->getStateUsing(fn ($record) => ! $record->sync_all_fields),
                IconColumn::make('has_errors')
                    ->label(__('core::common.has_errors'))
                    ->toggleable()
                    ->boolean(),
                TextColumn::make('last_sync')
                    ->label(__('core::sync.last_sync'))
                    ->toggleable()
                    ->date()
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('source_platform_id')
                    ->label(__('core::sync.source_platform_id'))
                    ->relationship('sourcePlatform', 'name')
                    ->indicator('Platform')
                    ->multiple(),

                SelectFilter::make('target_platform_id')
                    ->label(__('core::sync.target_platform_id'))
                    ->relationship('targetPlatform', 'name')
                    ->indicator('Platform')
                    ->multiple(),
            ])
            ->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSyncs::route('/'),
            'create' => CreateSync::route('/create'),
            'view' => ViewSync::route('/{record}'),
            'edit' => EditSync::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return config('sync.sync.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('sync.sync.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('sync.sync.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('sync.sync.single');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationBadge(): ?string
    {
        return number_format(static::getModel()::count());
    }

    public static function getNavigationGroup(): ?string
    {
        return config('sync.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('sync.navigation_sort') + 1;
    }
}
