<?php

declare(strict_types=1);

namespace Moox\Contact\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Moox\Contact\Models\Contact;
use Moox\Contact\Resources\Contact\Pages\CreateContact;
use Moox\Contact\Resources\Contact\Pages\EditContact;
use Moox\Contact\Resources\Contact\Pages\ListContacts;
use Moox\Contact\Resources\Contact\Pages\ViewContact;
use Moox\Contact\Support\ContactRules;

class ContactResource extends Resource
{
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
            Section::make(__('contact::fields.identity'))
                ->schema([
                    Toggle::make('is_active')
                        ->label(__('contact::fields.is_active'))
                        ->default(true)
                        ->rules(ContactRules::for('is_active')),
                    TextInput::make('name_1')
                        ->label(__('contact::fields.name_1'))
                        ->required()
                        ->maxLength(255)
                        ->rules(ContactRules::for('name_1')),
                    TextInput::make('name_2')
                        ->label(__('contact::fields.name_2'))
                        ->maxLength(255)
                        ->rules(ContactRules::for('name_2')),
                    TextInput::make('name_3')
                        ->label(__('contact::fields.name_3'))
                        ->maxLength(255)
                        ->rules(ContactRules::for('name_3')),
                    TextInput::make('display_name')
                        ->label(__('contact::fields.display_name'))
                        ->disabled()
                        ->dehydrated(false),
                    Textarea::make('note')
                        ->label(__('contact::fields.note'))
                        ->rules(ContactRules::for('note'))
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make(__('contact::fields.external'))
                ->schema([
                    TextInput::make('external_reference')
                        ->label(__('contact::fields.external_reference'))
                        ->maxLength(255)
                        ->rules(ContactRules::for('external_reference')),
                    TextInput::make('external_info')
                        ->label(__('contact::fields.external_info'))
                        ->maxLength(255)
                        ->rules(ContactRules::for('external_info')),
                    Select::make('external_status')
                        ->label(__('contact::fields.external_status'))
                        ->options([])
                        ->nullable()
                        ->helperText(__('contact::fields.external_status_undefined'))
                        ->rules(ContactRules::for('external_status')),
                ])
                ->columns(2),
            Section::make(__('contact::fields.relations'))
                ->schema([
                    TextInput::make('language_id')
                        ->label(__('contact::fields.language_id'))
                        ->numeric()
                        ->rules(ContactRules::for('language_id')),
                    TextInput::make('country_id')
                        ->label(__('contact::fields.country_id'))
                        ->numeric()
                        ->rules(ContactRules::for('country_id')),
                    TextInput::make('organization_type_id')
                        ->label(__('contact::fields.organization_type_id'))
                        ->numeric()
                        ->rules(ContactRules::for('organization_type_id')),
                    TextInput::make('legal_form_id')
                        ->label(__('contact::fields.legal_form_id'))
                        ->numeric()
                        ->rules(ContactRules::for('legal_form_id')),
                ])
                ->columns(2),
            Section::make(__('contact::fields.data_json'))
                ->schema([
                    Textarea::make('data_json')
                        ->label(__('contact::fields.data_json'))
                        ->default('{}')
                        ->rows(8)
                        ->formatStateUsing(function (mixed $state): string {
                            if (is_string($state)) {
                                return $state;
                            }

                            if ($state === null || $state === []) {
                                return '{}';
                            }

                            $encoded = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                            return is_string($encoded) ? $encoded : '{}';
                        })
                        ->dehydrateStateUsing(function (mixed $state): ?array {
                            if (! is_string($state) || $state === '' || $state === '{}' || $state === '[]') {
                                return null;
                            }

                            $decoded = json_decode($state, true);

                            return is_array($decoded) ? $decoded : null;
                        })
                        ->dehydrated(fn (mixed $state): bool => is_string($state) && $state !== '' && $state !== '{}' && $state !== '[]')
                        ->rules(ContactRules::for('data_json'))
                        ->columnSpanFull(),
                ]),
            Section::make(__('contact::fields.audit'))
                ->schema([
                    TextInput::make('id')->disabled()->dehydrated(false),
                    TextInput::make('ulid')->disabled()->dehydrated(false),
                    TextInput::make('uuid')->disabled()->dehydrated(false),
                    TextInput::make('created_at')->disabled()->dehydrated(false),
                    TextInput::make('created_by_id')->disabled()->dehydrated(false),
                    TextInput::make('created_by_type')->disabled()->dehydrated(false),
                    TextInput::make('updated_at')->disabled()->dehydrated(false),
                    TextInput::make('updated_by_id')->disabled()->dehydrated(false),
                    TextInput::make('updated_by_type')->disabled()->dehydrated(false),
                    TextInput::make('archived_at')->disabled()->dehydrated(false),
                    TextInput::make('deleted_at')->disabled()->dehydrated(false),
                    TextInput::make('restored_at')->disabled()->dehydrated(false),
                ])
                ->columns(2)
                ->hidden(fn (?Contact $record): bool => $record === null),
        ]);
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
