<?php

namespace Moox\Locate\Resources;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Locate\Models\Area;
use Moox\Locate\Resources\AreaResource\Pages\ListPage;

class AreaResource extends Resource
{
    protected static ?string $model = Area::class;

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationIcon = 'gmdi-place';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')->required(),
            TextInput::make('slug')->required(),
            Select::make('area_type')
                ->options([
                    'continent' => 'Continent',
                    'sub-continent' => 'Sub-Continent',
                    'union' => 'Union',
                    'other' => 'Other',
                ])
                ->required(),
            Textarea::make('description'),
            TextInput::make('nutrition')->required(),
            Toggle::make('tropical')->inline(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->sortable(),
            TextColumn::make('title')->searchable()->sortable(),
            TextColumn::make('slug')->searchable()->sortable(),
            TextColumn::make('area_type')->sortable(),
            TextColumn::make('description')->sortable(),
            TextColumn::make('nutrition')->sortable(),
            IconColumn::make('tropical')->boolean(),
            TextColumn::make('created_at')->dateTime()->sortable(),
            TextColumn::make('updated_at')->dateTime()->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPage::route('/'),

        ];
    }

    public static function getNavigationSort(): ?int
    {
        return 8001;
    }
}
