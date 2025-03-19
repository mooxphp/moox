<?php

declare(strict_types=1);

namespace App\Builder\Resources;

use App\Builder\Models\SimpleTaxonomy;
use App\Builder\Resources\SimpleTaxonomyResource\Pages\CreateSimpleTaxonomy;
use App\Builder\Resources\SimpleTaxonomyResource\Pages\EditSimpleTaxonomy;
use App\Builder\Resources\SimpleTaxonomyResource\Pages\ListSimpleTaxonomies;
use App\Builder\Resources\SimpleTaxonomyResource\Pages\ViewSimpleTaxonomy;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class SimpleTaxonomyResource extends Resource
{
    protected static ?string $model = SimpleTaxonomy::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    #[Override]
    public static function getModelLabel(): string
    {
        return config('previews.simple-taxonomy.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('previews.simple-taxonomy.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('previews.simple-taxonomy.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('previews.simple-taxonomy.single');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('previews.navigation_group');
    }

    #[Override]
    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(2)
                ->schema([
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextInput::make('title')
                                        ->label('Title')
                                        ->maxLength(255)->required(),
                                    TextInput::make('slug')
                                        ->label('Slug')
                                        ->maxLength(255)->required(),
                                    Textarea::make('description')
                                        ->label('Description'),
                                ]),
                        ])
                        ->columnSpan(['lg' => 2]),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                ]),
                        ])
                        ->columnSpan(['lg' => 1]),
                ])
                ->columns(['lg' => 3]),
        ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->sortable()->searchable(),
                TextColumn::make('slug'),
                TextColumn::make('description')
                    ->limit(50),
            ])
            ->defaultSort('title', 'desc')
            ->actions([])
            ->bulkActions([])
            ->filters([
                Filter::make('title')
                    ->form([
                        TextInput::make('title')
                            ->label('Title')
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['title'],
                        fn (Builder $query, $value): Builder => $query->where('title', 'like', sprintf('%%%s%%', $value)),
                    ))
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['title']) {
                            return null;
                        }

                        return 'Title: '.$data['title'];
                    }),
                Filter::make('slug')
                    ->form([
                        TextInput::make('slug')
                            ->label('Slug')
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['slug'],
                        fn (Builder $query, $value): Builder => $query->where('slug', 'like', sprintf('%%%s%%', $value)),
                    ))
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['slug']) {
                            return null;
                        }

                        return 'Slug: '.$data['slug'];
                    }),
            ]);
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListSimpleTaxonomies::route('/'),
            'create' => CreateSimpleTaxonomy::route('/create'),
            'edit' => EditSimpleTaxonomy::route('/{record}/edit'),
            'view' => ViewSimpleTaxonomy::route('/{record}'),
        ];
    }
}
