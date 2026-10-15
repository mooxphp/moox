<?php

declare(strict_types=1);

namespace Moox\Contact\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Validation\ValidationRule;
use Moox\Contact\Models\ContactIdentifierRule;
use Moox\Contact\Resources\ContactIdentifierRule\Pages\CreateContactIdentifierRule;
use Moox\Contact\Resources\ContactIdentifierRule\Pages\EditContactIdentifierRule;
use Moox\Contact\Resources\ContactIdentifierRule\Pages\ListContactIdentifierRules;
use Moox\Contact\Resources\ContactIdentifierRule\Pages\ViewContactIdentifierRule;
use Moox\Support\Active\HasActiveForm;
use Moox\Support\Audit\HasAuditForm;
use Moox\Support\Filament\FeatureFields;
use Moox\Support\Identity\HasIdentityForm;
use Moox\Support\Relation\HasRelationForm;

class ContactIdentifierRuleResource extends Resource
{
    use HasActiveForm;
    use HasAuditForm;
    use HasIdentityForm;
    use HasRelationForm;

    protected static ?string $model = ContactIdentifierRule::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-rule';

    public static function getModelLabel(): string
    {
        return __('contact::contact.contact_identifier_rule');
    }

    public static function getPluralModelLabel(): string
    {
        return __('contact::contact.contact_identifier_rules');
    }

    public static function getNavigationLabel(): string
    {
        return __('contact::contact.contact_identifier_rules');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->schema([
                    Grid::make(1)
                        ->schema([
                            static::activeSection(),
                        ])
                        ->columnSpan(2),
                    Grid::make(1)
                        ->schema([
                            static::relationSection()->columns(1),
                            static::identitySection()->columns(1),
                            static::auditSection()->columns(1),
                        ])
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }

    protected static function relationSection(): Section
    {
        $translation = static::featureTranslation();

        return Section::make(FeatureFields::label($translation, 'relation'))
            ->schema([
                TextInput::make('contact_identifier_type_id')
                    ->label(FeatureFields::label($translation, 'contact_identifier_type_id'))
                    ->numeric()
                    ->rules(static::featureRules('contact_identifier_type_id')),
                ...static::configuredRelationFields(),
            ])
            ->columns(2);
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
        return match ($field) {
            'is_active' => ['boolean'],
            'contact_identifier_type_id', 'country_id', 'organization_type_id', 'legal_form_id' => ['nullable', 'integer'],
            default => [],
        };
    }

    public static function table(Table $table): Table
    {
        $translation = static::featureTranslation();

        return $table
            ->columns([
                TextColumn::make('contact_identifier_type_id')
                    ->label(FeatureFields::label($translation, 'contact_identifier_type_id'))
                    ->sortable(),
                TextColumn::make('country_id')
                    ->label(FeatureFields::label($translation, 'country_id'))
                    ->sortable(),
                TextColumn::make('organization_type_id')
                    ->label(FeatureFields::label($translation, 'organization_type_id'))
                    ->sortable(),
                TextColumn::make('legal_form_id')
                    ->label(FeatureFields::label($translation, 'legal_form_id'))
                    ->sortable(),
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
            'index' => ListContactIdentifierRules::route('/'),
            'create' => CreateContactIdentifierRule::route('/create'),
            'view' => ViewContactIdentifierRule::route('/{record}'),
            'edit' => EditContactIdentifierRule::route('/{record}/edit'),
        ];
    }
}
