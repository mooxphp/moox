<?php

declare(strict_types=1);

namespace Moox\Organization\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Validation\ValidationRule;
use Moox\Organization\Models\LegalForm;
use Moox\Organization\Resources\LegalForm\Pages\CreateLegalForm;
use Moox\Organization\Resources\LegalForm\Pages\EditLegalForm;
use Moox\Organization\Resources\LegalForm\Pages\ListLegalForms;
use Moox\Organization\Resources\LegalForm\Pages\ViewLegalForm;
use Moox\Support\Active\HasActiveForm;
use Moox\Support\Audit\HasAuditForm;
use Moox\Support\Filament\FeatureFields;
use Moox\Support\Identity\HasIdentityForm;
use Moox\Support\Relation\HasRelationForm;
use Moox\Support\SoftDelete\HasSoftDeleteForm;
use Moox\Support\Title\HasTitleSlugForm;

class LegalFormResource extends Resource
{
    use HasActiveForm;
    use HasAuditForm;
    use HasIdentityForm;
    use HasRelationForm;
    use HasSoftDeleteForm;
    use HasTitleSlugForm;

    protected static ?string $model = LegalForm::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-account-balance';

    public static function getModelLabel(): string
    {
        return __('organization::organization.legal_form');
    }

    public static function getPluralModelLabel(): string
    {
        return __('organization::organization.legal_forms');
    }

    public static function getNavigationLabel(): string
    {
        return __('organization::organization.legal_forms');
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
                            static::relationSection()->columns(1),
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

    protected static function titleSection(): Section
    {
        $translation = static::featureTranslation();

        return Section::make(FeatureFields::label($translation, 'titles'))
            ->schema(static::titleSlugFields())
            ->columns(2);
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
                TextColumn::make('title')
                    ->label(FeatureFields::label($translation, 'title'))
                    ->searchable(),
                TextColumn::make('slug')
                    ->label(FeatureFields::label($translation, 'slug'))
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label(FeatureFields::label($translation, 'is_active'))
                    ->boolean(),
            ])
            ->filters([
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
            'index' => ListLegalForms::route('/'),
            'create' => CreateLegalForm::route('/create'),
            'view' => ViewLegalForm::route('/{record}'),
            'edit' => EditLegalForm::route('/{record}/edit'),
        ];
    }
}
