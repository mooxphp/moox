<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Entities\Items\Static\BaseStaticResource;
use Moox\Data\Filament\Resources\StaticVatExemptionReasonResource\Pages\CreateStaticVatExemptionReason;
use Moox\Data\Filament\Resources\StaticVatExemptionReasonResource\Pages\EditStaticVatExemptionReason;
use Moox\Data\Filament\Resources\StaticVatExemptionReasonResource\Pages\ListStaticVatExemptionReasons;
use Moox\Data\Filament\Resources\StaticVatExemptionReasonResource\Pages\ViewStaticVatExemptionReason;
use Moox\Data\Models\StaticVatExemptionReason;
use Moox\Static\Filament\Resources\Concerns\HasStaticCodelistResource;

class StaticVatExemptionReasonResource extends BaseStaticResource
{
    use HasStaticCodelistResource;

    protected static ?string $model = StaticVatExemptionReason::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-rule';

    public static function getModelLabel(): string
    {
        return config('static-vat-exemption-reason.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('static-vat-exemption-reason.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('static-vat-exemption-reason.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('static-vat-exemption-reason.single');
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
                                    ->maxLength(64)
                                    ->required(),
                                TextInput::make('vat_category_code')
                                    ->label(__('data::fields.vat_category_code'))
                                    ->maxLength(10),
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
                TextColumn::make('vat_category_code')
                    ->label(__('data::fields.vat_category_code'))
                    ->sortable()
                    ->searchable(),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaticVatExemptionReasons::route('/'),
            'create' => CreateStaticVatExemptionReason::route('/create'),
            'edit' => EditStaticVatExemptionReason::route('/{record}/edit'),
            'view' => ViewStaticVatExemptionReason::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }
}
