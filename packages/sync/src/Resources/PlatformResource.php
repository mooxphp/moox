<?php

namespace Moox\Sync\Resources;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Pages\ViewRecord;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Sync\Models\Platform;
use Moox\Sync\Resources\PlatformResource\Pages\CreatePlatform;
use Moox\Sync\Resources\PlatformResource\Pages\EditPlatform;
use Moox\Sync\Resources\PlatformResource\Pages\ListPlatforms;
use Moox\Sync\Resources\PlatformResource\Pages\ViewPlatform;

class PlatformResource extends Resource
{
    protected static ?string $model = Platform::class;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    TextInput::make('name')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->unique()
                        ->placeholder('Name')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('domain')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Domain')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    Toggle::make('show_in_menu')
                        ->rules(['boolean'])
                        ->nullable()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('order')
                        ->rules(['max:255'])
                        ->nullable()
                        ->placeholder('Order')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    Toggle::make('read_only')
                        ->rules(['boolean'])
                        ->nullable()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    Toggle::make('locked')
                        ->rules(['boolean'])
                        ->nullable()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    Toggle::make('master')
                        ->rules(['boolean'])
                        ->nullable()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    FileUpload::make('thumbnail')
                        ->rules(['file'])
                        ->nullable()
                        ->image()
                        ->placeholder('Thumbnail')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('api_token')
                        ->rules(['max:80'])
                        ->unique()
                        ->nullable()
                        ->placeholder('Api Token')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ])
                        ->suffixAction(
                            Action::make('generateToken')
                                ->label('Generate Token')
                                ->icon('heroicon-o-arrow-path')
                                ->action('generateToken')
                                ->hidden(fn ($livewire) => $livewire instanceof ViewRecord)
                        ),

                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('name')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('domain')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                IconColumn::make('show_in_menu')
                    ->toggleable()
                    ->boolean(),
                TextColumn::make('order')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                IconColumn::make('read_only')
                    ->toggleable()
                    ->boolean(),
                IconColumn::make('locked')
                    ->toggleable()
                    ->boolean(),
                IconColumn::make('master')
                    ->toggleable()
                    ->boolean(),
                ImageColumn::make('thumbnail')
                    ->toggleable()
                    ->circular(),
                TextColumn::make('api_token')
                    ->toggleable()
                    ->searchable()
                    ->limit(30),
                TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            // Todo: debug - SQLSTATE[42000]: Syntax error or access violation: 1250 Table 'syncs' from one of the SELECTs cannot be used in global ORDER clause
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
        return __('sync::translations.platform');
    }

    public static function getPluralModelLabel(): string
    {
        return __('sync::translations.platforms');
    }

    public static function getNavigationLabel(): string
    {
        return __('sync::translations.platforms');
    }

    public static function getBreadcrumb(): string
    {
        return __('sync::translations.breadcrumb');
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
        return __('sync::translations.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('sync.plattforms.navigation_sort');
    }
}
