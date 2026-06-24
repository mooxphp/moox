<?php

declare(strict_types=1);

namespace Moox\Customer\Resources;

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
use Moox\Customer\Models\Customer;
use Moox\Customer\Resources\Customer\Pages\CreateCustomer;
use Moox\Customer\Resources\Customer\Pages\EditCustomer;
use Moox\Customer\Resources\Customer\Pages\ListCustomers;
use Moox\Customer\Resources\Customer\Pages\ViewCustomer;
use Moox\Customer\Support\CustomerRules;

class CustomerResource extends BaseRecordResource
{
    use HasResourceTabs;
    use HasResourceTaxonomy;

    protected static ?string $model = Customer::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-person';

    protected static function getEntityType(): string
    {
        return 'customer';
    }

    public static function getModelLabel(): string
    {
        return config('customer.resources.customer.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('customer.resources.customer.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('customer.resources.customer.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('customer.resources.customer.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('customer.navigation_group');
    }

    public static function form(Schema $form): Schema
    {
        $taxonomyFields = static::getTaxonomyFields();
        $statusOptions = static::configOptions('customer.statuses');
        $priceTypeOptions = static::configOptions('customer.price_types');

        $schema = [
            Grid::make()
                ->schema([
                    Section::make(__('customer::fields.identity'))
                        ->schema([
                            Select::make('status')
                                ->label(__('customer::fields.status'))
                                ->options($statusOptions)
                                ->required()
                                ->rules(CustomerRules::for('status'))
                                ->default('draft'),
                            TextInput::make('customer_number')
                                ->label(__('customer::fields.customer_number'))
                                ->rules(CustomerRules::for('customer_number'))
                                ->maxLength(40),
                            TextInput::make('external_reference')
                                ->label(__('customer::fields.external_reference'))
                                ->rules(CustomerRules::for('external_reference'))
                                ->maxLength(100),
                            Textarea::make('search_terms')
                                ->label(__('customer::fields.search_terms'))
                                ->rules(CustomerRules::for('search_terms'))
                                ->columnSpanFull(),
                        ])
                        ->columnSpan(2),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    static::getFormActions(),
                                ]),
                            Section::make(__('customer::fields.settings'))
                                ->schema([
                                    Select::make('language_id')
                                        ->label(__('customer::fields.language_id'))
                                        ->relationship('language', 'common_name')
                                        ->searchable()
                                        ->preload()
                                        ->rules(CustomerRules::for('language_id')),
                                    TextInput::make('sort')
                                        ->label(__('customer::fields.sort'))
                                        ->numeric()
                                        ->rules(CustomerRules::for('sort')),
                                    Toggle::make('is_active')
                                        ->label(__('customer::fields.is_active'))
                                        ->default(true),
                                    DateTimePicker::make('approved_at')
                                        ->label(__('customer::fields.approved_at'))
                                        ->rules(CustomerRules::for('approved_at')),
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
                                ->hidden(fn (?Customer $record) => $record === null),
                        ])
                        ->columnSpan(1)
                        ->columns(1),
                ])
                ->columns(3)
                ->columnSpanFull(),
            Section::make(__('customer::fields.commercial'))
                ->schema([
                    Select::make('price_type')
                        ->label(__('customer::fields.price_type'))
                        ->options($priceTypeOptions)
                        ->rules(CustomerRules::for('price_type')),
                    TextInput::make('customer_group')
                        ->label(__('customer::fields.customer_group'))
                        ->rules(CustomerRules::for('customer_group'))
                        ->maxLength(50),
                    TextInput::make('discount_percent')
                        ->label(__('customer::fields.discount_percent'))
                        ->numeric()
                        ->rules(CustomerRules::for('discount_percent')),
                    TextInput::make('credit_limit')
                        ->label(__('customer::fields.credit_limit'))
                        ->numeric()
                        ->rules(CustomerRules::for('credit_limit')),
                    Textarea::make('note')
                        ->label(__('customer::fields.note'))
                        ->rules(CustomerRules::for('note'))
                        ->columnSpanFull(),
                    Textarea::make('data')
                        ->label(__('customer::fields.data'))
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
                TextColumn::make('customer_number')
                    ->label(__('customer::fields.customer_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('customer::fields.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('price_type')
                    ->label(__('customer::fields.price_type'))
                    ->toggleable(),
                TextColumn::make('customer_group')
                    ->label(__('customer::fields.customer_group'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('credit_limit')
                    ->label(__('customer::fields.credit_limit'))
                    ->money('EUR')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label(__('customer::fields.is_active'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ...static::getTaxonomyColumns(),
            ])
            ->defaultSort('customer_number')
            ->recordActions([...static::getTableActions()])
            ->toolbarActions([...static::getBulkActions()])
            ->filters([
                ...static::getCustomerTableFilters(),
                ...static::getTaxonomyFilters(),
            ])
            ->deferFilters(false)
            ->persistFiltersInSession();
    }

    /**
     * @return array<SelectFilter|TernaryFilter>
     */
    protected static function getCustomerTableFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->label(__('customer::fields.status'))
                ->options(static::configOptions('customer.statuses')),
            TernaryFilter::make('is_active')
                ->label(__('customer::fields.is_active')),
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
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
            'view' => ViewCustomer::route('/{record}'),
        ];
    }
}
