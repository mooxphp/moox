<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Entities\Items\Static\BaseStaticResource;
use Moox\Data\Filament\Resources\StaticEasSchemeResource\Pages\CreateStaticEasScheme;
use Moox\Data\Filament\Resources\StaticEasSchemeResource\Pages\EditStaticEasScheme;
use Moox\Data\Filament\Resources\StaticEasSchemeResource\Pages\ListStaticEasSchemes;
use Moox\Data\Filament\Resources\StaticEasSchemeResource\Pages\ViewStaticEasScheme;
use Moox\Data\Models\StaticEasScheme;
use Moox\Static\Filament\Resources\Concerns\HasStaticCodelistResource;

class StaticEasSchemeResource extends BaseStaticResource
{
    use HasStaticCodelistResource;

    protected static ?string $model = StaticEasScheme::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-schema';

    public static function getModelLabel(): string
    {
        return config('static-eas-scheme.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('static-eas-scheme.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('static-eas-scheme.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('static-eas-scheme.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('data.navigation-group');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('code')
                                    ->label(__('data::fields.code'))
                                    ->maxLength(10)
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
                            ->label(__('data::fields.code'))
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaticEasSchemes::route('/'),
            'create' => CreateStaticEasScheme::route('/create'),
            'edit' => EditStaticEasScheme::route('/{record}/edit'),
            'view' => ViewStaticEasScheme::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }
}
