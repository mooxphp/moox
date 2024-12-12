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
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Builder\Models\FullItem;
use Moox\Builder\Resources\FullItemResource\Pages\CreateFullItem;
use Moox\Builder\Resources\FullItemResource\Pages\EditFullItem;
use Moox\Builder\Resources\FullItemResource\Pages\ListFullItems;
use Moox\Builder\Resources\FullItemResource\Pages\ViewFullItem;
use Moox\Builder\Resources\FullItemResource\Widgets\FullItemWidgets;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Publish\SinglePublishInResource;
use Moox\Core\Traits\Tabs\TabsInResource;
use Moox\Core\Traits\Taxonomy\TaxonomyInResource;
use Moox\Core\Traits\UserRelation\UserInResource;

class FullItemResource extends Resource
{
    use BaseInResource;
    use SinglePublishInResource;
    use TabsInResource;
    use TaxonomyInResource;
    use UserInResource;

    protected static ?string $model = FullItem::class;

    protected static ?string $navigationIcon = 'gmdi-engineering';

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
                                    Actions::make([
                                        Actions\Action::make('restore')
                                            ->label(__('core::core.restore'))
                                            ->color('success')
                                            ->button()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(fn ($record) => $record->restore())
                                            ->visible(fn ($livewire, $record) => $record && $record->trashed() && $livewire instanceof ViewFullItem),
                                        Actions\Action::make('save')
                                            ->label(__('core::core.save'))
                                            ->color('primary')
                                            ->button()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(function ($livewire) {
                                                $livewire instanceof CreateFullItem ? $livewire->create() : $livewire->save();
                                            })
                                            ->visible(fn ($livewire) => $livewire instanceof CreateFullItem || $livewire instanceof EditFullItem),
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
                                                $livewire instanceof CreateFullItem ? $livewire->create() : $livewire->save();
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
                                            ->visible(fn ($livewire) => $livewire instanceof CreateFullItem),
                                        Actions\Action::make('cancel')
                                            ->label(__('core::core.cancel'))
                                            ->color('secondary')
                                            ->outlined()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->url(fn () => static::getUrl('index'))
                                            ->visible(fn ($livewire) => $livewire instanceof CreateFullItem),
                                        Actions\Action::make('edit')
                                            ->label(__('core::core.edit'))
                                            ->color('primary')
                                            ->button()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->url(fn ($record) => static::getUrl('edit', ['record' => $record]))
                                            ->visible(fn ($livewire, $record) => $livewire instanceof ViewFullItem && ! $record->trashed()),
                                        Actions\Action::make('restore')
                                            ->label(__('core::core.restore'))
                                            ->color('success')
                                            ->button()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(fn ($record) => $record->restore())
                                            ->visible(fn ($livewire, $record) => $record && $record->trashed() && $livewire instanceof EditFullItem),
                                        Actions\Action::make('delete')
                                            ->label(__('core::core.delete'))
                                            ->color('danger')
                                            ->link()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(fn ($record) => $record->delete())
                                            ->visible(fn ($livewire, $record) => $record && ! $record->trashed() && $livewire instanceof EditFullItem),
                                    ]),
                                    Select::make('type')
                                        ->options(static::getModel()::getTypeOptions())
                                        ->default('post')
                                        ->visible(! empty(config('builder.types')))
                                        ->required(),
                                    DateTimePicker::make('publish_at')
                                        ->label(__('core::core.publish_at')),
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
                TextColumn::make('type')
                    ->label(__('core::core.type'))
                    ->visible(! empty(config('builder.types')))
                    ->formatStateUsing(fn ($record): string => config('builder.types')[$record->type] ?? ucfirst($record->type))
                    ->sortable(),
                ...static::getTaxonomyColumns(),
                static::getStatusTableColumn(),
                TextColumn::make('publish_at')
                    ->label(__('core::core.publish_at'))
                    ->dateTime('Y-m-d H:i:s')
                    ->toggleable()
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('slug', 'desc')
            ->actions([
                ...static::getTableActions(),
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
                Filter::make('title')
                    ->form([
                        TextInput::make('title')
                            ->label(__('core::core.title')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['title'],
                            fn (Builder $query, $title): Builder => $query->where('title', 'like', "%{$title}%"),
                        );
                    }),
                Filter::make('slug')
                    ->form([
                        TextInput::make('slug')
                            ->label(__('core::core.slug')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['slug'],
                            fn (Builder $query, $slug): Builder => $query->where('slug', 'like', "%{$slug}%"),
                        );
                    }),
                SelectFilter::make('type')
                    ->options(static::getModel()::getTypeOptions())
                    ->label(__('core::core.type')),
                ...static::getTableFilters(),
                ...static::getTaxonomyFilters(),
                ...static::getUserFilters(),
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
            'index' => ListFullItems::route('/'),
            'edit' => EditFullItem::route('/{record}/edit'),
            'create' => CreateFullItem::route('/create'),
            'view' => ViewFullItem::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            FullItemWidgets::class,
        ];
    }

    public static function getModelLabel(): string
    {
        return config('builder.resources.full-item.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('builder.resources.full-item.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('builder.resources.full-item.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('builder.resources.full-item.single');
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
        return config('builder.navigation_sort') + 2;
    }

    public static function getTableQuery(?string $currentTab = null): Builder
    {
        $model = static::getModel();

        $query = $model::query()->withoutGlobalScopes();

        if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
            $query = $model::withTrashed();
        }

        if ($currentTab) {
            static::applyStatusFilter($query, $currentTab);
        }

        return $query;
    }
}
