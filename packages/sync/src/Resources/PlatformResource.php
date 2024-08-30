<?php

namespace Moox\Sync\Resources;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Services\DnsLookupService;
use Moox\Sync\Models\Platform;
use Moox\Sync\Resources\PlatformResource\Pages\CreatePlatform;
use Moox\Sync\Resources\PlatformResource\Pages\EditPlatform;
use Moox\Sync\Resources\PlatformResource\Pages\ListPlatforms;
use Moox\Sync\Resources\PlatformResource\Pages\ViewPlatform;

class PlatformResource extends Resource
{
    protected static ?string $model = Platform::class;

    protected static ?string $navigationIcon = 'gmdi-dns';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    TextInput::make('name')
                        ->label(__('core::core.name'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('domain')
                        ->label(__('core::core.domain'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->live(debounce: 500)
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (empty($state)) {
                                $set('ip_address', 'The host is not resolvable');
                            } else {
                                $ipAddress = DnsLookupService::getIpAddress($state);
                                $set('ip_address', $ipAddress ?: 'The host is not resolvable');
                            }
                        })
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('ip_address')
                        ->label(__('core::core.ip_address'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    FileUpload::make('thumbnail')
                        ->label(__('core::core.thumbnail'))
                        ->rules(['file'])
                        ->nullable()
                        ->image()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('api_token')
                        ->label(__('core::sync.api_token'))
                        ->rules(['max:80'])
                        ->unique(ignoreRecord: true)
                        ->nullable()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ])
                        ->suffixAction(
                            Action::make('generateToken')
                                ->label(__('core::sync.generate_token'))
                                ->icon('gmdi-generating-tokens')
                                ->action('generateToken')
                                ->hidden(fn ($livewire) => $livewire instanceof ViewRecord)
                        ),

                    Toggle::make('master')
                        ->label(__('core::core.master'))
                        ->rules(['boolean'])
                        ->nullable()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ])
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if ($state) {
                                $existingMaster = Platform::where('master', true)
                                    ->where('id', '!=', $get('id'))
                                    ->first();

                                if ($existingMaster) {
                                    $set('master', false);
                                    Notification::make()
                                        ->title(__('core::sync.sync_error'))
                                        ->body(__('core::sync.sync_error_master'))
                                        ->danger()
                                        ->send();
                                }
                            }
                        }),

                    Toggle::make('locked')
                        ->label(__('core::sync.locked'))
                        ->rules(['boolean'])
                        ->nullable()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ])
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (! $state) {
                                $set('lock_reason', null);
                            }
                        }),

                    TextInput::make('lock_reason')
                        ->label(__('core::sync.lock_reason'))
                        ->rules(['max:255'])
                        ->nullable()
                        ->label('Reason')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ])
                        ->visible(fn ($get) => $get('locked')),

                    Toggle::make('show_in_menu')
                        ->label(__('core::core.show_in_menu'))
                        ->rules(['boolean'])
                        ->nullable()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ])
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (! $state) {
                                $set('order', null);
                            }
                        }),

                    TextInput::make('order')
                        ->label(__('core::core.order'))
                        ->rules(['max:255'])
                        ->nullable()
                        ->unique(ignoreRecord: true)
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ])
                        ->visible(fn ($get) => $get('show_in_menu')),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label(__('core::core.thumbnail'))
                    ->toggleable()
                    ->label('')
                    ->square(),
                TextColumn::make('name')
                    ->label(__('core::core.name'))
                    ->toggleable()
                    ->sortable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('domain')
                    ->label(__('core::core.domain'))
                    ->toggleable()
                    ->sortable()
                    ->searchable()
                    ->limit(50),
                IconColumn::make('master')
                    ->label(__('core::core.master'))
                    ->toggleable()
                    ->sortable()
                    ->boolean(),
                IconColumn::make('locked')
                    ->label(__('core::sync.locked'))
                    ->toggleable()
                    ->sortable()
                    ->boolean(),
                IconColumn::make('api_token')
                    ->label(__('core::sync.api_token'))
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('show_in_menu')
                    ->label(__('core::core.show_in_menu'))
                    ->toggleable()
                    ->sortable()
                    ->boolean(),
                TextColumn::make('order')
                    ->label(__('core::core.order'))
                    ->toggleable()
                    ->sortable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('created_at')
                    ->label(__('core::core.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since(),
            ])
            ->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            // TODO: debug - SQLSTATE[42000]: Syntax error or access violation: 1250 Table 'syncs' from one of the SELECTs cannot be used in global ORDER clause
            // PlatformResource\RelationManagers\SyncsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlatforms::route('/'),
            'create' => CreatePlatform::route('/create'),
            'view' => ViewPlatform::route('/{record}'),
            'edit' => EditPlatform::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return config('sync.resources.platform.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('sync.resources.platform.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('sync.resources.platform.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('sync.resources.platform.single');
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
        return config('sync.navigation_sort') + 2;
    }
}
