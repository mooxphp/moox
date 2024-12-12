<?php

namespace Moox\Sync\Resources;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;
use Moox\Core\Traits\Tabs\TabsInResource;
use Moox\Sync\Models\Platform;
use Moox\Sync\Models\Sync;
use Moox\Sync\Resources\SyncResource\Pages\CreateSync;
use Moox\Sync\Resources\SyncResource\Pages\EditSync;
use Moox\Sync\Resources\SyncResource\Pages\ListSyncs;
use Moox\Sync\Resources\SyncResource\Pages\ViewSync;
use Moox\Sync\Services\ModelCompatibilityChecker;

class SyncResource extends Resource
{
    use TabsInResource;

    protected static ?string $model = Sync::class;

    protected static ?string $navigationIcon = 'gmdi-sync';

    protected static ?string $recordTitleAttribute = 'title';

    private static function generateTitle(callable $get)
    {
        if (! $get('source_platform_id') || ! $get('target_platform_id')) {
            return '';
        }

        $sourceModel = $get('source_model');
        $sourcePlatform = Platform::find($get('source_platform_id'));
        $targetModel = $get('target_model');
        $targetPlatform = Platform::find($get('target_platform_id'));
        $usePlatformRelations = $get('use_platform_relations');

        if (! $sourcePlatform || ! $targetPlatform) {
            return '';
        }

        $title = "{$sourcePlatform->domain} ({$sourceModel}) to {$targetPlatform->domain} ({$targetModel})";

        if ($usePlatformRelations) {
            $title .= ' by platform';
        }

        return $title;
    }

    private static function updateTitle(callable $set, callable $get)
    {
        $title = self::generateTitle($get);
        $set('title', $title);
    }

    private static function getApiUrl(?Platform $platform): ?string
    {
        return $platform ? "https://{$platform->domain}/api/models" : null;
    }

    private static function fetchModelsFromApi(string $apiUrl, Platform $platform): array
    {
        try {
            $response = Http::get($apiUrl);

            if ($response->failed()) {
                Notification::make()
                    ->title('API Error')
                    ->body(__('An error occurred while fetching the models from platform: ').$platform->name.' ('.$platform->domain.')')
                    ->danger()
                    ->send();

                return [];
            }

            $data = $response->json();
            $options = [];

            foreach ($data['models'] as $model) {
                $package = str_replace('Models', ' - ', str_replace('\\', ' ', $model));
                if ($package && $model) {
                    $options["{$package}"] = "{$model}";
                }
            }

            return array_filter(array_flip($options)); // Remove any null values
        } catch (\Exception $e) {
            Notification::make()
                ->title('API Error')
                ->body(__('An error occurred while fetching the models from platform: ').$platform->name.' ('.$platform->domain.')')
                ->danger()
                ->send();

            return [];
        }
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    Toggle::make('status')
                        ->label(__('core::core.status'))
                        ->columnSpan(['default' => 12])
                        ->default(true)
                        ->reactive()
                        ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::updateTitle($set, $get)),

                    Select::make('source_platform_id')
                        ->label(__('core::sync.source_platform_id'))
                        ->rules(['exists:platforms,id'])
                        ->required()
                        ->relationship('sourcePlatform', 'name')
                        ->options(function () {
                            return Platform::all()->mapWithKeys(function ($platform) {
                                return [$platform->id => $platform->name ?? "Platform {$platform->id}"];
                            })->toArray();
                        })
                        ->columnSpan(['default' => 12])
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

                    Select::make('source_model')
                        ->label(__('core::sync.source_model'))
                        ->options(function (callable $get) {
                            $sourcePlatform = Platform::find($get('source_platform_id'));
                            $apiUrl = self::getApiUrl($sourcePlatform);

                            $options = $apiUrl ? self::fetchModelsFromApi($apiUrl, $sourcePlatform) : [];

                            return collect($options)->filter(function ($value, $key) {
                                return $key !== null && $value !== null;
                            })->toArray();
                        })
                        ->rules(['max:255'])
                        ->required()
                        ->reactive()
                        ->columnSpan(['default' => 12])
                        ->hint(function (callable $get) {
                            $sourcePlatform = Platform::find($get('source_platform_id'));

                            return $sourcePlatform ? new HtmlString('<a href="'.self::getApiUrl($sourcePlatform).'" target="_blank">Test API</a>') : null;
                        })
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $sourcePlatformId = $get('source_platform_id');
                            $sourceModel = $get('source_model');
                            $targetPlatformId = $get('target_platform_id');
                            $targetModel = $get('target_model');

