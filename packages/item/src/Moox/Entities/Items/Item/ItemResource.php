<?php

namespace Moox\Item\Moox\Entities\Items\Item;

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Entities\Items\Item\BaseItemResource;
use Moox\Item\Models\Item;
use Moox\Item\Moox\Entities\Items\Item\Pages\CreateItem;
use Moox\Item\Moox\Entities\Items\Item\Pages\EditItem;
use Moox\Item\Moox\Entities\Items\Item\Pages\ListItems;
use Moox\Item\Moox\Entities\Items\Item\Pages\ViewItem;

class ItemResource extends BaseItemResource
{
    protected static ?string $model = Item::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-local-offer';

    public static function getModelLabel(): string
    {
        return config('item.resources.item.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('item.resources.item.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('item.resources.item.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('item.resources.item.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('item.navigation_group');
    }

    public static function form(Schema $form): Schema
    {
        return $form->components([
            Grid::make()
                ->schema([
                    Section::make()
                        ->schema([
                            TextInput::make('title')
                                ->label(__('core::core.title')),
                            MarkdownEditor::make('description')
                                ->label(__('core::core.description')),
                            Grid::make(2)
                                ->schema([
                                    static::getFooterActions()->columnSpan(1),
                                ]),
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
                                    ...static::getStandardTimestampFields(),
                                ])->hidden(fn ($record) => $record === null),
                        ])
                        ->columnSpan(1)
                        ->columns(1),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('core::core.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label(__('core::core.description'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('custom_properties')
                    ->limit(50),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('title', 'desc')
            ->recordActions([...static::getTableActions()])
            ->toolbarActions([...static::getBulkActions()])
            ->filters([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListItems::route('/'),
            'create' => CreateItem::route('/create'),
            'edit' => EditItem::route('/{record}/edit'),
            'view' => ViewItem::route('/{record}'),
        ];
    }
}
