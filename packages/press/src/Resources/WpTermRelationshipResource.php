<?php

namespace Moox\Press\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Tabs\TabsInResource;
use Moox\Press\Models\WpTermRelationship;
use Filament\Tables\Actions\DeleteBulkAction;
use Moox\Press\Resources\WpTermRelationshipResource\Pages;

class WpTermRelationshipResource extends Resource
{
    use TabsInResource, BaseInResource;

    protected static ?string $model = WpTermRelationship::class;

    protected static ?string $navigationIcon = 'gmdi-category-o';

    protected static ?string $recordTitleAttribute = 'object_id';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    TextInput::make('term_taxonomy_id')
                        ->label(__('core::core.term_taxonomy_id'))
                        ->rules(['max:255'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('term_order')
                        ->label(__('core::core.term_order'))
                        ->rules(['numeric'])
                        ->required()
                        ->numeric()
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
                Tables\Columns\TextColumn::make('term_taxonomy_id')
                    ->label(__('core::core.term_taxonomy_id'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('term_order')
                    ->label(__('core::core.term_order'))
                    ->toggleable()
                    ->searchable(true, null, true),
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
            'index' => Pages\ListWpTermRelationships::route('/'),
            'create' => Pages\CreateWpTermRelationship::route('/create'),
            'view' => Pages\ViewWpTermRelationship::route('/{record}'),
            'edit' => Pages\EditWpTermRelationship::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return config('press.resources.termRelationships.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('press.resources.termRelationships.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('press.resources.termRelationships.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('press.resources.termRelationships.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('press.meta_navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('press.meta_navigation_sort') + 4;
    }
}
