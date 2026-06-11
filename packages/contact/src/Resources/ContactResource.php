<?php

declare(strict_types=1);

namespace Moox\Contact\Resources;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Moox\Contact\Models\Contact;
use Moox\Contact\Resources\Contact\Pages\CreateContact;
use Moox\Contact\Resources\Contact\Pages\EditContact;
use Moox\Contact\Resources\Contact\Pages\ListContacts;
use Moox\Contact\Resources\Contact\Pages\ViewContact;
use Moox\Contact\Support\ContactRules;
use Moox\Core\Entities\Items\Record\BaseRecordResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Core\Traits\Taxonomy\HasResourceTaxonomy;

class ContactResource extends BaseRecordResource
{
    use HasResourceTabs;
    use HasResourceTaxonomy;

    protected static ?string $model = Contact::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-contact-page';

    protected static function getEntityType(): string
    {
        return 'contact';
    }

    public static function getModelLabel(): string
    {
        return config('contact.resources.contact.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('contact.resources.contact.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('contact.resources.contact.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('contact.resources.contact.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('contact.navigation_group');
    }

    public static function form(Schema $form): Schema
    {
        $taxonomyFields = static::getTaxonomyFields();
        $statusOptions = static::configOptions('contact.statuses');
        $typeOptions = static::configOptions('contact.contact_types');

        $schema = [
            Grid::make()
                ->schema([
                    Section::make(__('contact::fields.identity'))
                        ->schema([
                            Select::make('status')
                                ->label(__('contact::fields.status'))
                                ->options($statusOptions)
                                ->required()
                                ->rules(ContactRules::for('status'))
                                ->default('draft'),
                            TextInput::make('first_name')
                                ->label(__('contact::fields.first_name'))
                                ->rules(ContactRules::for('first_name'))
                                ->maxLength(80),
                            TextInput::make('last_name')
                                ->label(__('contact::fields.last_name'))
                                ->rules(ContactRules::for('last_name'))
                                ->maxLength(80),
                            TextInput::make('display_name')
                                ->label(__('contact::fields.display_name'))
                                ->rules(ContactRules::for('display_name'))
                                ->maxLength(120),
                            Select::make('contact_type')
                                ->label(__('contact::fields.contact_type'))
                                ->options($typeOptions)
                                ->required()
                                ->rules(ContactRules::for('contact_type'))
                                ->default('external'),
                            Select::make('gender')
                                ->label(__('contact::fields.gender'))
                                ->options(static::configOptions('contact.genders'))
                                ->rules(ContactRules::for('gender')),
                            TextInput::make('external_reference')
                                ->label(__('contact::fields.external_reference'))
                                ->rules(ContactRules::for('external_reference'))
                                ->maxLength(100),
                            Textarea::make('note')
                                ->label(__('contact::fields.note'))
                                ->rules(ContactRules::for('note'))
                                ->columnSpanFull(),
                        ])
                        ->columnSpan(2),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    static::getFormActions(),
                                ]),
                            Section::make(__('contact::fields.contact'))
                                ->schema([
                                    TextInput::make('phone')
                                        ->label(__('contact::fields.phone'))
                                        ->tel()
                                        ->rules(ContactRules::for('phone'))
                                        ->maxLength(30),
                                    TextInput::make('mobile')
                                        ->label(__('contact::fields.mobile'))
                                        ->rules(ContactRules::for('mobile'))
                                        ->maxLength(30),
                                    TextInput::make('email')
                                        ->label(__('contact::fields.email'))
                                        ->email()
                                        ->rules(ContactRules::for('email'))
                                        ->maxLength(120),
                                    Toggle::make('is_system_user')
                                        ->label(__('contact::fields.is_system_user')),
                                ]),
                            Section::make(__('contact::fields.settings'))
                                ->schema([
                                    Toggle::make('is_active')
                                        ->label(__('contact::fields.is_active'))
                                        ->default(true),
                                ]),
                            Section::make('')
                                ->schema($taxonomyFields),
                            Section::make('')
                                ->schema([
                                    Section::make('')
                                        ->schema([
                                            ...static::getStandardTimestampFields(),
                                        ]),
                                ])
                                ->hidden(fn (?Contact $record) => $record === null),
                        ])
                        ->columnSpan(1)
                        ->columns(1),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ];

        return $form->components($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')
                    ->label(__('contact::fields.display_name'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('contact_type')
                    ->label(__('contact::fields.contact_type'))
                    ->badge()
                    ->color(
                        fn (?string $state): string => match ($state) {
                            'external' => 'success',
                            'internal' => 'gray',
                            default => 'gray',
                        }
                    )
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('contact::fields.status'))
                    ->badge()
                    ->color(
                        fn (string $state): string => match ($state) {
                            'draft' => 'info',
                            'active' => 'success',
                            'inactive' => 'warning',
                            'approved' => 'success',
                            'archived' => 'danger',
                            default => 'gray',
                        }
                    )
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('contact::fields.email'))
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label(__('contact::fields.is_active'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ...static::getTaxonomyColumns(),
            ])
            ->defaultSort('display_name')
            ->recordActions([...static::getTableActions()])
            ->toolbarActions([...static::getBulkActions()])
            ->filters([
                ...static::getContactTableFilters(),
                ...static::getTaxonomyFilters(),
            ])
            ->deferFilters(false)
            ->persistFiltersInSession();
    }

    /**
     * @return array<SelectFilter|TernaryFilter>
     */
    protected static function getContactTableFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->label(__('contact::fields.status'))
                ->options(static::configOptions('contact.statuses')),
            SelectFilter::make('contact_type')
                ->label(__('contact::fields.contact_type'))
                ->options(static::configOptions('contact.contact_types')),
            TernaryFilter::make('is_active')
                ->label(__('contact::fields.is_active')),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function configOptions(string $configKey): array
    {
        /** @var mixed $configured */
        $configured = config($configKey, []);
        $values = is_array($configured) ? $configured : [];

        $options = [];
        foreach ($values as $value) {
            if (is_string($value) && $value !== '') {
                $options[$value] = $value;
            }
        }

        return $options;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContacts::route('/'),
            'create' => CreateContact::route('/create'),
            'edit' => EditContact::route('/{record}/edit'),
            'view' => ViewContact::route('/{record}'),
        ];
    }
}
