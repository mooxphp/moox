<?php

declare(strict_types=1);

namespace Moox\Supplier\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
use Moox\Core\Entities\Items\Record\BaseRecordResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Core\Traits\Taxonomy\HasResourceTaxonomy;
use Moox\Supplier\Models\Supplier;
use Moox\Supplier\Resources\Supplier\Pages\CreateSupplier;
use Moox\Supplier\Resources\Supplier\Pages\EditSupplier;
use Moox\Supplier\Resources\Supplier\Pages\ListSuppliers;
use Moox\Supplier\Resources\Supplier\Pages\ViewSupplier;
use Moox\Supplier\Support\SupplierRules;

class SupplierResource extends BaseRecordResource
{
    use HasResourceTabs;
    use HasResourceTaxonomy;

    protected static ?string $model = Supplier::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-local-shipping';

    protected static function getEntityType(): string
    {
        return 'supplier';
    }

    public static function getModelLabel(): string
    {
        return config('supplier.resources.supplier.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('supplier.resources.supplier.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('supplier.resources.supplier.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('supplier.resources.supplier.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('supplier.navigation_group');
    }

    public static function form(Schema $form): Schema
    {
        $taxonomyFields = static::getTaxonomyFields();
        $statusOptions = static::configOptions('supplier.statuses');

        $schema = [
            Grid::make()
                ->schema([
                    Section::make(__('supplier::fields.identity'))
                        ->schema([
                            Select::make('status')
                                ->label(__('supplier::fields.status'))
                                ->options($statusOptions)
                                ->required()
                                ->rules(SupplierRules::for('status'))
                                ->default('draft'),
                            TextInput::make('supplier_number')
                                ->label(__('supplier::fields.supplier_number'))
                                ->rules(SupplierRules::for('supplier_number'))
                                ->maxLength(40),
                            TextInput::make('supplier_name')
                                ->label(__('supplier::fields.supplier_name'))
                                ->rules(SupplierRules::for('supplier_name'))
                                ->maxLength(160),
                            TextInput::make('external_reference')
                                ->label(__('supplier::fields.external_reference'))
                                ->rules(SupplierRules::for('external_reference'))
                                ->maxLength(100),
                            Textarea::make('search_terms')
                                ->label(__('supplier::fields.search_terms'))
                                ->rules(SupplierRules::for('search_terms'))
                                ->columnSpanFull(),
                        ])
                        ->columnSpan(2),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    static::getFormActions(),
                                ]),
                            Section::make(__('supplier::fields.settings'))
                                ->schema([
                                    Select::make('language_id')
                                        ->label(__('supplier::fields.language_id'))
                                        ->relationship('language', 'common_name')
                                        ->searchable()
                                        ->preload()
                                        ->rules(SupplierRules::for('language_id')),
                                    TextInput::make('sort')
                                        ->label(__('supplier::fields.sort'))
                                        ->numeric()
                                        ->rules(SupplierRules::for('sort')),
                                    Toggle::make('is_preferred')
                                        ->label(__('supplier::fields.is_preferred')),
                                    Toggle::make('is_active')
                                        ->label(__('supplier::fields.is_active'))
                                        ->default(true),
                                    DateTimePicker::make('approved_at')
                                        ->label(__('supplier::fields.approved_at'))
                                        ->rules(SupplierRules::for('approved_at')),
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
                                ->hidden(fn (?Supplier $record) => $record === null),
                        ])
                        ->columnSpan(1)
                        ->columns(1),
                ])
                ->columns(3)
                ->columnSpanFull(),
            Section::make(__('supplier::fields.procurement'))
                ->schema([
                    TextInput::make('discount_percent')
                        ->label(__('supplier::fields.discount_percent'))
                        ->numeric()
                        ->rules(SupplierRules::for('discount_percent')),
                    TextInput::make('lead_time_days')
                        ->label(__('supplier::fields.lead_time_days'))
                        ->numeric()
                        ->rules(SupplierRules::for('lead_time_days')),
                    TextInput::make('minimum_order_value')
                        ->label(__('supplier::fields.minimum_order_value'))
                        ->numeric()
                        ->rules(SupplierRules::for('minimum_order_value')),
                    Textarea::make('note')
                        ->label(__('supplier::fields.note'))
                        ->rules(SupplierRules::for('note'))
                        ->columnSpanFull(),
                    Textarea::make('data')
                        ->label(__('supplier::fields.data'))
                        ->columnSpanFull()
                        ->cols(100)
                        ->rows(10)
                        ->formatStateUsing(function ($state) {
                            return json_encode((array) $state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        }),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];

        return $form->components($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('supplier_name')
                    ->label(__('supplier::fields.supplier_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('supplier_number')
                    ->label(__('supplier::fields.supplier_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('supplier::fields.status'))
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_preferred')
                    ->label(__('supplier::fields.is_preferred'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('lead_time_days')
                    ->label(__('supplier::fields.lead_time_days'))
                    ->toggleable(),
                TextColumn::make('minimum_order_value')
                    ->label(__('supplier::fields.minimum_order_value'))
                    ->money('EUR')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label(__('supplier::fields.is_active'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ...static::getTaxonomyColumns(),
            ])
            ->defaultSort('supplier_name')
            ->recordActions([...static::getTableActions()])
            ->toolbarActions([...static::getBulkActions()])
            ->filters([
                ...static::getSupplierTableFilters(),
                ...static::getTaxonomyFilters(),
            ])
            ->deferFilters(false)
            ->persistFiltersInSession();
    }

    /**
     * @return array<SelectFilter|TernaryFilter>
     */
    protected static function getSupplierTableFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->label(__('supplier::fields.status'))
                ->options(static::configOptions('supplier.statuses')),
            TernaryFilter::make('is_active')
                ->label(__('supplier::fields.is_active')),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function configOptions(string $configKey): array
    {
        /** @var mixed $configured */
        $configured = config($configKey, []);
        $values = is_array($configured) ? $configured : [];

        $options = [];
        foreach ($values as $value) {
            if (is_string($value) && $value !== '') {
                $options[$value] = $value;
            }
        }

        return $options;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSuppliers::route('/'),
            'create' => CreateSupplier::route('/create'),
            'edit' => EditSupplier::route('/{record}/edit'),
            'view' => ViewSupplier::route('/{record}'),
        ];
    }
}
