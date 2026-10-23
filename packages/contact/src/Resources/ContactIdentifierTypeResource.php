<?php

declare(strict_types=1);

namespace Moox\Contact\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Moox\Contact\Models\ContactIdentifierType;
use Moox\Contact\Resources\ContactIdentifierType\Pages\CreateContactIdentifierType;
use Moox\Contact\Resources\ContactIdentifierType\Pages\EditContactIdentifierType;
use Moox\Contact\Resources\ContactIdentifierType\Pages\ListContactIdentifierTypes;
use Moox\Contact\Resources\ContactIdentifierType\Pages\ViewContactIdentifierType;
use Moox\Support\Audit\HasAuditForm;
use Moox\Support\Filament\FeatureFields;
use Moox\Support\Identity\HasIdentityForm;
use Moox\Support\Title\HasTitleForm;

class ContactIdentifierTypeResource extends Resource
{
    use HasAuditForm;
    use HasIdentityForm;
    use HasTitleForm;

    protected static ?string $model = ContactIdentifierType::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-label';

    public static function getModelLabel(): string
    {
        return __('contact::contact.contact_identifier_type');
    }

    public static function getPluralModelLabel(): string
    {
        return __('contact::contact.contact_identifier_types');
    }

    public static function getNavigationLabel(): string
    {
        return __('contact::contact.contact_identifier_types');
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
                            static::mandatorySection(),
                            static::titleSection(),
                        ])
                        ->columnSpan(2),
                    Grid::make(1)
                        ->schema([
                            static::identitySection()->columns(1),
                            static::auditSection()->columns(1),
                        ])
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }

    protected static function mandatorySection(): Section
    {
        $translation = static::featureTranslation();

        return Section::make(FeatureFields::label($translation, 'mandatory'))
            ->schema([
                Toggle::make('mandatory')
                    ->label(FeatureFields::label($translation, 'mandatory'))
                    ->default(false)
                    ->rules(static::featureRules('mandatory')),
            ]);
    }

    protected static function featureTranslation(): string
    {
        return 'contact::fields';
    }

    /**
     * @return list<string|ValidationRule>
     */
    protected static function featureRules(string $field): array
    {
        return $field === 'mandatory' ? ['boolean'] : [];
    }

    public static function table(Table $table): Table
    {
        $translation = static::featureTranslation();

        return $table
            ->columns([
                ...static::titleColumns(),
                IconColumn::make('mandatory')
                    ->label(FeatureFields::label($translation, 'mandatory'))
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('mandatory')
                    ->label(FeatureFields::label($translation, 'mandatory')),
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
            'index' => ListContactIdentifierTypes::route('/'),
            'create' => CreateContactIdentifierType::route('/create'),
            'view' => ViewContactIdentifierType::route('/{record}'),
            'edit' => EditContactIdentifierType::route('/{record}/edit'),
        ];
    }
}
