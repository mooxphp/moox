<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Entities\Items\Static\BaseStaticResource;
use Moox\Data\Filament\Resources\StaticCertificateKindResource\Pages\CreateStaticCertificateKind;
use Moox\Data\Filament\Resources\StaticCertificateKindResource\Pages\EditStaticCertificateKind;
use Moox\Data\Filament\Resources\StaticCertificateKindResource\Pages\ListStaticCertificateKinds;
use Moox\Data\Filament\Resources\StaticCertificateKindResource\Pages\ViewStaticCertificateKind;
use Moox\Data\Models\StaticCertificateKind;

class StaticCertificateKindResource extends BaseStaticResource
{
    protected static ?string $model = StaticCertificateKind::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-description';

    public static function getModelLabel(): string
    {
        return config('static-certificate-kind.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('static-certificate-kind.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('static-certificate-kind.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('static-certificate-kind.single');
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
                                    ->maxLength(32)
                                    ->required(),
                                Toggle::make('is_normative')
                                    ->label(__('data::fields.is_normative'))
                                    ->default(true),
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
            ->columns(static::staticCodelistTableColumns(extraColumns: [
                IconColumn::make('is_normative')
                    ->label(__('data::fields.is_normative'))
                    ->boolean()
                    ->toggleable(),
            ]))
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
        $managers = config('static-certificate-kind.relation_managers', []);

        if (! is_array($managers)) {
            return [];
        }

        return array_values(array_unique($managers));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaticCertificateKinds::route('/'),
            'create' => CreateStaticCertificateKind::route('/create'),
            'edit' => EditStaticCertificateKind::route('/{record}/edit'),
            'view' => ViewStaticCertificateKind::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }
}
