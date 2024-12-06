<?php

declare(strict_types=1);

namespace Moox\Builder\Resources;

/* ! Slug ! */
use Camya\Filament\Forms\Components\TitleWithSlugInput;
use Filament\Forms\Components\Actions;
/* ! DateTime ! */
use Filament\Forms\Components\DateTimePicker;
/* ! File Upload ! */
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
/* ! Markdown ! */
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Section;
/* ! Select ! */
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
/* ! Delete ! */
use Filament\Resources\Resource;
/* ! Edit ! */
use Filament\Tables\Actions\DeleteBulkAction;
/* ! Restore ! */
use Filament\Tables\Actions\EditAction;
/* ! View ! */
use Filament\Tables\Actions\RestoreBulkAction;
/* ! ImageColumn ! */
use Filament\Tables\Actions\ViewAction;
/* ! TextColumn ! */
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
/* ! FullItem ! */
use Illuminate\Database\Eloquent\SoftDeletes;
/* ! Create ! */
use Moox\Builder\Models\FullItem;
/* ! Edit ! */
use Moox\Builder\Resources\FullItemResource\Pages\CreateFullItem;
use Moox\Builder\Resources\FullItemResource\Pages\EditFullItem;
/* ! View ! */
use Moox\Builder\Resources\FullItemResource\Pages\ListFullItems;
/* ! Widgets ! */
use Moox\Builder\Resources\FullItemResource\Pages\ViewFullItem;
/* ! Author ! */
use Moox\Builder\Resources\FullItemResource\Widgets\FullItemWidgets;
/* ! Publish ! */
/* ! Tabs ! */
use Moox\Core\Traits\Publish\SinglePublishInResource;
/* ! Taxonomy ! */
use Moox\Core\Traits\Tabs\TabsInResource;
use Moox\Core\Traits\Taxonomy\TaxonomyInResource;
use Moox\Core\Traits\UserRelation\UserInResource;

/* ! FullItem => Entity */
class FullItemResource extends Resource
{
    /* ! Publish ! */
    use SinglePublishInResource;

    /* ! Tabs ! */
    use TabsInResource;

    /* ! Taxonomy ! */
    use TaxonomyInResource;

    /* ! Author ! */
    use UserInResource;

    protected static ?string $model = FullItem::class;

    protected static ?string $navigationIcon = 'gmdi-engineering';

    public static function form(Form $form): Form
    {
        /* ! Author ! */
        static::initUserModel();

        return $form->schema([
            Grid::make(2)
                ->schema([
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    /* !! Form Fields */
                                    /* ! Slug ! */
                                    TitleWithSlugInput::make(
                                        fieldTitle: 'title',
                                        fieldSlug: 'slug',
                                    ),
                                    /* ! File Upload ! */
                                    FileUpload::make('featured_image_url')
                                        ->label(__('core::core.featured_image_url')),
                                    /* ! Markdown ! */
                                    MarkdownEditor::make('content')
                                        ->label(__('core::core.content')),
                                    /* ! File Upload ! */
                                    FileUpload::make('gallery_image_urls')
                                        ->multiple()
                                        ->label(__('core::core.gallery_image_urls')),
                                    /* !! Form Fields */
                                ]),
                        ])
                        ->columnSpan(['lg' => 2]),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    Actions::make([
                                        /* !! Form Actions */
                                        /* ! Restore ! */
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
                                        /* ! Publish ! */
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
                                        /* ! Restore ! */
                                        Actions\Action::make('restore')
                                            ->label(__('core::core.restore'))
                                            ->color('success')
                                            ->button()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(fn ($record) => $record->restore())
                                            ->visible(fn ($livewire, $record) => $record && $record->trashed() && $livewire instanceof EditFullItem),
                                        /* ! Delete ! */
                                        Actions\Action::make('delete')
                                            ->label(__('core::core.delete'))
                                            ->color('danger')
                                            ->link()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(fn ($record) => $record->delete())
                                            ->visible(fn ($livewire, $record) => $record && ! $record->trashed() && $livewire instanceof EditFullItem),
                                        /* !! Form Actions */
                                    ]),
                                    /* !! Meta Form Fields */
                                    /* ! Select ! */
                                    Select::make('type')
                                        ->options(static::getModel()::getTypeOptions())
                                        ->default('post')
                                        ->visible(! empty(config('builder.types')))
                                        ->required(),
                                    /* ! DateTime ! */
                                    DateTimePicker::make('publish_at')
                                        ->label(__('core::core.publish_at')),
                                    /* ! Author ! */
                                    static::getUserFormField(),
                                    /* !! Meta Form Fields */
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
        /* ! Author ! */
        static::initUserModel();

        /* ! Tabs ! */
        $currentTab = static::getCurrentTab();

        return $table
            ->columns([
                /* !! Table Columns */
                /* ! ImageColumn ! */
                ImageColumn::make('featured_image_url')
                    ->label(__('core::core.image'))
                    ->defaultImageUrl(url('/moox/core/assets/noimage.svg'))
                    ->alignment('center')
                    ->square()
                    ->toggleable(),
                /* ! TextColumn ! */
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
                /* ! Author ! */
                static::getUserTableColumn(),
                TextColumn::make('type')
                    ->label(__('core::core.type'))
                    ->visible(! empty(config('builder.types')))
                    ->formatStateUsing(fn ($record): string => config('builder.types')[$record->type] ?? ucfirst($record->type))
                    ->sortable(),
                /* ! Taxonomy ! */
                ...static::getTaxonomyColumns(),
                /* ! Publish ! */
                static::getStatusTableColumn(),
                TextColumn::make('publish_at')
                    ->label(__('core::core.publish_at'))
                    ->dateTime('Y-m-d H:i:s')
                    ->toggleable()
                    ->since()
                    ->sortable(),
                /* !! Table Columns */
            ])
            ->defaultSort('slug', 'desc')
            ->actions([
                /* !! Table Actions */
                /* ! View ! */
                ViewAction::make(),
                /* ! Edit ! */
                EditAction::make()->hidden(fn () => in_array(static::getCurrentTab(), ['trash', 'deleted'])),
                /* !! Table Actions */
            ])
            ->bulkActions([
                /* !! Table Bulk Actions */
                /* ! Delete ! */
                DeleteBulkAction::make()->hidden(function () use ($currentTab) {
                    $isHidden = in_array($currentTab, ['trash', 'deleted']);

                    return $isHidden;
                }),
                /* ! Restore ! */
                RestoreBulkAction::make()->visible(function () use ($currentTab) {
                    $isVisible = in_array($currentTab, ['trash', 'deleted']);

                    return $isVisible;
                }),
                /* !! Table Bulk Actions */
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
                /* !! Table Filters */
                /* ! Select ! */
                SelectFilter::make('type')
                    ->options(static::getModel()::getTypeOptions())
                    ->label(__('core::core.type')),
                ...static::getTableFilters(),
                /* ! Taxonomy ! */
                ...static::getTaxonomyFilters(),
                /* ! Author ! */
                ...static::getUserFilters(),
                /* !! Table Filters */
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
