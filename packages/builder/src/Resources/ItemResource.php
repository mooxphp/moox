<?php

namespace Moox\Builder\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Moox\Builder\Models\Item;
use Moox\Builder\Resources\ItemResource\Pages\CreatePage;
use Moox\Builder\Resources\ItemResource\Pages\EditPage;
use Moox\Builder\Resources\ItemResource\Pages\ListPage;
use Moox\Builder\Resources\ItemResource\Pages\ViewPage;
use Moox\Builder\Resources\ItemResource\Widgets\ItemWidgets;
use Moox\Core\Forms\Components\TitleWithSlugInput;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    protected static ?string $navigationIcon = 'gmdi-engineering';

    protected static ?string $authorModel = null;

    public static function form(Form $form): Form
    {
        static::initAuthorModel();

        return $form->schema([
            Grid::make(2)
                ->schema([
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    ...TitleWithSlugInput::make('title')
                                        ->titleLabel(__('core::core.title'))
                                        ->slugLabel(__('core::core.slug'))
                                        ->showSlugInput(fn ($record) => ! $record ||
                                            (config('builder.allow_slug_change_after_saved') || ! $record->exists) &&
                                            (config('builder.allow_slug_change_after_publish') || ! $record->published_at)
                                        )
                                        ->slugPrefix(url('/').'/'.config('builder.url_slug', 'items/'))
                                        ->components(),
                                    FileUpload::make('featured_image_url')
                                        ->label(__('builder::translations.featured_image_url')),
                                    Textarea::make('content')
                                        ->label(__('core::core.content'))
                                        ->rows(10),
                                    FileUpload::make('gallery_image_urls')
                                        ->multiple()
                                        ->label(__('builder::translations.gallery_image_urls')),
                                ]),
                        ])
                        ->columnSpan(['lg' => 2]),

                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    Select::make('type')
                                        ->options(static::getModel()::getTypeOptions())
                                        ->default('post')
                                        ->required(),
                                    DateTimePicker::make('publish_at')
                                        ->label(__('core::core.publish_at')),
                                    Select::make('author_id')
                                        ->label(__('core::core.author'))
                                        ->options(fn () => static::getAuthorOptions())
                                        ->required()
                                        ->searchable()
                                        ->visible(fn () => static::shouldShowAuthorField()),
                                ]),
                        ])
                        ->columnSpan(['lg' => 1]),
                ])
                ->columns(['lg' => 3]),
        ]);
    }

    public static function table(Table $table): Table
    {
        static::initAuthorModel();

        return $table
            ->columns([
                ImageColumn::make('featured_image_url')
                    ->label(__('core::core.image'))
                    ->alignment('center')
                    ->square()
                    ->toggleable(),
                TextColumn::make('title')
                    ->label(__('core::core.title'))
                    ->searchable()
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
                    ->searchable()
                    ->toggleable(),
                ImageColumn::make('author.avatar_url')
                    ->label(__('core::core.author'))
                    ->tooltip(fn ($record) => $record->author?->name)
                    ->alignment('center')
                    ->circular()
                    ->visible(fn () => static::shouldShowAuthorField())
                    ->toggleable(),
                TextColumn::make('type')
                    ->label(__('core::core.type'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('core::core.status'))
                    ->alignment('center')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => strtoupper($state))
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'primary',
                        'published' => 'success',
                        'scheduled' => 'warning',
                        default => 'secondary',
                    })
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('publish_at')
                    ->label(__('core::core.publish_at'))
                    ->dateTime('Y-m-d H:i:s')
                    ->toggleable()
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('slug', 'desc')
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(static::getModel()::getTypeOptions())
                    ->label(__('core::core.type')),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPage::route('/'),
            'edit' => EditPage::route('/{record}/edit'),
            'create' => CreatePage::route('/create'),
            'view' => ViewPage::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            ItemWidgets::class,
        ];
    }

    public static function getModelLabel(): string
    {
        return config('builder.resources.builder.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('builder.resources.builder.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('builder.resources.builder.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('builder.resources.builder.single');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationBadge(): ?string
    {
        return number_format(static::getModel()::count());
    }

    public static function getNavigationGroup(): ?string
    {
        return config('builder.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('builder.navigation_sort') + 3;
    }

    protected static function initAuthorModel(): void
    {
        if (static::$authorModel === null) {
            static::$authorModel = config('builder.author_model');
        }
    }

    protected static function getAuthorOptions(): array
    {
        return static::$authorModel::query()->get()->pluck('name', 'id')->toArray();
    }

    protected static function shouldShowAuthorField(): bool
    {
        return static::$authorModel && class_exists(static::$authorModel);
    }
}
