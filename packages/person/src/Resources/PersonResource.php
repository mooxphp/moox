<?php

declare(strict_types=1);

namespace Moox\Person\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
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
use Moox\Person\Models\Person;
use Moox\Person\Resources\Person\Pages\CreatePerson;
use Moox\Person\Resources\Person\Pages\EditPerson;
use Moox\Person\Resources\Person\Pages\ListPersons;
use Moox\Person\Resources\Person\Pages\ViewPerson;
use Moox\Person\Support\PersonRules;
use Moox\Support\Active\HasActiveForm;
use Moox\Support\Archive\HasArchiveForm;
use Moox\Support\Audit\HasAuditForm;
use Moox\Support\External\HasExternalForm;
use Moox\Support\Filament\FeatureFields;
use Moox\Support\Identity\HasIdentityForm;
use Moox\Support\Relation\HasRelationForm;
use Moox\Support\SoftDelete\HasSoftDeleteForm;

class PersonResource extends Resource
{
    use HasActiveForm;
    use HasArchiveForm;
    use HasAuditForm;
    use HasExternalForm;
    use HasIdentityForm;
    use HasRelationForm;
    use HasSoftDeleteForm;

    protected static ?string $model = Person::class;

    protected static ?string $recordTitleAttribute = 'last_name';

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-person';

    public static function getModelLabel(): string
    {
        return __('person::person.person');
    }

    public static function getPluralModelLabel(): string
    {
        return __('person::person.persons');
    }

    public static function getNavigationLabel(): string
    {
        return __('person::person.persons');
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
                TextInput::make('first_name')
                    ->label(FeatureFields::label($translation, 'first_name'))
                    ->required()
                    ->maxLength(255)
                    ->rules(static::featureRules('first_name')),
                TextInput::make('middle_name')
                    ->label(FeatureFields::label($translation, 'middle_name'))
                    ->maxLength(255)
                    ->rules(static::featureRules('middle_name')),
                TextInput::make('last_name')
                    ->label(FeatureFields::label($translation, 'last_name'))
                    ->required()
                    ->maxLength(255)
                    ->rules(static::featureRules('last_name')),
                DatePicker::make('birth_date')
                    ->label(FeatureFields::label($translation, 'birth_date'))
                    ->required()
                    ->rules(static::featureRules('birth_date')),
                Textarea::make('note')
                    ->label(FeatureFields::label($translation, 'note'))
                    ->rules(static::featureRules('note'))
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    protected static function dataSection(): Section
    {
        $translation = static::featureTranslation();

        return Section::make(FeatureFields::label($translation, 'data'))
            ->schema([
                Textarea::make('data')
                    ->label(FeatureFields::label($translation, 'data'))
                    ->rows(8)
                    ->formatStateUsing(function (mixed $state): string {
                        if (is_string($state)) {
                            return $state;
                        }

                        if ($state === null || $state === []) {
                            return '';
                        }

                        $encoded = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                        return is_string($encoded) ? $encoded : '';
                    })
                    ->dehydrateStateUsing(function (mixed $state): ?array {
                        if (! is_string($state) || $state === '' || $state === '{}' || $state === '[]') {
                            return null;
                        }

                        $decoded = json_decode($state, true);

                        return is_array($decoded) ? $decoded : null;
                    })
                    ->dehydrated(fn (mixed $state): bool => is_string($state) && $state !== '' && $state !== '{}' && $state !== '[]')
                    ->rules(static::featureRules('data'))
                    ->columnSpanFull(),
            ]);
    }

    protected static function featureTranslation(): string
    {
        return 'person::fields';
    }

    /**
     * @return list<string|ValidationRule>
     */
    protected static function featureRules(string $field): array
    {
        return PersonRules::for($field);
    }

    public static function table(Table $table): Table
    {
        $translation = static::featureTranslation();

        return $table
            ->columns([
                TextColumn::make('first_name')
                    ->label(FeatureFields::label($translation, 'first_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('last_name')
                    ->label(FeatureFields::label($translation, 'last_name'))
                    ->searchable()
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
            ->defaultSort('last_name')
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
            'index' => ListPersons::route('/'),
            'create' => CreatePerson::route('/create'),
            'view' => ViewPerson::route('/{record}'),
            'edit' => EditPerson::route('/{record}/edit'),
        ];
    }
}
