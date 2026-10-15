<?php

declare(strict_types=1);

namespace Moox\Contact\Resources;

use Closure;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Moox\Contact\Models\Contact;
use Moox\Contact\Resources\Contact\Pages\CreateContact;
use Moox\Contact\Resources\Contact\Pages\EditContact;
use Moox\Contact\Resources\Contact\Pages\ListContacts;
use Moox\Contact\Resources\Contact\Pages\ViewContact;
use Moox\Contact\Support\ContactRules;
use Moox\Organization\Models\LegalForm;
use Moox\Support\Active\HasActiveForm;
use Moox\Support\Archive\HasArchiveForm;
use Moox\Support\Audit\HasAuditForm;
use Moox\Support\Data\HasDataForm;
use Moox\Support\External\HasExternalForm;
use Moox\Support\Filament\FeatureFields;
use Moox\Support\Identity\HasIdentityForm;
use Moox\Support\Relation\HasRelationForm;
use Moox\Support\SoftDelete\HasSoftDeleteForm;

class ContactResource extends Resource
{
    use HasActiveForm;
    use HasArchiveForm;
    use HasAuditForm;
    use HasDataForm;
    use HasExternalForm;
    use HasIdentityForm;
    use HasRelationForm {
        relationField as buildRelationField;
    }
    use HasSoftDeleteForm;

    protected static ?string $model = Contact::class;

    protected static ?string $recordTitleAttribute = 'display_name';

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-contact-page';

    public static function getModelLabel(): string
    {
        return __('contact::contact.contact');
    }

    public static function getPluralModelLabel(): string
    {
        return __('contact::contact.contacts');
    }

    public static function getNavigationLabel(): string
    {
        return __('contact::contact.contacts');
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
                TextInput::make('name_1')
                    ->label(FeatureFields::label($translation, 'name_1'))
                    ->required()
                    ->maxLength(255)
                    ->rules(static::featureRules('name_1')),
                TextInput::make('name_2')
                    ->label(FeatureFields::label($translation, 'name_2'))
                    ->maxLength(255)
                    ->rules(static::featureRules('name_2')),
                TextInput::make('name_3')
                    ->label(FeatureFields::label($translation, 'name_3'))
                    ->maxLength(255)
                    ->rules(static::featureRules('name_3')),
                TextInput::make('display_name')
                    ->label(FeatureFields::label($translation, 'display_name'))
                    ->disabled()
                    ->dehydrated(false),
                Textarea::make('note')
                    ->label(FeatureFields::label($translation, 'note'))
                    ->rules(static::featureRules('note'))
                    ->columnSpanFull(),
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
        return ContactRules::for($field);
    }

    /**
     * @param  array<string, mixed>  $configured
     */
    protected static function relationField(string $column, string $relation, array $configured): Select|TextInput
    {
        $field = static::buildRelationField($column, $relation, $configured);

        if ($column === 'country_id' && $field instanceof Select) {
            return $field
                ->live()
                ->afterStateUpdated(function (Set $set): void {
                    $set('legal_form_id', null);
                });
        }

        if ($column !== 'legal_form_id' || ! $field instanceof Select) {
            return $field;
        }

        return $field
            ->relationship(
                $relation,
                'title',
                function (Builder $query, Get $get): Builder {
                    $countryId = $get('country_id');

                    if (blank($countryId)) {
                        return $query->whereRaw('1 = 0');
                    }

                    return $query->where('country_id', $countryId);
                },
            )
            ->disabled(fn (Get $get): bool => blank($get('country_id')))
            ->rules(function (Get $get): array {
                return [
                    ...static::featureRules('legal_form_id'),
                    function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                        if (blank($value)) {
                            return;
                        }

                        $countryId = $get('country_id');
                        $matches = filled($countryId) && LegalForm::query()
                            ->whereKey($value)
                            ->where('country_id', $countryId)
                            ->exists();

                        if (! $matches) {
                            $fail(__('contact::fields.legal_form_country_mismatch'));
                        }
                    },
                ];
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name_1')
                    ->label(__('contact::fields.name_1'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('display_name')
                    ->label(__('contact::fields.display_name'))
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('contact::fields.is_active'))
                    ->boolean(),
                TextColumn::make('external_reference')
                    ->label(__('contact::fields.external_reference'))
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(__('contact::fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('display_name')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('contact::fields.is_active')),
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
            'index' => ListContacts::route('/'),
            'create' => CreateContact::route('/create'),
            'view' => ViewContact::route('/{record}'),
            'edit' => EditContact::route('/{record}/edit'),
        ];
    }
}
