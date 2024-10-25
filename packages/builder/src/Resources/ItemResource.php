<?php

declare(strict_types=1);

namespace Moox\Builder\Resources;

use Camya\Filament\Forms\Components\TitleWithSlugInput;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Moox\Builder\Models\Item;
use Moox\Builder\Resources\ItemResource\Pages\CreateItem;
use Moox\Builder\Resources\ItemResource\Pages\EditItem;
use Moox\Builder\Resources\ItemResource\Pages\ListItems;
use Moox\Builder\Resources\ItemResource\Pages\ViewItem;
use Moox\Builder\Resources\ItemResource\Widgets\ItemWidgets;
use Moox\Core\Traits\AuthorInResource;
use Moox\Core\Traits\TabsInResource;
use Moox\Core\Traits\TaxonomyInResource;

class ItemResource extends Resource
{
    use AuthorInResource, TabsInResource, TaxonomyInResource;

    protected static ?string $model = Item::class;

    protected static ?string $navigationIcon = 'gmdi-article';

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
                                    Actions::make([
                                        Actions\Action::make('restore')
                                            ->label(__('core::core.restore'))
                                            ->color('success')
                                            ->button()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(fn ($record) => $record->restore())
                                            ->visible(fn ($livewire, $record) => $record && $record->trashed() && $livewire instanceof ViewItem),
                                        Actions\Action::make('save')
                                            ->label(__('core::core.save'))
                                            ->color('primary')
                                            ->button()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(function ($livewire) {
                                                $livewire instanceof CreateItem ? $livewire->create() : $livewire->save();
                                            })
                                            ->visible(fn ($livewire) => $livewire instanceof CreateItem || $livewire instanceof EditItem),
                                        Actions\Action::make('publish')
                                            ->label(__('core::core.publish'))
                                            ->color('success')
                                            ->button()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(function ($livewire) {
                                                $data = $livewire->form->getState();
                                                if (! $data['published_at']) {
                                                    $data['published_at'] = now();
                                                }
                                                $livewire->form->fill($data);
                                                $livewire instanceof CreateItem ? $livewire->create() : $livewire->save();
                                            })
                                            ->hidden(fn ($livewire, $record) => $record && $record->trashed()),
                                        Actions\Action::make('saveAndCreateAnother')
                                            ->label(__('core::core.save_and_create_another'))
                                            ->color('secondary')
                                            ->button()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(function ($livewire) {
                                                $livewire->saveAndCreateAnother();
                                            })
                                            ->visible(fn ($livewire) => $livewire instanceof CreateItem),
                                        Actions\Action::make('cancel')
                                            ->label(__('core::core.cancel'))
                                            ->color('secondary')
                                            ->outlined()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->url(fn () => static::getUrl('index'))
                                            ->visible(fn ($livewire) => $livewire instanceof CreateItem),
                                        Actions\Action::make('edit')
                                            ->label(__('core::core.edit'))
                                            ->color('primary')
                                            ->button()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->url(fn ($record) => static::getUrl('edit', ['record' => $record]))
                                            ->visible(fn ($livewire, $record) => $livewire instanceof ViewItem && ! $record->trashed()),
                                        Actions\Action::make('restore')
                                            ->label(__('core::core.restore'))
                                            ->color('success')
                                            ->button()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(fn ($record) => $record->restore())
                                            ->visible(fn ($livewire, $record) => $record && $record->trashed() && $livewire instanceof EditItem),
                                        Actions\Action::make('delete')
                                            ->label(__('core::core.delete'))
                                            ->color('danger')
                                            ->link()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(fn ($record) => $record->delete())
                                            ->visible(fn ($livewire, $record) => $record && ! $record->trashed() && $livewire instanceof EditItem),
                                    ]),
                                    Select::make('type')
                                        ->options(static::getModel()::getTypeOptions())
                                        ->default('post')
                                        ->visible(! empty(config('builder.types')))
                                        ->required(),
                                    DateTimePicker::make('publish_at')
                                        ->label(__('core::core.publish_at')),

                                    static::getAuthorFormField(),
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
        static::initAuthorModel();

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
                static::getAuthorTableColumn(),
                TextColumn::make('type')
                    ->label(__('core::core.type'))
                    ->visible(! empty(config('builder.types')))
                    ->formatStateUsing(fn ($record): string => config('builder.types')[$record->type] ?? ucfirst($record->type))
                    ->sortable(),

                ...static::getTaxonomyColumns(),

                TextColumn::make('status')
                    ->label(__('core::core.status'))
                    ->alignment('center')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => strtoupper($state))
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'primary',
                        'published' => 'success',
                        'scheduled' => 'info',
                        'deleted' => 'danger',
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
                EditAction::make()->hidden(fn () => in_array(static::getCurrentTab(), ['trash', 'deleted'])),
            ])
            ->bulkActions([
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
                SelectFilter::make('type')
                    ->options(static::getModel()::getTypeOptions())
                    ->label(__('core::core.type')),

                ...static::getAuthorFilters(),

                ...static::getTaxonomyFilters(),
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
            'index' => ListItems::route('/'),
            'edit' => EditItem::route('/{record}/edit'),
            'create' => CreateItem::route('/create'),
            'view' => ViewItem::route('/{record}'),
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
        return config('builder.navigation_sort') + 1;
    }
}
