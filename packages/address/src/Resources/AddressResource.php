<?php

declare(strict_types=1);

namespace Moox\Address\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\MorphToSelect\Type;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Moox\Address\Models\Address;
use Moox\Address\Resources\Address\Pages\CreateAddress;
use Moox\Address\Resources\Address\Pages\EditAddress;
use Moox\Address\Resources\Address\Pages\ListAddresses;
use Moox\Address\Resources\Address\Pages\ViewAddress;
use Moox\Support\Active\HasActiveForm;
use Moox\Support\Archive\HasArchiveForm;
use Moox\Support\Audit\HasAuditForm;
use Moox\Support\Data\HasDataForm;
use Moox\Support\External\HasExternalForm;
use Moox\Support\Filament\FeatureFields;
use Moox\Support\Identity\HasIdentityForm;
use Moox\Support\Relation\HasRelationForm;
use Moox\Support\SoftDelete\HasSoftDeleteForm;

class AddressResource extends Resource
{
    use HasActiveForm;
    use HasArchiveForm;
    use HasAuditForm;
    use HasDataForm;
    use HasExternalForm;
    use HasIdentityForm;
    use HasRelationForm;
    use HasSoftDeleteForm;

    protected static ?string $model = Address::class;

    protected static ?string $recordTitleAttribute = 'street';

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-place';

    public static function getModelLabel(): string
    {
        return __('address::address.address');
    }

    public static function getPluralModelLabel(): string
    {
        return __('address::address.addresses');
    }

    public static function getNavigationLabel(): string
    {
        return __('address::address.addresses');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('owner');
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
                TextInput::make('name')
                    ->label(FeatureFields::label($translation, 'name'))
                    ->maxLength(255)
                    ->rules(static::featureRules('name')),
                TextInput::make('street')
                    ->label(FeatureFields::label($translation, 'street'))
                    ->required()
                    ->maxLength(255)
                    ->rules(static::featureRules('street')),
                TextInput::make('house_number')
                    ->label(FeatureFields::label($translation, 'house_number'))
                    ->maxLength(255)
                    ->rules(static::featureRules('house_number')),
                TextInput::make('address_line_2')
                    ->label(FeatureFields::label($translation, 'address_line_2'))
                    ->maxLength(255)
                    ->rules(static::featureRules('address_line_2')),
                TextInput::make('po_box')
                    ->label(FeatureFields::label($translation, 'po_box'))
                    ->maxLength(255)
                    ->rules(static::featureRules('po_box')),
                TextInput::make('postal_code')
                    ->label(FeatureFields::label($translation, 'postal_code'))
                    ->required()
                    ->maxLength(255)
                    ->rules(static::featureRules('postal_code')),
                TextInput::make('city')
                    ->label(FeatureFields::label($translation, 'city'))
                    ->required()
                    ->maxLength(255)
                    ->rules(static::featureRules('city')),
                TextInput::make('state')
                    ->label(FeatureFields::label($translation, 'state'))
                    ->maxLength(255)
                    ->rules(static::featureRules('state')),
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
        $ownerTypes = config('address.owner_types', []);

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
        return 'address::fields';
    }

    /**
     * @return list<string|ValidationRule>
     */
    protected static function featureRules(string $field): array
    {
        return match ($field) {
            'is_active' => ['boolean'],
            'street', 'postal_code', 'city' => ['required', 'string', 'max:255'],
            'name', 'house_number', 'address_line_2', 'po_box', 'state' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'external_reference', 'external_info', 'external_status' => ['nullable', 'string', 'max:255'],
            'owner_id' => ['required', 'integer'],
            'owner_type' => ['required', 'string', 'max:255'],
            'country_id' => ['nullable', 'integer'],
            default => [],
        };
    }

    public static function table(Table $table): Table
    {
        $translation = static::featureTranslation();

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(FeatureFields::label($translation, 'name'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('street')
                    ->label(FeatureFields::label($translation, 'street'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('house_number')
                    ->label(FeatureFields::label($translation, 'house_number'))
                    ->toggleable(),
                TextColumn::make('postal_code')
                    ->label(FeatureFields::label($translation, 'postal_code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('city')
                    ->label(FeatureFields::label($translation, 'city'))
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(FeatureFields::label($translation, 'is_active'))
                    ->boolean(),
                TextColumn::make('owner_label')
                    ->label(FeatureFields::label($translation, 'owner'))
                    ->state(function (Model $record): string {
                        if (! $record instanceof Address) {
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
            ->defaultSort('city')
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

    public static function labelForOwner(Model $owner): string
    {
        /** @var array<class-string, array{title_attribute?: string, title_attributes?: list<string>}> $ownerTypes */
        $ownerTypes = config('address.owner_types', []);
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
            'index' => ListAddresses::route('/'),
            'create' => CreateAddress::route('/create'),
            'view' => ViewAddress::route('/{record}'),
            'edit' => EditAddress::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): string
    {
        return config('address.navigationgroup.label');
    }
}
