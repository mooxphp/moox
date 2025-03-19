<?php

declare(strict_types=1);

namespace App\Builder\Resources;

use App\Builder\Models\SoftItem;
use App\Builder\Resources\SoftItemResource\Pages\CreateSoftItem;
use App\Builder\Resources\SoftItemResource\Pages\EditSoftItem;
use App\Builder\Resources\SoftItemResource\Pages\ListSoftItems;
use App\Builder\Resources\SoftItemResource\Pages\ViewSoftItem;
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
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Override;

class SoftItemResource extends Resource
{
    use BaseInResource;
    use HasResourceTabs;
    use SingleSoftDeleteInResource;

    protected static ?string $model = SoftItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    #[Override]
    public static function getModelLabel(): string
    {
        return config('previews.soft-item.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('previews.soft-item.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('previews.soft-item.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('previews.soft-item.single');
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
                                        ->maxLength(255)->nullable(),
                                    Textarea::make('content')
                                        ->label('Content')->required(),
                                    TextInput::make('keks')
                                        ->label('Keks')
                                        ->maxLength(255)->required(),
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
                                    Select::make('type')
                                        ->label('Type')
                                        ->placeholder(__('core::core.type'))
                                        ->options(['Post' => 'Post', 'Page' => 'Page'])
                                        ->required(),
                                ]),
                            Section::make('')
                                ->schema([
                                    Select::make('status')
                                        ->label('Status')
                                        ->placeholder(__('core::core.status'))
                                        ->options(['Probably' => 'Probably', 'Never' => 'Never', 'Done' => 'Done', 'Maybe' => 'Maybe'])
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
        static::getCurrentTab();

        return $table
            ->columns([
                TextColumn::make('title'),
                TextColumn::make('content')
                    ->limit(50),
                TextColumn::make('keks'),
                TextColumn::make('type')->sortable()->searchable()->toggleable(),
                TextColumn::make('status')->sortable()->searchable()->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->actions([...static::getTableActions()])
            ->bulkActions([...static::getBulkActions()])
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
                Filter::make('keks')
                    ->form([
                        TextInput::make('keks')
                            ->label('Keks')
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['keks'],
                        fn (Builder $query, $value): Builder => $query->where('keks', 'like', sprintf('%%%s%%', $value)),
                    ))
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['keks']) {
                            return null;
                        }

                        return 'Keks: '.$data['keks'];
                    }),
                SelectFilter::make('type')
                    ->label('Type')
                    ->placeholder(__('core::core.filter').' Type')
                    ->options(['Post' => 'Post', 'Page' => 'Page']),
                SelectFilter::make('status')
                    ->label('Status')
                    ->placeholder(__('core::core.filter').' Status')
                    ->options(['Probably' => 'Probably', 'Never' => 'Never', 'Done' => 'Done', 'Maybe' => 'Maybe']),
            ]);
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListSoftItems::route('/'),
            'create' => CreateSoftItem::route('/create'),
            'edit' => EditSoftItem::route('/{record}/edit'),
            'view' => ViewSoftItem::route('/{record}'),
        ];
    }
}
