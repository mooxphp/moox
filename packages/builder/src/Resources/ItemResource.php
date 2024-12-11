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
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Builder\Models\Item;
use Moox\Builder\Resources\ItemResource\Pages\CreateItem;
use Moox\Builder\Resources\ItemResource\Pages\EditItem;
use Moox\Builder\Resources\ItemResource\Pages\ListItems;
use Moox\Builder\Resources\ItemResource\Pages\ViewItem;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Publish\SinglePublishInResource;
use Moox\Core\Traits\Tabs\TabsInResource;
use Moox\Core\Traits\Taxonomy\TaxonomyInResource;
use Moox\Core\Traits\UserRelation\UserInResource;

class ItemResource extends Resource
{
    use BaseInResource, SinglePublishInResource, TabsInResource, TaxonomyInResource, UserInResource;

    protected static ?string $model = Item::class;

    protected static ?string $navigationIcon = 'gmdi-article';

    public static function form(Form $form): Form
    {
        static::initUserModel();

        return $form->schema([
            Grid::make(2)
                ->schema([
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
                                    FileUpload::make('gallery_image_urls')
                                        ->multiple()
                                        ->label(__('core::core.gallery_image_urls')),
                                ]),
                        ])
                        ->columnSpan(['lg' => 2]),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    static::getFormActions(),
                                    static::getPublishAtFormField(),
                                    static::getUserFormField(),
                                ]),

                            Section::make()
                                ->schema(static::getTaxonomyFields())
                                ->columns(1)
                                ->visible(fn () => ! empty(static::getTaxonomyFields())),
                        ])
                        ->columnSpan(['lg' => 1]),
                ])
                ->columns(['lg' => 3]),
        ]);
    }

    public static function table(Table $table): Table
    {
        static::initUserModel();

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
                static::getUserTableColumn(),
                ...static::getTaxonomyColumns(),
                static::getStatusTableColumn(),

                // SinglePublishInResource - getPublishColumn
                TextColumn::make('publish_at')
                    ->label(__('core::core.publish_at'))
                    ->dateTime('Y-m-d H:i:s')
                    ->toggleable()
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('slug', 'desc')
            ->actions([
                // SinglePublishInResource - getTableActions
                ViewAction::make(),
                EditAction::make()->hidden(fn () => in_array(static::getCurrentTab(), ['trash', 'deleted'])),
            ])
            ->bulkActions([
                // SinglePublishInResource - getTableBulkActions
                DeleteBulkAction::make()->hidden(function () use ($currentTab) {
                    $isHidden = in_array($currentTab, ['trash', 'deleted']);

                    return $isHidden;
                }),
                RestoreBulkAction::make()->visible(function () use ($currentTab) {
                    $isVisible = in_array($currentTab, ['trash', 'deleted']);

                    return $isVisible;
                }),
            ])
            ->filters([
                ...static::getTableFilters(),
                ...static::getUserFilters(),
                ...static::getTaxonomyFilters(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListItems::route('/'),
            'edit' => EditItem::route('/{record}/edit'),
            'create' => CreateItem::route('/create'),
            'view' => ViewItem::route('/{record}'),
        ];
    }

    public static function getModelLabel(): string
    {
        return config('builder.resources.item.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('builder.resources.item.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('builder.resources.item.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('builder.resources.item.single');
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
