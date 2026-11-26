<?php

declare(strict_types=1);

namespace Moox\Organization\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Moox\Organization\Models\OrganizationType;
use Moox\Organization\Resources\OrganizationType\Pages\CreateOrganizationType;
use Moox\Organization\Resources\OrganizationType\Pages\EditOrganizationType;
use Moox\Organization\Resources\OrganizationType\Pages\ListOrganizationTypes;
use Moox\Organization\Resources\OrganizationType\Pages\ViewOrganizationType;
use Moox\Support\Active\Columns\HasActiveColumns;
use Moox\Support\Active\Forms\HasActiveForm;
use Moox\Support\Audit\Forms\HasAuditForm;
use Moox\Support\Filament\FeatureFields;
use Moox\Support\Identity\Forms\HasIdentityForm;
use Moox\Support\SoftDelete\Forms\HasSoftDeleteForm;
use Moox\Support\Title\Forms\HasTitleForm;

class OrganizationTypeResource extends Resource
{
    use HasActiveColumns;
    use HasActiveForm;
    use HasAuditForm;
    use HasIdentityForm;
    use HasSoftDeleteForm;
    use HasTitleForm;

    protected static ?string $model = OrganizationType::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-domain';

    public static function getModelLabel(): string
    {
        return __('organization::organization.organization_type');
    }

    public static function getPluralModelLabel(): string
    {
        return __('organization::organization.organization_types');
    }

    public static function getNavigationLabel(): string
    {
        return __('organization::organization.organization_types');
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
                            static::activeSection(),
                            static::titleSection(),
                        ])
                        ->columnSpan(2),
                    Grid::make(1)
                        ->schema([
                            static::identitySection()->columns(1),
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
        return 'organization::fields';
    }

    /**
     * @return list<string|ValidationRule>
     */
    protected static function featureRules(string $field): array
    {
        return $field === 'is_active' ? ['boolean'] : [];
    }

    public static function table(Table $table): Table
    {
        $translation = static::featureTranslation();

        return $table
            ->columns([
                ...static::titleColumns(),
                static::activeColumn(),
            ])
            ->filters([
                static::activeFilter(),
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
            'index' => ListOrganizationTypes::route('/'),
            'create' => CreateOrganizationType::route('/create'),
            'view' => ViewOrganizationType::route('/{record}'),
            'edit' => EditOrganizationType::route('/{record}/edit'),
        ];
    }
}
