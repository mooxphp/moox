<?php

declare(strict_types=1);

namespace Moox\Address\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Validation\ValidationRule;
use Moox\Address\Models\AddressUsage;
use Moox\Address\Resources\AddressUsage\Pages\CreateAddressUsage;
use Moox\Address\Resources\AddressUsage\Pages\EditAddressUsage;
use Moox\Address\Resources\AddressUsage\Pages\ListAddressUsages;
use Moox\Address\Resources\AddressUsage\Pages\ViewAddressUsage;
use Moox\Support\Audit\HasAuditForm;
use Moox\Support\Filament\FeatureFields;
use Moox\Support\Relation\HasRelationForm;
use Moox\Support\SoftDelete\HasSoftDeleteForm;

class AddressUsageResource extends Resource
{
    use HasAuditForm;
    use HasRelationForm;
    use HasSoftDeleteForm;

    protected static ?string $model = AddressUsage::class;

    protected static ?string $recordTitleAttribute = 'id';

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-label';

    public static function getModelLabel(): string
    {
        return __('address::address.address_usage');
    }

    public static function getPluralModelLabel(): string
    {
        return __('address::address.address_usages');
    }

    public static function getNavigationLabel(): string
    {
        return __('address::address.address_usages');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->schema([
                    Grid::make(1)
                        ->schema([
                            static::relationSection()->columns(1),
                        ])
                        ->columnSpan(2),
                    Grid::make(1)
                        ->schema([
                            static::auditSection()->columns(1),
                            static::softDeleteSection()->columns(1),
                        ])
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }

    protected static function featureTranslation(): string
    {
        return 'address::fields';
    }

    /**
     * @return list<string|ValidationRule>
     */
    protected static function featureRules(string $field): array
    {
        return match ($field) {
            'address_id', 'address_usage_type_id' => ['required', 'integer'],
            default => [],
        };
    }

    public static function table(Table $table): Table
    {
        $translation = static::featureTranslation();

        return $table
            ->columns([
                TextColumn::make('address_id')
                    ->label(FeatureFields::label($translation, 'address_id'))
                    ->sortable(),
                TextColumn::make('address_usage_type_id')
                    ->label(FeatureFields::label($translation, 'address_usage_type_id'))
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(FeatureFields::label($translation, 'updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id')
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
            'index' => ListAddressUsages::route('/'),
            'create' => CreateAddressUsage::route('/create'),
            'view' => ViewAddressUsage::route('/{record}'),
            'edit' => EditAddressUsage::route('/{record}/edit'),
        ];
    }
}
