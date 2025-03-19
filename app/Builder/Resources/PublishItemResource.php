<?php

declare(strict_types=1);

namespace App\Builder\Resources;

use App\Builder\Resources\PublishItemResource\Pages;
use Camya\Filament\Forms\Components\TitleWithSlugInput;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Publish\SinglePublishInResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;

class PublishItemResource extends Resource
{
    use BaseInResource, HasResourceTabs, SinglePublishInResource;

    protected static ?string $model = \App\Builder\Models\PublishItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getModelLabel(): string
    {
        return config('previews.publish-item.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('previews.publish-item.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('previews.publish-item.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('previews.publish-item.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('previews.navigation_group');
    }

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
                                    MarkdownEditor::make('content')
                                        ->label('Content')->required(),
                                ]),
                        ])
                        ->columnSpan(['lg' => 2]),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    static::getFormActions(),
                                    static::getPublishAtFormField(),
                                ]),
                        ])
                        ->columnSpan(['lg' => 1]),
                ])
                ->columns(['lg' => 3]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('publish_at')
                    ->label(__('core::core.publish_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('published_at')
                    ->label(__('core::core.published_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('content')
                    ->markdown(),
            ])
            ->defaultSort('title', 'desc')
            ->actions([])
            ->bulkActions([])
            ->filters([
                Filter::make('title')
                    ->form([
                        TextInput::make('title')
                            ->label('Title')
                            ->placeholder(__('core::core.filter').' Title'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['title'],
                            fn (Builder $query, $value): Builder => $query->where('title', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['title']) {
                            return null;
                        }

                        return 'Title: '.$data['title'];
                    }),
                Filter::make('slug')
                    ->form([
                        TextInput::make('slug')
                            ->label(__('core::core.slug'))
                            ->placeholder(__('core::core.filter').' Title'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['slug'],
                            fn (Builder $query, $value): Builder => $query->where('slug', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['slug']) {
                            return null;
                        }

                        return __('core::core.slug').': '.$data['slug'];
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPublishItems::route('/'),
            'create' => Pages\CreatePublishItem::route('/create'),
            'edit' => Pages\EditPublishItem::route('/{record}/edit'),
            'view' => Pages\ViewPublishItem::route('/{record}'),
        ];
    }
}
