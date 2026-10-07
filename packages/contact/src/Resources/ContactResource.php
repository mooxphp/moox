<?php

declare(strict_types=1);

namespace Moox\Contact\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Validation\ValidationRule;
use Moox\Contact\Models\Contact;
use Moox\Contact\Resources\Contact\Pages\CreateContact;
use Moox\Contact\Resources\Contact\Pages\EditContact;
use Moox\Contact\Resources\Contact\Pages\ListContacts;
use Moox\Contact\Resources\Contact\Pages\ViewContact;
use Moox\Contact\Support\ContactRules;
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
    use HasRelationForm;
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
            static::identitySection(),
            static::activeSection(),
            static::domainSection(),
            static::externalSection(),
            static::relationSection(),
            static::dataSection(),
            static::auditSection(),
            static::archiveSection(),
            static::softDeleteSection(),
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

    /**
     * @return list<TextInput>
     */
    protected static function additionalRelationFields(): array
    {
        $translation = static::featureTranslation();

        return [
            TextInput::make('organization_type_id')
                ->label(FeatureFields::label($translation, 'organization_type_id'))
                ->numeric()
                ->rules(static::featureRules('organization_type_id')),
            TextInput::make('legal_form_id')
                ->label(FeatureFields::label($translation, 'legal_form_id'))
                ->numeric()
                ->rules(static::featureRules('legal_form_id')),
        ];
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
