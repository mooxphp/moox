<?php

declare(strict_types=1);

namespace Moox\Contact\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Validation\ValidationRule;
use Moox\Contact\Models\ContactRelationship;
use Moox\Contact\Resources\ContactRelationship\Pages\CreateContactRelationship;
use Moox\Contact\Resources\ContactRelationship\Pages\EditContactRelationship;
use Moox\Contact\Resources\ContactRelationship\Pages\ListContactRelationships;
use Moox\Contact\Resources\ContactRelationship\Pages\ViewContactRelationship;
use Moox\Support\Archive\HasArchiveForm;
use Moox\Support\Audit\HasAuditForm;
use Moox\Support\External\HasExternalForm;
use Moox\Support\Filament\FeatureFields;
use Moox\Support\Identity\HasIdentityForm;
use Moox\Support\Relation\HasRelationForm;
use Moox\Support\SoftDelete\HasSoftDeleteForm;

class ContactRelationshipResource extends Resource
{
    use HasArchiveForm;
    use HasAuditForm;
    use HasExternalForm;
    use HasIdentityForm;
    use HasRelationForm;
    use HasSoftDeleteForm;

    protected static ?string $model = ContactRelationship::class;

    protected static ?string $recordTitleAttribute = 'id';

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-account-tree';

    public static function getModelLabel(): string
    {
        return __('contact::contact.contact_relationship');
    }

    public static function getPluralModelLabel(): string
    {
        return __('contact::contact.contact_relationships');
    }

    public static function getNavigationLabel(): string
    {
        return __('contact::contact.contact_relationships');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->schema([
                    Grid::make(1)
                        ->schema([
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
                Textarea::make('note')
                    ->label(FeatureFields::label($translation, 'note'))
                    ->rules(static::featureRules('note'))
                    ->columnSpanFull(),
            ]);
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
        return 'contact::fields';
    }

    /**
     * @return list<string|ValidationRule>
     */
    protected static function featureRules(string $field): array
    {
        return match ($field) {
            'note' => ['nullable', 'string'],
            'external_reference', 'external_info', 'external_status' => ['nullable', 'string', 'max:255'],
            'contact_id', 'related_contact_id', 'relationship_type_id' => ['required', 'integer'],
            'data' => [
                'nullable',
                new class implements ValidationRule
                {
                    public function validate(string $attribute, mixed $value, \Closure $fail): void
                    {
                        if ($value === null || $value === '' || is_array($value)) {
                            return;
                        }

                        if (! is_string($value) || ! json_validate($value)) {
                            $fail(__('contact::fields.data_invalid'));
                        }
                    }
                },
            ],
            default => [],
        };
    }

    public static function table(Table $table): Table
    {
        $translation = static::featureTranslation();

        return $table
            ->columns([
                TextColumn::make('contact_id')
                    ->label(FeatureFields::label($translation, 'contact_id'))
                    ->sortable(),
                TextColumn::make('related_contact_id')
                    ->label(FeatureFields::label($translation, 'related_contact_id'))
                    ->sortable(),
                TextColumn::make('relationship_type_id')
                    ->label(FeatureFields::label($translation, 'relationship_type_id'))
                    ->sortable(),
                TextColumn::make('external_reference')
                    ->label(FeatureFields::label($translation, 'external_reference'))
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(FeatureFields::label($translation, 'updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id')
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
            'index' => ListContactRelationships::route('/'),
            'create' => CreateContactRelationship::route('/create'),
            'view' => ViewContactRelationship::route('/{record}'),
            'edit' => EditContactRelationship::route('/{record}/edit'),
        ];
    }
}
