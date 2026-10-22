<?php

declare(strict_types=1);

namespace Moox\Supplier\Resources;

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
use Moox\Supplier\Models\Supplier;
use Moox\Supplier\Resources\Supplier\Pages\CreateSupplier;
use Moox\Supplier\Resources\Supplier\Pages\EditSupplier;
use Moox\Supplier\Resources\Supplier\Pages\ListSuppliers;
use Moox\Supplier\Resources\Supplier\Pages\ViewSupplier;
use Moox\Supplier\Support\SupplierRules;
use Moox\Support\Active\HasActiveForm;
use Moox\Support\Archive\HasArchiveForm;
use Moox\Support\Audit\HasAuditForm;
use Moox\Support\Data\HasDataForm;
use Moox\Support\External\HasExternalForm;
use Moox\Core\Traits\Relations\HasResourceRelations;
use Moox\Support\Filament\FeatureFields;
use Moox\Support\Identity\HasIdentityForm;
use Moox\Support\Relation\HasRelationForm;
use Moox\Support\SoftDelete\HasSoftDeleteForm;

class SupplierResource extends Resource
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

    protected static ?string $model = Supplier::class;

    protected static ?string $recordTitleAttribute = 'supplier_name';

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-local-shipping';

    public static function getModelLabel(): string
    {
        return __('supplier::supplier.supplier');
    }

    public static function getPluralModelLabel(): string
    {
        return __('supplier::supplier.suppliers');
    }

    public static function getNavigationLabel(): string
    {
        return __('supplier::supplier.suppliers');
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
                Select::make('supplier_type')
                    ->label(FeatureFields::label($translation, 'supplier_type'))
                    ->options([
                        'Producer' => 'Producer',
                        'Distributor' => 'Distributor',
                    ])
                    ->rules(static::featureRules('supplier_type')),
                TextInput::make('supplier_name')
                    ->label(FeatureFields::label($translation, 'supplier_name'))
                    ->required()
                    ->maxLength(255)
                    ->rules(static::featureRules('supplier_name')),
                TextInput::make('supplier_number')
                    ->label(FeatureFields::label($translation, 'supplier_number'))
                    ->required()
                    ->maxLength(255)
                    ->rules(static::featureRules('supplier_number')),
                Textarea::make('note')
                    ->label(FeatureFields::label($translation, 'note'))
                    ->rules(static::featureRules('note'))
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    protected static function featureTranslation(): string
    {
        return 'supplier::fields';
    }

    /**
     * @return list<string|ValidationRule>
     */
    protected static function featureRules(string $field): array
    {
        return SupplierRules::for($field);
    }

    public static function table(Table $table): Table
    {
        $translation = static::featureTranslation();

        return $table
            ->columns([
                TextColumn::make('supplier_number')
                    ->label(FeatureFields::label($translation, 'supplier_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('supplier_name')
                    ->label(FeatureFields::label($translation, 'supplier_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('supplier_type')
                    ->label(FeatureFields::label($translation, 'supplier_type'))
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
            ->defaultSort('supplier_number')
            ->filters([
                SelectFilter::make('supplier_type')
                    ->label(FeatureFields::label($translation, 'supplier_type'))
                    ->options([
                        'Producer' => 'Producer',
                        'Distributor' => 'Distributor',
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
            'index' => ListSuppliers::route('/'),
            'create' => CreateSupplier::route('/create'),
            'view' => ViewSupplier::route('/{record}'),
            'edit' => EditSupplier::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): string
    {
        return config('supplier.navigationgroup.label');
    }
}
