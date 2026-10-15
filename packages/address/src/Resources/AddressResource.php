<?php

declare(strict_types=1);

namespace Moox\Address\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
use Moox\Address\Models\Address;
use Moox\Address\Resources\Address\Pages\CreateAddress;
use Moox\Address\Resources\Address\Pages\EditAddress;
use Moox\Address\Resources\Address\Pages\ListAddresses;
use Moox\Address\Resources\Address\Pages\ViewAddress;
use Moox\Address\Support\AddressRules;
use Moox\Support\Active\HasActiveForm;
use Moox\Support\Archive\HasArchiveForm;
use Moox\Support\Audit\HasAuditForm;
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
                TextInput::make('street')
                    ->label(FeatureFields::label($translation, 'street'))
                    ->required()
                    ->maxLength(255)
                    ->rules(static::featureRules('street')),
                TextInput::make('house_number')
                    ->label(FeatureFields::label($translation, 'house_number'))
                    ->required()
                    ->maxLength(255)
                    ->rules(static::featureRules('house_number')),
                TextInput::make('address_line_2')
                    ->label(FeatureFields::label($translation, 'address_line_2'))
                    ->required()
                    ->maxLength(255)
                    ->rules(static::featureRules('address_line_2')),
                TextInput::make('po_box')
                    ->label(FeatureFields::label($translation, 'po_box'))
                    ->required()
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
                    ->required()
                    ->maxLength(255)
                    ->rules(static::featureRules('state')),
                TextInput::make('owner_id')
                    ->label(FeatureFields::label($translation, 'owner_id'))
                    ->numeric()
                    ->required()
                    ->rules(static::featureRules('owner_id')),
                TextInput::make('owner_type')
                    ->label(FeatureFields::label($translation, 'owner_type'))
                    ->required()
                    ->maxLength(255)
                    ->rules(static::featureRules('owner_type')),
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
        return 'address::fields';
    }

    /**
     * @return list<string|ValidationRule>
     */
    protected static function featureRules(string $field): array
    {
        return AddressRules::for($field);
    }

    public static function table(Table $table): Table
    {
        $translation = static::featureTranslation();

        return $table
            ->columns([
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
                TextColumn::make('owner_type')
                    ->label(FeatureFields::label($translation, 'owner_type'))
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

    public static function getPages(): array
    {
        return [
            'index' => ListAddresses::route('/'),
            'create' => CreateAddress::route('/create'),
            'view' => ViewAddress::route('/{record}'),
            'edit' => EditAddress::route('/{record}/edit'),
        ];
    }
}
