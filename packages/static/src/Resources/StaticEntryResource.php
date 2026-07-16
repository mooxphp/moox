<?php

declare(strict_types=1);

namespace Moox\Static\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Entities\Items\Static\BaseStaticResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Static\Filament\Resources\Concerns\HasStaticCodelistResource;
use Moox\Static\Models\StaticEntry;
use Moox\Static\Resources\StaticEntryResource\Pages\CreateStaticEntry;
use Moox\Static\Resources\StaticEntryResource\Pages\EditStaticEntry;
use Moox\Static\Resources\StaticEntryResource\Pages\ListStaticEntries;
use Moox\Static\Resources\StaticEntryResource\Pages\ViewStaticEntry;

class StaticEntryResource extends BaseStaticResource
{
    use HasResourceTabs;
    use HasStaticCodelistResource;

    protected static ?string $model = StaticEntry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-library-books';

    public static function getModelLabel(): string
    {
        return config('static.resources.static_entry.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('static.resources.static_entry.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('static.resources.static_entry.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('static.resources.static_entry.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('static.navigation_group');
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->components([
                Grid::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('code')
                                    ->label(__('static::fields.code'))
                                    ->maxLength(255)
                                    ->required(),
                                ...static::staticCodelistFormFields(),
                            ])
                            ->columnSpan(2),
                        Grid::make()
                            ->schema([
                                Section::make()
                                    ->schema([
                                        static::getFormActions(),
                                    ]),
                            ])
                            ->columns(1)
                            ->columnSpan(1),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::staticCodelistTableColumns())
            ->defaultSort('code', 'asc')
            ->recordActions([...static::getTableActions()])
            ->toolbarActions([...static::getBulkActions()])
            ->filters([
                Filter::make('code')
                    ->schema([
                        TextInput::make('code')
                            ->label(__('static::fields.code'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['code'] ?? null,
                            fn (Builder $query, string $value): Builder => $query->where('code', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['code'])) {
                            return null;
                        }

                        return 'Code: '.$data['code'];
                    }),
                static::staticCodelistCommonNameFilter(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaticEntries::route('/'),
            'create' => CreateStaticEntry::route('/create'),
            'edit' => EditStaticEntry::route('/{record}/edit'),
            'view' => ViewStaticEntry::route('/{record}'),
        ];
    }
}
