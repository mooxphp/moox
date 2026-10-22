<?php

declare(strict_types=1);

namespace Moox\Customer\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Validation\ValidationRule;
use Moox\Customer\Models\Customer;
use Moox\Customer\Resources\Customer\Pages\CreateCustomer;
use Moox\Customer\Resources\Customer\Pages\EditCustomer;
use Moox\Customer\Resources\Customer\Pages\ListCustomers;
use Moox\Customer\Resources\Customer\Pages\ViewCustomer;
use Moox\Customer\Support\CustomerRules;
use Moox\Support\Active\HasActiveForm;
use Moox\Support\Archive\HasArchiveForm;
use Moox\Support\Audit\HasAuditForm;
use Moox\Support\Data\HasDataForm;
use Moox\Support\External\HasExternalForm;
use Moox\Support\Filament\FeatureFields;
use Moox\Support\Identity\HasIdentityForm;
use Moox\Core\Traits\Relations\HasResourceRelations;
use Moox\Support\Relation\HasRelationForm;
use Moox\Support\SoftDelete\HasSoftDeleteForm;

class CustomerResource extends Resource
{
    use HasActiveForm;
    use HasArchiveForm;
    use HasAuditForm;
    use HasDataForm;
    use HasExternalForm;
    use HasIdentityForm;
    use HasRelationForm;
    use HasResourceRelations;
    use HasSoftDeleteForm;

    protected static ?string $model = Customer::class;

    protected static ?string $recordTitleAttribute = 'customer_name';

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-storefront';

    public static function getModelLabel(): string
    {
        return __('customer::customer.customer');
    }

    public static function getPluralModelLabel(): string
    {
        return __('customer::customer.customers');
    }

    public static function getNavigationLabel(): string
    {
        return __('customer::customer.customers');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->schema([
                    Grid::make(1)
                        ->schema([
                            static::activeSection(),
                            static::domainSection(),
                            static::externalSection(),
                            static::dataSection(),
                        ])
                        ->columnSpan(2),
                    Grid::make(1)
                        ->schema([
                            static::relationSection()->columns(1),
                            static::identitySection()->columns(1),
                            static::auditSection()->columns(1),
                            static::archiveSection()->columns(1),
                            static::softDeleteSection()->columns(1),
                        ])
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }

    protected static function domainSection(): Section
    {
        $translation = static::featureTranslation();

        return Section::make(FeatureFields::label($translation, 'domain'))
            ->schema([
                Select::make('customer_type')
                    ->label(FeatureFields::label($translation, 'customer_type'))
                    ->options([
                        'B2B' => 'B2B',
                        'B2C' => 'B2C',
                        'B2D' => 'B2D',
                        'B2G' => 'B2G',
                    ])
                    ->required()
                    ->rules(static::featureRules('customer_type')),
                TextInput::make('customer_name')
                    ->label(FeatureFields::label($translation, 'customer_name'))
                    ->required()
                    ->maxLength(255)
                    ->rules(static::featureRules('customer_name')),
                TextInput::make('customer_number')
                    ->label(FeatureFields::label($translation, 'customer_number'))
                    ->required()
                    ->maxLength(255)
                    ->rules(static::featureRules('customer_number')),
                Textarea::make('note')
                    ->label(FeatureFields::label($translation, 'note'))
                    ->rules(static::featureRules('note'))
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    protected static function featureTranslation(): string
    {
        return 'customer::fields';
    }

    /**
     * @return list<string|ValidationRule>
     */
    protected static function featureRules(string $field): array
    {
        return CustomerRules::for($field);
    }

    public static function table(Table $table): Table
    {
        $translation = static::featureTranslation();

        return $table
            ->columns([
                TextColumn::make('customer_number')
                    ->label(FeatureFields::label($translation, 'customer_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer_name')
                    ->label(FeatureFields::label($translation, 'customer_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer_type')
                    ->label(FeatureFields::label($translation, 'customer_type'))
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(FeatureFields::label($translation, 'is_active'))
                    ->boolean(),
                TextColumn::make('external_reference')
                    ->label(FeatureFields::label($translation, 'external_reference'))
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(FeatureFields::label($translation, 'updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('customer_number')
            ->filters([
                SelectFilter::make('customer_type')
                    ->label(FeatureFields::label($translation, 'customer_type'))
                    ->options([
                        'B2B' => 'B2B',
                        'B2C' => 'B2C',
                        'B2D' => 'B2D',
                        'B2G' => 'B2G',
                    ]),
                TernaryFilter::make('is_active')
                    ->label(FeatureFields::label($translation, 'is_active')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'view' => ViewCustomer::route('/{record}'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): string
    {
        return config('customer.navigationgroup.label');
    }
}
