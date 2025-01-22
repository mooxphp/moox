<?php

namespace Moox\Press\Resources;

use Override;
use Filament\Tables\Columns\TextColumn;
use Moox\Press\Resources\WpTermResource\Pages\ListWpTerms;
use Moox\Press\Resources\WpTermResource\Pages\CreateWpTerm;
use Moox\Press\Resources\WpTermResource\Pages\ViewWpTerm;
use Moox\Press\Resources\WpTermResource\Pages\EditWpTerm;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Tabs\TabsInResource;
use Moox\Press\Models\WpTerm;
use Moox\Press\Resources\WpTermResource\Pages;

class WpTermResource extends Resource
{
    use BaseInResource;
    use TabsInResource;
    protected static ?string $model = WpTerm::class;

    protected static ?string $navigationIcon = 'gmdi-category-o';

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('termTaxonomy');
    }

    #[Override]
    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    TextInput::make('name')
                        ->label(__('core::core.name'))
                        ->rules(['max:200', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('slug')
                        ->label(__('core::core.slug'))
                        ->rules(['max:200', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    Textarea::make('description')
                        ->label(__('core::core.description'))
                        ->rules(['string'])
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    Select::make('parent')
                        ->label(__('core::core.parent'))
                        ->options(fn () => WpTerm::pluck('name', 'term_id'))
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('term_group')
                        ->label(__('core::core.term_group'))
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
                        ->rules(['max:20'])
                        ->required()
                        ->readonly()
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
                TextColumn::make('name')
                    ->label(__('core::core.name'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('slug')
                    ->label(__('core::core.slug'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('description')
                    ->label(__('core::core.description'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('parent')
                    ->label(__('core::core.parent'))
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('count')
                    ->label(__('core::core.count'))
                    ->toggleable()
                    ->searchable(),
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
            'index' => ListWpTerms::route('/'),
            'create' => CreateWpTerm::route('/create'),
            'view' => ViewWpTerm::route('/{record}'),
            'edit' => EditWpTerm::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('press.resources.term.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('press.resources.term.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('press.resources.term.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('press.resources.term.single');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('press.meta_navigation_group');
    }

    #[Override]
    public static function getNavigationSort(): ?int
    {
        return config('press.meta_navigation_sort') + 5;
    }
}
