<?php

declare(strict_types=1);

namespace Moox\Address\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Moox\Address\Models\Address;
use Moox\Address\Support\AddressRules;
use Moox\Address\Resources\Address\Pages\CreateAddress;
use Moox\Address\Resources\Address\Pages\EditAddress;
use Moox\Address\Resources\Address\Pages\ListAddresses;
use Moox\Address\Resources\Address\Pages\ViewAddress;
use Moox\Address\Resources\Address\RelationManagers\AddressablesRelationManager;
use Moox\Core\Entities\Items\Record\BaseRecordResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Core\Traits\Taxonomy\HasResourceTaxonomy;

class AddressResource extends BaseRecordResource
{
    use HasResourceTabs;
    use HasResourceTaxonomy;

    protected static ?string $model = Address::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-location-on';

    protected static function getEntityType(): string
    {
        return 'address';
    }

    public static function getModelLabel(): string
    {
        return config('address.resources.address.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('address.resources.address.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('address.resources.address.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('address.resources.address.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('address.navigation_group');
    }

    public static function form(Schema $form): Schema
    {
        $taxonomyFields = static::getTaxonomyFields();

        $schema = [
            Grid::make()
                ->schema([
                    Section::make()
                        ->schema([
                            TextInput::make('label')
                                ->label(__('address::fields.label'))
                                ->rules(AddressRules::for('label'))
                                ->maxLength(120),
                            TextInput::make('name')
                                ->label(__('address::fields.name'))
                                ->required()
                                ->rules(AddressRules::for('name'))
                                ->maxLength(160),
                            TextInput::make('street')
                                ->label(__('address::fields.street'))
                                ->required()
                                ->rules(AddressRules::for('street'))
                                ->maxLength(160),
                            TextInput::make('street2')
                                ->label(__('address::fields.street2'))
                                ->rules(AddressRules::for('street2'))
                                ->maxLength(160),
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('postal_code')
                                        ->label(__('address::fields.postal_code'))
                                        ->required()
                                        ->rules(AddressRules::for('postal_code'))
                                        ->maxLength(20),
                                    TextInput::make('city')
                                        ->label(__('address::fields.city'))
                                        ->required()
                                        ->rules(AddressRules::for('city'))
                                        ->maxLength(120),
                                ]),
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('state')
                                        ->label(__('address::fields.state'))
                                        ->rules(AddressRules::for('state'))
                                        ->maxLength(120),
                                    TextInput::make('country_code')
                                        ->label(__('address::fields.country_code'))
                                        ->required()
                                        ->rules(AddressRules::for('country_code'))
                                        ->maxLength(2)
                                        ->length(2),
                                ]),
                            Toggle::make('is_primary')
                                ->label(__('address::fields.is_primary')),
                        ])
                        ->columnSpan(2),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    static::getFormActions(),
                                ]),
                            Section::make('')
                                ->schema($taxonomyFields),
                            Section::make('')
                                ->schema([
                                    Section::make('')
                                        ->schema([
                                            ...static::getStandardTimestampFields(),
                                        ]),
                                ])
                                ->hidden(fn (?Address $record) => $record === null),
                        ])
                        ->columnSpan(1)
                        ->columns(1),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ];

        return $form->components($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label(__('address::fields.label'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('address::fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('city')
                    ->label(__('address::fields.city'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('postal_code')
                    ->label(__('address::fields.postal_code'))
                    ->searchable(),
                TextColumn::make('country_code')
                    ->label(__('address::fields.country_code')),
                IconColumn::make('is_primary')
                    ->label(__('address::fields.is_primary'))
                    ->boolean(),
                TextColumn::make('addressables_count')
                    ->counts('addressables')
                    ->label(__('address::fields.assignments')),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                ...static::getTaxonomyColumns(),
            ])
            ->defaultSort('name')
            ->recordActions([...static::getTableActions()])
            ->toolbarActions([...static::getBulkActions()])
            ->filters([
                ...static::getAddressTableFilters(),
                ...static::getTaxonomyFilters(),
            ])
            ->deferFilters(false)
            ->persistFiltersInSession();
    }

    /**
     * @return array<SelectFilter|TernaryFilter>
     */
    protected static function getAddressTableFilters(): array
    {
        return [
            SelectFilter::make('city')
                ->label(__('address::fields.city'))
                ->options(fn (): array => static::getDistinctFilterOptions('city'))
                ->searchable()
                ->preload(),
            SelectFilter::make('postal_code')
                ->label(__('address::fields.postal_code'))
                ->options(fn (): array => static::getDistinctFilterOptions('postal_code'))
                ->searchable()
                ->preload(),
            TernaryFilter::make('is_primary')
                ->label(__('address::fields.is_primary')),
            SelectFilter::make('country_code')
                ->label(__('address::fields.country_code'))
                ->options(fn (): array => static::getCountryFilterOptions())
                ->searchable()
                ->preload(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function getDistinctFilterOptions(string $column): array
    {
        return Address::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column, $column)
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected static function getCountryFilterOptions(): array
    {
        if (class_exists(\Moox\Data\Models\StaticCountry::class)) {
            /** @var class-string<\Illuminate\Database\Eloquent\Model> $model */
            $model = \Moox\Data\Models\StaticCountry::class;

            return $model::query()
                ->whereNotNull('alpha2')
                ->orderBy('common_name')
                ->get(['alpha2', 'common_name'])
                ->mapWithKeys(fn ($country): array => [
                    strtoupper((string) $country->alpha2) => sprintf('%s %s', $country->name, strtoupper((string) $country->alpha2)),
               
                ])
                ->all();
        }

        return static::getDistinctFilterOptions('country_code');
    }

    /**
     * @return array<class-string>
     */
    public static function getRelations(): array
    {
        return [
            AddressablesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAddresses::route('/'),
            'create' => CreateAddress::route('/create'),
            'edit' => EditAddress::route('/{record}/edit'),
            'view' => ViewAddress::route('/{record}'),
        ];
    }
}
