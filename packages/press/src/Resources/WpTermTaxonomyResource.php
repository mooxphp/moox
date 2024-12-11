<?php

namespace Moox\Press\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Moox\Press\Models\WpTermTaxonomy;
use Filament\Forms\Components\Section;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Tabs\TabsInResource;
use Filament\Tables\Actions\DeleteBulkAction;
use Moox\Press\Resources\WpTermTaxonomyResource\Pages;

class WpTermTaxonomyResource extends Resource
{
    use TabsInResource, BaseInResource;

    protected static ?string $model = WpTermTaxonomy::class;

    protected static ?string $navigationIcon = 'gmdi-category-o';

    protected static ?string $recordTitleAttribute = 'taxonomy';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    TextInput::make('term_id')
                        ->label(__('core::core.term_id'))
                        ->rules(['max:255'])
                        ->required()
                        ->default('0')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('taxonomy')
                        ->rules(['max:32', 'string'])
                        ->label(__('core::core.taxonomy'))
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('description')
                        ->label(__('core::core.description'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('parent')
                        ->label(__('core::core.parent'))
                        ->rules(['max:255'])
                        ->required()
                        ->default('0')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('count')
                        ->label(__('core::core.count'))
                        ->rules(['max:255'])
                        ->required()
                        ->default('0')
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
                Tables\Columns\TextColumn::make('term_id')
                    ->label(__('core::core.term_id'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('taxonomy')
                    ->label(__('core::core.taxonomy'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('core::core.description'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('parent')
                    ->label(__('core::core.parent'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('count')
                    ->label(__('core::core.count'))
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
            'index' => Pages\ListWpTermTaxonomies::route('/'),
            'create' => Pages\CreateWpTermTaxonomy::route('/create'),
            'view' => Pages\ViewWpTermTaxonomy::route('/{record}'),
            'edit' => Pages\EditWpTermTaxonomy::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return config('press.resources.termTaxonomy.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('press.resources.termTaxonomy.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('press.resources.termTaxonomy.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('press.resources.termTaxonomy.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('press.meta_navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('press.meta_navigation_sort') + 6;
    }
}
