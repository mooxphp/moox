<?php

declare(strict_types=1);

namespace Moox\Builder\Resources;

use Camya\Filament\Forms\Components\TitleWithSlugInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Builder\Models\SimpleItem;
use Moox\Builder\Resources\SimpleItemResource\Pages\CreateSimpleItem;
use Moox\Builder\Resources\SimpleItemResource\Pages\EditSimpleItem;
use Moox\Builder\Resources\SimpleItemResource\Pages\ListSimpleItems;
use Moox\Builder\Resources\SimpleItemResource\Pages\ViewSimpleItem;
use Moox\Core\Traits\Tabs\TabsInResource;

class SimpleItemResource extends Resource
{
    use TabsInResource;

    protected static ?string $model = SimpleItem::class;

    protected static ?string $navigationIcon = 'gmdi-circle';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make()
                ->schema([
                    Section::make()
                        ->schema([
                            TitleWithSlugInput::make(
                                fieldTitle: 'title',
                                fieldSlug: 'slug',
                            ),
                            FileUpload::make('featured_image_url')
                                ->label(__('core::core.featured_image_url')),
                            MarkdownEditor::make('content')
                                ->label(__('core::core.content')),
                        ]),
                ])
                ->columns(['lg' => 3]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $currentTab = static::getCurrentTab();

        return $table
            ->columns([
                ImageColumn::make('featured_image_url')
                    ->label(__('core::core.image'))
                    ->defaultImageUrl(url('/moox/core/assets/noimage.svg'))
                    ->alignment('center')
                    ->square()
                    ->toggleable(),
                TextColumn::make('title')
                    ->label(__('core::core.title'))
                    ->searchable()
                    ->limit(30)
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('core::core.slug'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('content')
                    ->label(__('core::core.content'))
                    ->sortable()
                    ->limit(30)
                    ->searchable()
                    ->toggleable(),
            ])
            ->defaultSort('slug', 'desc')
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSimpleItems::route('/'),
            'create' => CreateSimpleItem::route('/create'),
            'edit' => EditSimpleItem::route('/{record}/edit'),
            'view' => ViewSimpleItem::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    public static function getModelLabel(): string
    {
        return config('builder.resources.simple-item.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('builder.resources.simple-item.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('builder.resources.simple-item.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('builder.resources.simple-item.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('builder.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('builder.navigation_sort') + 1;
    }
}
