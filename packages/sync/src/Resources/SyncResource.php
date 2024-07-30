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
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Moox\Sync\Models\Sync;
use Moox\Sync\Resources\SyncResource\Pages\CreateSync;
use Moox\Sync\Resources\SyncResource\Pages\EditSync;
use Moox\Sync\Resources\SyncResource\Pages\ListSyncs;
use Moox\Sync\Resources\SyncResource\Pages\ViewSync;

class SyncResource extends Resource
{
    protected static ?string $model = Sync::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $recordTitleAttribute = 'syncable_type';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    TextInput::make('syncable_id')
                        ->rules(['max:255'])
                        ->required()
                        ->placeholder('Syncable Id')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('syncable_type')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Syncable Type')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    Select::make('source_platform_id')
                        ->rules(['exists:platforms,id'])
                        ->required()
                        ->relationship('sourcePlatform', 'name')
                        ->placeholder('Source Platform')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    Select::make('target_platform_id')
                        ->rules(['exists:platforms,id'])
                        ->required()
                        ->relationship('targetPlatform', 'name')
                        ->placeholder('Target Platform')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    DatePicker::make('last_sync')
                        ->rules(['date'])
                        ->placeholder('Last Sync')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    Toggle::make('has_errors')
                        ->rules(['boolean'])
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    KeyValue::make('field_mappings')
                        ->rules(['array'])
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
                TextColumn::make('syncable_id')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('syncable_type')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('sourcePlatform.name')
                    ->toggleable()
                    ->limit(50),
                TextColumn::make('targetPlatform.name')
                    ->toggleable()
                    ->limit(50),
                TextColumn::make('last_sync')
                    ->toggleable()
                    ->date(),
                IconColumn::make('has_errors')
                    ->toggleable()
                    ->boolean(),
                IconColumn::make('field_mappings')
                    ->label('Mappings')
                    ->toggleable()
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->getStateUsing(fn ($record) => ! empty($record->field_mappings)),
            ])
            ->filters([
                SelectFilter::make('source_platform_id')
                    ->relationship('sourcePlatform', 'name')
                    ->indicator('Platform')
                    ->multiple()
                    ->label('Platform'),

                SelectFilter::make('target_platform_id')
                    ->relationship('targetPlatform', 'name')
                    ->indicator('Platform')
                    ->multiple()
                    ->label('Platform'),
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
        return __('sync::translations.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('sync::translations.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('sync::translations.navigation_label');
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
        return config('sync.sync.navigation_sort');
    }
}