                            if ($sourcePlatformId === $targetPlatformId && $sourceModel === $targetModel) {
                                $set('source_model', null);

                                Notification::make()
                                    ->title('Sync Error')
                                    ->body(__('You cannot sync the same platform and model as source and target.'))
                                    ->danger()
                                    ->send();
                            } else {
                                self::updateTitle($set, $get);
                                self::checkModelCompatibility($set, $get);
                            }
                        }),

                    Select::make('target_platform_id')
                        ->label(__('core::sync.target_platform_id'))
                        ->rules(['exists:platforms,id'])
                        ->required()
                        ->relationship('targetPlatform', 'name')
                        ->options(function () {
                            return Platform::all()->mapWithKeys(function ($platform) {
                                return [$platform->id => $platform->name ?? "Platform {$platform->id}"];
                            })->toArray();
                        })
                        ->columnSpan(['default' => 12])
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

                    Select::make('target_model')
                        ->label(__('core::sync.target_model'))
                        ->options(function (callable $get) {
                            $targetPlatform = Platform::find($get('target_platform_id'));
                            $apiUrl = self::getApiUrl($targetPlatform);

                            $options = $apiUrl ? self::fetchModelsFromApi($apiUrl, $targetPlatform) : [];

                            return collect($options)->filter(function ($value, $key) {
                                return $key !== null && $value !== null;
                            })->toArray();
                        })
                        ->rules(['max:255'])
                        ->required()
                        ->reactive()
                        ->columnSpan(['default' => 12])
                        ->hint(function (callable $get) {
                            $targetPlatform = Platform::find($get('target_platform_id'));

                            return $targetPlatform ? new HtmlString('<a href="'.self::getApiUrl($targetPlatform).'" target="_blank">Test API</a>') : null;
                        })
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $sourcePlatformId = $get('source_platform_id');
                            $sourceModel = $get('source_model');
                            $targetPlatformId = $get('target_platform_id');
                            $targetModel = $get('target_model');

                            if ($sourcePlatformId === $targetPlatformId && $sourceModel === $targetModel) {
                                $set('target_model', null);

                                Notification::make()
                                    ->title('Sync Error')
                                    ->body(__('You cannot sync the same platform and model as source and target.'))
                                    ->danger()
                                    ->send();
                            } else {
                                self::updateTitle($set, $get);
                                self::checkModelCompatibility($set, $get);
                            }
                        }),

                    Toggle::make('use_platform_relations')
                        ->label(__('core::sync.use_platform_relations'))
                        ->columnSpan(['default' => 12])
                        ->reactive()
                        ->afterStateUpdated(fn ($state, callable $set, callable $get) => self::updateTitle($set, $get)),

                    Hidden::make('if_exists')
                        ->default('update'),

                    Hidden::make('title')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->default(fn (callable $get) => SyncResource::generateTitle($get))
                        ->reactive(),

                    Hidden::make('models_compatible')
                        ->default(true),

                    Hidden::make('compatibility_error'),

                    Hidden::make('missing_columns'),

                    Hidden::make('extra_columns'),
                ]),
            ]),
        ]);
    }

    private static function checkModelCompatibility(callable $set, callable $get)
    {
        $sourceModel = $get('source_model');
        $targetModel = $get('target_model');

        if ($sourceModel && $targetModel) {
            $compatibility = ModelCompatibilityChecker::checkCompatibility($sourceModel, $targetModel);

            $set('models_compatible', $compatibility['compatible']);
            $set('compatibility_error', $compatibility['error']);
            $set('missing_columns', $compatibility['missingColumns']);
            $set('extra_columns', $compatibility['extraColumns']);

            if (! $compatibility['compatible']) {
                $missingColumnsStr = implode(', ', $compatibility['missingColumns']);
                $extraColumnsStr = implode(', ', $compatibility['extraColumns']);

                Notification::make()
                    ->title(__('core::sync.model_compatibility_warning'))
                    ->body(__('core::sync.models_are_not_fully_compatible').'<br><br>'.
                    __('core::sync.missing_columns').':<br>'.$missingColumnsStr.'<br><br>'.
                    __('core::sync.extra_columns').':<br>'.$extraColumnsStr.'<br><br>'.
                    __('core::sync.please_map_fields_manually'))
                    ->warning()
                    ->send();
            }
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('sourcePlatformAndModel')
                    ->label(__('core::sync.source_platform_and_model'))
                    ->toggleable()
                    ->getStateUsing(fn ($record) => "{$record->sourcePlatform->name} ({$record->source_model})")
                    ->limit(50),
                TextColumn::make('targetPlatformAndModel')
                    ->label(__('core::sync.target_platform_and_model'))
                    ->toggleable()
                    ->getStateUsing(fn ($record) => "{$record->targetPlatform->name} ({$record->target_model})")
                    ->limit(50),
                IconColumn::make('use_platform_relations')
                    ->label(__('core::sync.use_platform_relations'))
                    ->toggleable()
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->getStateUsing(fn ($record) => $record->use_platform_relations),
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
        return config('sync.resources.sync.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('sync.resources.sync.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('sync.resources.sync.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('sync.resources.sync.single');
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
