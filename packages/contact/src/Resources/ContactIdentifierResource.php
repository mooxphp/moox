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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Validation\ValidationRule;
use Moox\Contact\Models\ContactIdentifier;
use Moox\Contact\Resources\ContactIdentifier\Pages\CreateContactIdentifier;
use Moox\Contact\Resources\ContactIdentifier\Pages\EditContactIdentifier;
use Moox\Contact\Resources\ContactIdentifier\Pages\ListContactIdentifiers;
use Moox\Contact\Resources\ContactIdentifier\Pages\ViewContactIdentifier;
use Moox\Support\Filament\FeatureFields;
use Moox\Support\Relation\HasRelationForm;

class ContactIdentifierResource extends Resource
{
    use HasRelationForm;

    protected static ?string $model = ContactIdentifier::class;

    protected static ?string $recordTitleAttribute = 'value';

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-badge';

    public static function getModelLabel(): string
    {
        return __('contact::contact.contact_identifier');
    }

    public static function getPluralModelLabel(): string
    {
        return __('contact::contact.contact_identifiers');
    }

    public static function getNavigationLabel(): string
    {
        return __('contact::contact.contact_identifiers');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->schema([
                    Grid::make(1)
                        ->schema([
                            static::domainSection(),
                        ])
                        ->columnSpan(2),
                    Grid::make(1)
                        ->schema([
                            static::relationSection()->columns(1),
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
                TextInput::make('value')
                    ->label(FeatureFields::label($translation, 'value'))
                    ->helperText(FeatureFields::label($translation, 'value_undefined'))
                    ->rules(static::featureRules('value')),
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
        return match ($field) {
            'value' => ['nullable', 'string'],
            'contact_id', 'contact_identifier_type_id', 'country_id' => ['nullable', 'integer'],
            default => [],
        };
    }

    public static function table(Table $table): Table
    {
        $translation = static::featureTranslation();

        return $table
            ->columns([
                TextColumn::make('value')
                    ->label(FeatureFields::label($translation, 'value'))
                    ->searchable(),
                TextColumn::make('contact_id')
                    ->label(FeatureFields::label($translation, 'contact_id'))
                    ->sortable(),
                TextColumn::make('contact_identifier_type_id')
                    ->label(FeatureFields::label($translation, 'contact_identifier_type_id'))
                    ->sortable(),
                TextColumn::make('country_id')
                    ->label(FeatureFields::label($translation, 'country_id'))
                    ->sortable(),
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
            'index' => ListContactIdentifiers::route('/'),
            'create' => CreateContactIdentifier::route('/create'),
            'view' => ViewContactIdentifier::route('/{record}'),
            'edit' => EditContactIdentifier::route('/{record}/edit'),
        ];
    }
}
