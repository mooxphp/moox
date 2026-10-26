<?php

declare(strict_types=1);

namespace Moox\Address\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Moox\Address\Models\AddressUsageType;
use Moox\Address\Resources\AddressUsageType\Pages\CreateAddressUsageType;
use Moox\Address\Resources\AddressUsageType\Pages\EditAddressUsageType;
use Moox\Address\Resources\AddressUsageType\Pages\ListAddressUsageTypes;
use Moox\Address\Resources\AddressUsageType\Pages\ViewAddressUsageType;
use Moox\Support\Audit\HasAuditForm;
use Moox\Support\SoftDelete\HasSoftDeleteForm;
use Moox\Support\Title\HasTitleForm;

class AddressUsageTypeResource extends Resource
{
    use HasAuditForm;
    use HasSoftDeleteForm;
    use HasTitleForm;

    protected static ?string $model = AddressUsageType::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-category';

    public static function getModelLabel(): string
    {
        return __('address::address.address_usage_type');
    }

    public static function getPluralModelLabel(): string
    {
        return __('address::address.address_usage_types');
    }

    public static function getNavigationLabel(): string
    {
        return __('address::address.address_usage_types');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('translations');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->schema([
                    Grid::make(1)
                        ->schema([
                            static::titleSection(),
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
            'title', 'slug' => ['required', 'string', 'max:255'],
            default => [],
        };
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ...static::titleColumns(),
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
            'index' => ListAddressUsageTypes::route('/'),
            'create' => CreateAddressUsageType::route('/create'),
            'view' => ViewAddressUsageType::route('/{record}'),
            'edit' => EditAddressUsageType::route('/{record}/edit'),
        ];
    }
}
