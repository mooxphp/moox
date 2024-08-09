<?php

namespace Moox\Press\Resources;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Table;
use Moox\Press\Models\WpOption;
use Moox\Press\Resources\WpOptionResource\Pages;

class WpOptionResource extends Resource
{
    protected static ?string $model = WpOption::class;

    protected static ?string $navigationIcon = 'gmdi-settings';

    protected static ?string $recordTitleAttribute = 'option_name';

    public static function getModelLabel(): string
    {
        return 'Option';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Options';
    }

    public static function getNavigationLabel(): string
    {
        return 'Options';
    }

    protected static ?string $navigationGroup = 'Moox Press Admin';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    TextInput::make('option_name')
                        ->label(__('core::content.option_name'))
                        ->rules(['max:191', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('option_value')
                        ->label(__('core::content.option_value'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('autoload')
                        ->label(__('core::content.autoload'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->default('20')
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
                Tables\Columns\TextColumn::make('option_name')
                    ->label(__('core::content.option_name'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('option_value')
                    ->label(__('core::content.option_value'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('autoload')
                    ->label(__('core::content.autoload'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
            ])
            ->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWpOptions::route('/'),
            'create' => Pages\CreateWpOption::route('/create'),
            'view' => Pages\ViewWpOption::route('/{record}'),
            'edit' => Pages\EditWpOption::route('/{record}/edit'),
        ];
    }
}
