<?php

declare(strict_types=1);

namespace App\Builder\Resources;

use App\Builder\Models\TranslateItem;
use App\Builder\Resources\TranslateItemResource\Pages\CreateTranslateItem;
use App\Builder\Resources\TranslateItemResource\Pages\EditTranslateItem;
use App\Builder\Resources\TranslateItemResource\Pages\ListTranslateItems;
use App\Builder\Resources\TranslateItemResource\Pages\ViewTranslateItem;
use Camya\Filament\Forms\Components\TitleWithSlugInput;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Simple\SingleSimpleInResource;
use Override;

class TranslateItemResource extends Resource
{
    use BaseInResource;
    use SingleSimpleInResource;

    protected static ?string $model = TranslateItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    #[Override]
    public static function getModelLabel(): string
    {
        return config('previews.translate-item.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('previews.translate-item.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('previews.translate-item.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('previews.translate-item.single');
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
                                    TitleWithSlugInput::make(
                                        fieldTitle: 'title',
                                        fieldSlug: 'slug',
                                    ),
                                    Textarea::make('content')
                                        ->label('Content')->required(),
                                ]),
                            Section::make('Address')
                                ->schema([
                                    TextInput::make('street'),
                                    TextInput::make('city'),
                                    TextInput::make('postal_code'),
                                    TextInput::make('country'),
                                ]),
                        ])
                        ->columnSpan(['lg' => 2]),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    static::getFormActions(),
                                ]),
                            Section::make('')
                                ->schema([
                                    Select::make('status')
                                        ->label('Status')
                                        ->placeholder(__('core::core.status'))
                                        ->options(['Probably' => 'Probably', 'Never' => 'Never', 'Done' => 'Done', 'Maybe' => 'Maybe'])
                                        ->required(),
                                ]),
                            Section::make('')
                                ->schema([
                                    Select::make('type')
                                        ->label('Type')
                                        ->placeholder(__('core::core.type'))
                                        ->options(['Post' => 'Post', 'Page' => 'Page'])
                                        ->required(),
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
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('content')
                    ->limit(50),
                TextColumn::make('status')->sortable()->searchable()->toggleable(),
                TextColumn::make('type')->sortable()->searchable()->toggleable(),
            ])
            ->defaultSort('title', 'desc')
            ->actions([...static::getTableActions()])
            ->bulkActions([...static::getBulkActions()])
            ->filters([
                Filter::make('title')
                    ->form([
                        TextInput::make('title')
                            ->label('Title')
                            ->placeholder(__('core::core.filter').' Title'),
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
                            ->label(__('core::core.slug'))
                            ->placeholder(__('core::core.filter').' Title'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['slug'],
                        fn (Builder $query, $value): Builder => $query->where('slug', 'like', sprintf('%%%s%%', $value)),
                    ))
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['slug']) {
                            return null;
                        }

                        return __('core::core.slug').': '.$data['slug'];
                    }),
                SelectFilter::make('status')
                    ->label('Status')
                    ->placeholder(__('core::core.filter').' Status')
                    ->options(['Probably' => 'Probably', 'Never' => 'Never', 'Done' => 'Done', 'Maybe' => 'Maybe']),
                SelectFilter::make('type')
                    ->label('Type')
                    ->placeholder(__('core::core.filter').' Type')
                    ->options(['Post' => 'Post', 'Page' => 'Page']),
            ]);
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListTranslateItems::route('/'),
            'create' => CreateTranslateItem::route('/create'),
            'edit' => EditTranslateItem::route('/{record}/edit'),
            'view' => ViewTranslateItem::route('/{record}'),
        ];
    }

    public static function enableCreate(): bool
    {
        return false;
    }
}
