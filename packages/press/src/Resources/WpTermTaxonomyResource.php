<?php

namespace Moox\Press\Resources;

use Override;
use Filament\Tables\Columns\TextColumn;
use Moox\Press\Resources\WpTermTaxonomyResource\Pages\ListWpTermTaxonomies;
use Moox\Press\Resources\WpTermTaxonomyResource\Pages\CreateWpTermTaxonomy;
use Moox\Press\Resources\WpTermTaxonomyResource\Pages\ViewWpTermTaxonomy;
use Moox\Press\Resources\WpTermTaxonomyResource\Pages\EditWpTermTaxonomy;
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
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Tabs\TabsInResource;
use Moox\Press\Models\WpTermTaxonomy;
use Moox\Press\Resources\WpTermTaxonomyResource\Pages;

class WpTermTaxonomyResource extends Resource
{
    use BaseInResource;
    use TabsInResource;
    protected static ?string $model = WpTermTaxonomy::class;

    protected static ?string $navigationIcon = 'gmdi-category-o';

    protected static ?string $recordTitleAttribute = 'taxonomy';

    #[Override]
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

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('term_id')
                    ->label(__('core::core.term_id'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('taxonomy')
                    ->label(__('core::core.taxonomy'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('description')
                    ->label(__('core::core.description'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('parent')
                    ->label(__('core::core.parent'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('count')
                    ->label(__('core::core.count'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
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
            'index' => ListWpTermTaxonomies::route('/'),
            'create' => CreateWpTermTaxonomy::route('/create'),
            'view' => ViewWpTermTaxonomy::route('/{record}'),
            'edit' => EditWpTermTaxonomy::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('press.resources.termTaxonomy.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('press.resources.termTaxonomy.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('press.resources.termTaxonomy.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('press.resources.termTaxonomy.single');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('press.meta_navigation_group');
    }

    #[Override]
    public static function getNavigationSort(): ?int
    {
        return config('press.meta_navigation_sort') + 6;
    }
}
