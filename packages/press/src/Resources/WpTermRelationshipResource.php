<?php

namespace Moox\Press\Resources;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Press\Models\WpTermRelationship;
use Moox\Press\Resources\WpTermRelationshipResource\Pages\CreateWpTermRelationship;
use Moox\Press\Resources\WpTermRelationshipResource\Pages\EditWpTermRelationship;
use Moox\Press\Resources\WpTermRelationshipResource\Pages\ListWpTermRelationships;
use Moox\Press\Resources\WpTermRelationshipResource\Pages\ViewWpTermRelationship;
use Override;

class WpTermRelationshipResource extends Resource
{
    use BaseInResource;
    use HasResourceTabs;

    protected static ?string $model = WpTermRelationship::class;

    protected static ?string $navigationIcon = 'gmdi-category-o';

    protected static ?string $recordTitleAttribute = 'object_id';

    #[Override]
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

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('term_taxonomy_id')
                    ->label(__('core::core.term_taxonomy_id'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('term_order')
                    ->label(__('core::core.term_order'))
                    ->toggleable()
                    ->searchable(true, null, true),
            ])
            ->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListWpTermRelationships::route('/'),
            'create' => CreateWpTermRelationship::route('/create'),
            'view' => ViewWpTermRelationship::route('/{record}'),
            'edit' => EditWpTermRelationship::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('press.resources.termRelationships.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('press.resources.termRelationships.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('press.resources.termRelationships.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('press.resources.termRelationships.single');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('press.meta_navigation_group');
    }

    #[Override]
    public static function getNavigationSort(): ?int
    {
        return config('press.meta_navigation_sort') + 4;
    }
}
