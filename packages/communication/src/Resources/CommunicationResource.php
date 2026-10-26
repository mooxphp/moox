<?php

declare(strict_types=1);

namespace Moox\Communication\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\MorphToSelect\Type;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Moox\Communication\Models\Communication;
use Moox\Communication\Resources\Communication\Pages\CreateCommunication;
use Moox\Communication\Resources\Communication\Pages\EditCommunication;
use Moox\Communication\Resources\Communication\Pages\ListCommunications;
use Moox\Communication\Resources\Communication\Pages\ViewCommunication;
use Moox\Support\Active\HasActiveForm;
use Moox\Support\Audit\HasAuditForm;
use Moox\Support\Data\HasDataForm;
use Moox\Support\External\HasExternalForm;
use Moox\Support\Filament\FeatureFields;
use Moox\Support\Identity\HasIdentityForm;
use Moox\Support\Relation\HasRelationForm;
use Moox\Support\SoftDelete\HasSoftDeleteForm;

class CommunicationResource extends Resource
{
    use HasActiveForm;
    use HasAuditForm;
    use HasDataForm;
    use HasExternalForm;
    use HasIdentityForm;
    use HasRelationForm;
    use HasSoftDeleteForm;

    protected static ?string $model = Communication::class;

    protected static ?string $recordTitleAttribute = 'value';

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-mail';

    public static function getModelLabel(): string
    {
        return __('communication::communication.communication');
    }

    public static function getPluralModelLabel(): string
    {
        return __('communication::communication.communications');
    }

    public static function getNavigationLabel(): string
    {
        return __('communication::communication.communications');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['owner', 'communicationType.translations']);
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
                TextInput::make('value')
                    ->label(FeatureFields::label($translation, 'value'))
                    ->required()
                    ->maxLength(255)
                    ->rules(static::featureRules('value')),
                TextInput::make('label')
                    ->label(FeatureFields::label($translation, 'label'))
                    ->required()
                    ->maxLength(255)
                    ->rules(static::featureRules('label')),
                Toggle::make('is_primary')
                    ->label(FeatureFields::label($translation, 'is_primary'))
                    ->rules(static::featureRules('is_primary')),
                Toggle::make('is_verified')
                    ->label(FeatureFields::label($translation, 'is_verified'))
                    ->rules(static::featureRules('is_verified')),
                DateTimePicker::make('verified_at')
                    ->label(FeatureFields::label($translation, 'verified_at'))
                    ->rules(static::featureRules('verified_at')),
                static::ownerSelect($translation),
                Textarea::make('note')
                    ->label(FeatureFields::label($translation, 'note'))
                    ->rules(static::featureRules('note'))
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    protected static function ownerSelect(string $translation): MorphToSelect
    {
        /** @var array<class-string, array{label?: string, title_attribute?: string}> $ownerTypes */
        $ownerTypes = config('communication.owner_types', []);

        return MorphToSelect::make('owner')
            ->label(FeatureFields::label($translation, 'owner'))
            ->types(
                collect($ownerTypes)
                    ->filter(fn (mixed $definition, mixed $class): bool => is_string($class) && is_a($class, Model::class, true) && is_array($definition))
                    ->map(function (array $definition, string $class): Type {
                        $titleAttribute = is_string($definition['title_attribute'] ?? null)
                            ? $definition['title_attribute']
                            : null;

                        if ($titleAttribute === null) {
                            $titleAttributes = $definition['title_attributes'] ?? null;
                            $titleAttribute = is_array($titleAttributes) && is_string($titleAttributes[0] ?? null)
                                ? $titleAttributes[0]
                                : 'id';
                        }

                        $label = is_string($definition['label'] ?? null)
                            ? $definition['label']
                            : class_basename($class);

                        return Type::make($class)
                            ->label($label)
                            ->titleAttribute($titleAttribute)
                            ->getOptionLabelFromRecordUsing(
                                fn (Model $record): string => static::labelForOwner($record)
                            );
                    })
                    ->values()
                    ->all()
            )
            ->searchable()
            ->preload()
            ->required();
    }

    protected static function featureTranslation(): string
    {
        return 'communication::fields';
    }

    /**
     * @return list<string|ValidationRule>
     */
    protected static function featureRules(string $field): array
    {
        return match ($field) {
            'is_active', 'is_primary', 'is_verified' => ['boolean'],
            'value', 'label' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'verified_at' => ['nullable', 'date'],
            'external_reference', 'external_info', 'external_status' => ['nullable', 'string', 'max:255'],
            'owner_id', 'communication_type_id' => ['required', 'integer'],
            'owner_type' => ['required', 'string', 'max:255'],
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
                    ->searchable()
                    ->sortable(),
                TextColumn::make('label')
                    ->label(FeatureFields::label($translation, 'label'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('communicationType.title')
                    ->label(FeatureFields::label($translation, 'communication_type_id'))
                    ->toggleable(),
                IconColumn::make('is_primary')
                    ->label(FeatureFields::label($translation, 'is_primary'))
                    ->boolean(),
                IconColumn::make('is_verified')
                    ->label(FeatureFields::label($translation, 'is_verified'))
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label(FeatureFields::label($translation, 'is_active'))
                    ->boolean(),
                TextColumn::make('owner_label')
                    ->label(FeatureFields::label($translation, 'owner'))
                    ->state(function (Model $record): string {
                        if (! $record instanceof Communication) {
                            return '';
                        }

                        $owner = $record->owner;

                        if (! $owner instanceof Model) {
                            return (string) ($record->owner_id ?? '');
                        }

                        $label = static::labelForOwner($owner);

                        return $label !== '' ? $label : (string) $owner->getKey();
                    })
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(FeatureFields::label($translation, 'updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('value')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(FeatureFields::label($translation, 'is_active')),
                TernaryFilter::make('is_primary')
                    ->label(FeatureFields::label($translation, 'is_primary')),
                TernaryFilter::make('is_verified')
                    ->label(FeatureFields::label($translation, 'is_verified')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function labelForOwner(Model $owner): string
    {
        /** @var array<class-string, array{title_attribute?: string, title_attributes?: list<string>}> $ownerTypes */
        $ownerTypes = config('communication.owner_types', []);
        $definition = is_array($ownerTypes[$owner::class] ?? null) ? $ownerTypes[$owner::class] : [];
        $titleAttributes = $definition['title_attributes'] ?? null;

        if (is_array($titleAttributes)) {
            $parts = [];

            foreach ($titleAttributes as $attribute) {
                if (! is_string($attribute) || $attribute === '') {
                    continue;
                }

                $value = $owner->getAttribute($attribute);

                if (is_string($value) && $value !== '') {
                    $parts[] = $value;
                }
            }

            if ($parts !== []) {
                return implode(' ', $parts);
            }
        }

        $titleAttribute = $definition['title_attribute'] ?? null;

        if (is_string($titleAttribute) && $titleAttribute !== '') {
            $value = $owner->getAttribute($titleAttribute);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return (string) $owner->getKey();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommunications::route('/'),
            'create' => CreateCommunication::route('/create'),
            'view' => ViewCommunication::route('/{record}'),
            'edit' => EditCommunication::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): string
    {
        return config('communication.navigationgroup.label');
    }
}
