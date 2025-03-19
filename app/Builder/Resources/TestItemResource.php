<?php

declare(strict_types=1);

namespace App\Builder\Resources;

use App\Builder\Resources\TestItemResource\Pages;
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

class TestItemResource extends Resource
{
    use BaseInResource, SingleSimpleInResource;

    protected static ?string $model = \App\Builder\Models\TestItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getModelLabel(): string
    {
        return config('previews.test-item.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('previews.test-item.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('previews.test-item.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('previews.test-item.single');
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
                                    TextInput::make('title')
                                        ->label('Title')
                                        ->maxLength(255)->required(),
                                    Textarea::make('content')
                                        ->label('Content'),
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title'),
                TextColumn::make('content')
                    ->limit(50),
                TextColumn::make('status')->sortable()->searchable()->toggleable(),
                TextColumn::make('type')->sortable()->searchable()->toggleable(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTestItems::route('/'),
            'create' => Pages\CreateTestItem::route('/create'),
            'edit' => Pages\EditTestItem::route('/{record}/edit'),
            'view' => Pages\ViewTestItem::route('/{record}'),
        ];
    }

    public static function enableCreate(): bool
    {
        return false;
    }
}
