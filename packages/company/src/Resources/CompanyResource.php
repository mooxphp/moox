<?php

declare(strict_types=1);

namespace Moox\Company\Resources;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Moox\Company\Models\Company;
use Moox\Company\Resources\Company\Pages\CreateCompany;
use Moox\Company\Resources\Company\Pages\EditCompany;
use Moox\Company\Resources\Company\Pages\ListCompanies;
use Moox\Company\Resources\Company\Pages\ViewCompany;
use Moox\Company\Support\CompanyRules;
use Moox\Core\Entities\Items\Record\BaseRecordResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Core\Traits\Taxonomy\HasResourceTaxonomy;

class CompanyResource extends BaseRecordResource
{
    use HasResourceTabs;
    use HasResourceTaxonomy;

    protected static ?string $model = Company::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-business';

    protected static function getEntityType(): string
    {
        return 'company';
    }

    public static function getModelLabel(): string
    {
        return config('company.resources.company.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('company.resources.company.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('company.resources.company.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('company.resources.company.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('company.navigation_group');
    }

    public static function form(Schema $form): Schema
    {
        $taxonomyFields = static::getTaxonomyFields();
        $statusOptions = static::configOptions('company.statuses');

        $identityFields = [
            Select::make('status')
                ->label(__('company::fields.status'))
                ->options($statusOptions)
                ->required()
                ->rules(CompanyRules::for('status'))
                ->default('draft'),
            TextInput::make('name')
                ->label(__('company::fields.name'))
                ->rules(CompanyRules::for('name'))
                ->maxLength(120),
            TextInput::make('display_name')
                ->label(__('company::fields.display_name'))
                ->rules(CompanyRules::for('display_name'))
                ->maxLength(120),
            TextInput::make('legal_name')
                ->label(__('company::fields.legal_name'))
                ->rules(CompanyRules::for('legal_name'))
                ->maxLength(120),
            TextInput::make('external_reference')
                ->label(__('company::fields.external_reference'))
                ->rules(CompanyRules::for('external_reference'))
                ->maxLength(100),
            Textarea::make('note')
                ->label(__('company::fields.note'))
                ->rules(CompanyRules::for('note'))
                ->columnSpanFull(),
            Textarea::make('data')
                ->label(__('company::fields.data'))
                ->columnSpanFull()
                ->cols(100)
                ->rows(10)
                ->formatStateUsing(function ($state) {
                    return json_encode((array) $state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                }),
        ];

        $schema = [
            Grid::make()
                ->schema([
                    Section::make(__('company::fields.identity'))
                        ->schema($identityFields)
                        ->columnSpan(2),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    static::getFormActions(),
                                ]),
                            Section::make(__('company::fields.contact'))
                                ->schema([
                                    TextInput::make('phone')
                                        ->label(__('company::fields.phone'))
                                        ->tel()
                                        ->rules(CompanyRules::for('phone'))
                                        ->maxLength(30),
                                    TextInput::make('fax')
                                        ->label(__('company::fields.fax'))
                                        ->rules(CompanyRules::for('fax'))
                                        ->maxLength(30),
                                    TextInput::make('email')
                                        ->label(__('company::fields.email'))
                                        ->email()
                                        ->rules(CompanyRules::for('email'))
                                        ->maxLength(100),
                                    TextInput::make('url')
                                        ->label(__('company::fields.url'))
                                        ->url()
                                        ->rules(CompanyRules::for('url'))
                                        ->maxLength(255),
                                ]),
                            Section::make(__('company::fields.tax'))
                                ->schema([
                                    TextInput::make('tax_number')
                                        ->label(__('company::fields.tax_number'))
                                        ->rules(CompanyRules::for('tax_number'))
                                        ->maxLength(30),
                                    TextInput::make('vat_number')
                                        ->label(__('company::fields.vat_number'))
                                        ->rules(CompanyRules::for('vat_number'))
                                        ->maxLength(30),
                                ]),
                            Section::make(__('company::fields.settings'))
                                ->schema([
                                    TextInput::make('default_currency_code')
                                        ->label(__('company::fields.default_currency_code'))
                                        ->required()
                                        ->rules(CompanyRules::for('default_currency_code'))
                                        ->maxLength(3)
                                        ->length(3)
                                        ->default(config('company.default_currency_code', 'EUR')),
                                    Select::make('language_id')
                                        ->label(__('company::fields.language_id'))
                                        ->relationship('language', 'common_name')
                                        ->searchable()
                                        ->preload()
                                        ->rules(CompanyRules::for('language_id')),
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
                                ->hidden(fn (?Company $record) => $record === null),
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
        $hasParentRelation = static::hasConfiguredRelation('parent');
        $hasChildrenRelation = static::hasConfiguredRelation('children');

        $columns = [
            TextColumn::make('display_name')
                ->label(__('company::fields.display_name'))
                ->searchable()
                ->toggleable(),
            TextColumn::make('status')
                ->label(__('company::fields.status'))
                ->badge()
                ->color(
                    fn (string $state): string => match ($state) {
                        'draft' => 'info',
                        'active' => 'success',
                        'inactive' => 'warning',
                        'approved' => 'success',
                        'archived' => 'danger',
                        default => 'gray',
                    }
                )
                ->sortable(),
        ];

        if ($hasParentRelation) {
            $columns[] = TextColumn::make('parent.display_name')
                ->label(__('company::fields.parent'))
                ->toggleable();
        }

        $columns = [
            ...$columns,
            TextColumn::make('email')
                ->label(__('company::fields.email'))
                ->searchable()
                ->toggleable(),
            TextColumn::make('contacts_count')
                ->counts('contacts')
                ->numeric()
                ->label(__('company::fields.contacts'))
                ->toggleable(),
            TextColumn::make('addresses_count')
                ->counts('addresses')
                ->label(__('company::fields.addresses'))
                ->numeric()
                ->toggleable(),
        ];

        if ($hasChildrenRelation) {
            $columns[] = TextColumn::make('children_count')
                ->counts('children')
                ->label(__('company::fields.children'));
        }

        return $table
            ->columns([
                ...$columns,
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ...static::getTaxonomyColumns(),
            ])
            ->defaultSort('name')
            ->recordActions([...static::getTableActions()])
            ->toolbarActions([...static::getBulkActions()])
            ->filters([
                ...static::getCompanyTableFilters(),
                ...static::getTaxonomyFilters(),
            ])
            ->deferFilters(false)
            ->persistFiltersInSession();
    }

    /**
     * @return array<SelectFilter>
     */
    protected static function getCompanyTableFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->label(__('company::fields.status'))
                ->options(static::configOptions('company.statuses')),
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
            'index' => ListCompanies::route('/'),
            'create' => CreateCompany::route('/create'),
            'edit' => EditCompany::route('/{record}/edit'),
            'view' => ViewCompany::route('/{record}'),
        ];
    }
}
