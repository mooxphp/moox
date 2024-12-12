<?php

declare(strict_types=1);

namespace Moox\Builder\Resources;

use Camya\Filament\Forms\Components\TitleWithSlugInput;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Builder\Models\SimpleTaxonomy;
use Moox\Builder\Resources\SimpleTaxonomyResource\Pages\CreateSimpleTaxonomy;
use Moox\Builder\Resources\SimpleTaxonomyResource\Pages\EditSimpleTaxonomy;
use Moox\Builder\Resources\SimpleTaxonomyResource\Pages\ListSimpleTaxonomies;
use Moox\Builder\Resources\SimpleTaxonomyResource\Pages\ViewSimpleTaxonomy;
use Moox\Core\Traits\Taxonomy\TaxonomyInResource;

class SimpleTaxonomyResource extends Resource
{
    use TaxonomyInResource;

    protected static ?string $model = SimpleTaxonomy::class;

    protected static ?string $currentTab = null;

    protected static ?string $authorModel = null;

    protected static ?string $navigationIcon = 'gmdi-label';

    public static function form(Form $form): Form
    {
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
                                            ->visible(fn ($livewire, $record) => $record && $record->trashed() && $livewire instanceof ViewSimpleTaxonomy),
                                        Actions\Action::make('save')
                                            ->label(__('core::core.save'))
                                            ->color('primary')
                                            ->button()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(function ($livewire) {
                                                $livewire instanceof CreateSimpleTaxonomy ? $livewire->create() : $livewire->save();
                                            })
                                            ->visible(fn ($livewire) => $livewire instanceof CreateSimpleTaxonomy || $livewire instanceof EditSimpleTaxonomy),
                                        Actions\Action::make('saveAndCreateAnother')
                                            ->label(__('core::core.save_and_create_another'))
                                            ->color('secondary')
                                            ->button()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(function ($livewire) {
                                                $livewire->saveAndCreateAnother();
                                            })
                                            ->visible(fn ($livewire) => $livewire instanceof CreateSimpleTaxonomy),
                                        Actions\Action::make('cancel')
                                            ->label(__('core::core.cancel'))
                                            ->color('secondary')
                                            ->outlined()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->url(fn () => static::getUrl('index'))
                                            ->visible(fn ($livewire) => $livewire instanceof CreateSimpleTaxonomy),
                                        Actions\Action::make('edit')
                                            ->label(__('core::core.edit'))
                                            ->color('primary')
                                            ->button()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->url(fn ($record) => static::getUrl('edit', ['record' => $record]))
                                            ->visible(fn ($livewire, $record) => $livewire instanceof ViewSimpleTaxonomy && ! $record->trashed()),
                                        Actions\Action::make('restore')
                                            ->label(__('core::core.restore'))
                                            ->color('success')
                                            ->button()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(fn ($record) => $record->restore())
                                            ->visible(fn ($livewire, $record) => $record && $record->trashed() && $livewire instanceof EditSimpleTaxonomy),
                                        Actions\Action::make('delete')
                                            ->label(__('core::core.delete'))
                                            ->color('danger')
                                            ->link()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(fn ($record) => $record->delete())
                                            ->visible(fn ($livewire, $record) => $record && ! $record->trashed() && $livewire instanceof EditSimpleTaxonomy),
                                    ]),
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
                EditAction::make()->hidden(fn () => in_array(static::getCurrentTab(), ['trash', 'deleted'])),
            ])
            ->bulkActions([

            ])
            ->filters([

            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSimpleTaxonomies::route('/'),
            'edit' => EditSimpleTaxonomy::route('/{record}/edit'),
            'create' => CreateSimpleTaxonomy::route('/create'),
            'view' => ViewSimpleTaxonomy::route('/{record}'),
        ];
    }

    public static function getModelLabel(): string
    {
        return config('builder.resources.simple-taxonomy.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('builder.resources.simple-taxonomy.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('builder.resources.simple-taxonomy.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('builder.resources.simple-taxonomy.single');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationGroup(): ?string
    {
        return config('builder.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('builder.navigation_sort') + 3;
    }

    public static function getCurrentTab(): ?string
    {
        if (static::$currentTab === null) {
            static::$currentTab = request()->query('tab', '');
        }

        return static::$currentTab ?: null;
    }

    public static function getTableQuery(?string $currentTab = null): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes();

        if ($currentTab === 'trash' || $currentTab === 'deleted') {
            $model = static::getModel();
            if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $query->whereNotNull($model::make()->getQualifiedDeletedAtColumn());
            }
        }

        return $query;
    }

    public static function setCurrentTab(?string $tab): void
    {
        static::$currentTab = $tab;
    }
}
