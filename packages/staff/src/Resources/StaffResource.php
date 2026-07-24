<?php

declare(strict_types=1);

namespace Moox\Staff\Resources;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Moox\Core\Entities\Items\Record\BaseRecordResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Core\Traits\Taxonomy\HasResourceTaxonomy;
use Moox\Staff\Models\Staff;
use Moox\Staff\Resources\Staff\Pages\CreateStaff;
use Moox\Staff\Resources\Staff\Pages\EditStaff;
use Moox\Staff\Resources\Staff\Pages\ListStaff;
use Moox\Staff\Resources\Staff\Pages\ViewStaff;
use Moox\Staff\Support\StaffRules;

class StaffResource extends BaseRecordResource
{
    use HasResourceTabs;
    use HasResourceTaxonomy;

    protected static ?string $model = Staff::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-badge';

    protected static function getEntityType(): string
    {
        return 'staff';
    }

    public static function getModelLabel(): string
    {
        return config('staff.resources.staff.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('staff.resources.staff.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('staff.resources.staff.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('staff.resources.staff.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('staff.navigation_group');
    }

    public static function form(Schema $form): Schema
    {
        $taxonomyFields = static::getTaxonomyFields();
        $statusOptions = static::configOptions('staff.statuses');

        $schema = [
            Grid::make()
                ->schema([
                    Section::make(__('staff::fields.identity'))
                        ->schema([
                            SpatieMediaLibraryFileUpload::make('avatar')
                                ->label(__('staff::fields.avatar'))
                                ->collection('avatar')
                                ->image()
                                ->avatar()
                                ->imageEditor()
                                ->columnSpanFull(),
                            Select::make('status')
                                ->label(__('staff::fields.status'))
                                ->options($statusOptions)
                                ->required()
                                ->rules(StaffRules::for('status'))
                                ->default('draft'),
                            TextInput::make('display_name')
                                ->label(__('staff::fields.display_name'))
                                ->rules(StaffRules::for('display_name'))
                                ->maxLength(160),
                            TextInput::make('first_name')
                                ->label(__('staff::fields.first_name'))
                                ->rules(StaffRules::for('first_name'))
                                ->maxLength(80),
                            TextInput::make('last_name')
                                ->label(__('staff::fields.last_name'))
                                ->rules(StaffRules::for('last_name'))
                                ->maxLength(80),
                            TextInput::make('short_code')
                                ->label(__('staff::fields.short_code'))
                                ->rules(StaffRules::for('short_code'))
                                ->maxLength(20),
                            TextInput::make('legacy_id')
                                ->label(__('staff::fields.legacy_id'))
                                ->numeric()
                                ->rules(StaffRules::for('legacy_id')),
                            TextInput::make('external_reference')
                                ->label(__('staff::fields.external_reference'))
                                ->rules(StaffRules::for('external_reference'))
                                ->maxLength(100),
                        ])
                        ->columnSpan(2),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    static::getFormActions(),
                                ]),
                            Section::make(__('staff::fields.settings'))
                                ->schema([
                                    Toggle::make('is_internal')
                                        ->label(__('staff::fields.is_internal'))
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
                                ->hidden(fn (?Staff $record) => $record === null),
                        ])
                        ->columnSpan(1)
                        ->columns(1),
                ])
                ->columns(3)
                ->columnSpanFull(),
            Section::make(__('staff::fields.contact'))
                ->schema([
                    TextInput::make('email')
                        ->label(__('staff::fields.email'))
                        ->email()
                        ->rules(StaffRules::for('email'))
                        ->maxLength(120),
                    TextInput::make('phone')
                        ->label(__('staff::fields.phone'))
                        ->tel()
                        ->rules(StaffRules::for('phone'))
                        ->maxLength(30),
                    Select::make('language_id')
                        ->label(__('staff::fields.language_id'))
                        ->relationship('language', 'common_name')
                        ->searchable()
                        ->preload()
                        ->rules(StaffRules::for('language_id')),
                    Select::make('contact_id')
                        ->label(__('staff::fields.linked_contact'))
                        ->relationship('contact', 'display_name')
                        ->searchable()
                        ->preload()
                        ->rules(StaffRules::for('contact_id')),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make(__('staff::fields.work'))
                ->schema([
                    TextInput::make('job_title')
                        ->label(__('staff::fields.job_title'))
                        ->rules(StaffRules::for('job_title'))
                        ->maxLength(100),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make(__('staff::fields.data'))
                ->schema([
                    Textarea::make('data')
                        ->label(__('staff::fields.data'))
                        ->columnSpanFull()
                        ->cols(100)
                        ->rows(10)
                        ->formatStateUsing(function ($state) {
                            return json_encode((array) $state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        }),
                ])
                ->columnSpanFull(),
        ];

        return $form->components($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label(__('staff::fields.avatar'))
                    ->circular()
                    ->getStateUsing(fn (Staff $record): ?string => $record->getFirstMediaUrl('avatar') ?: null)
                    ->defaultImageUrl(fn (Staff $record): string => 'https://ui-avatars.com/api/?name='.urlencode($record->displayLabel())),
                TextColumn::make('display_name')
                    ->label(__('staff::fields.display_name'))
                    ->searchable(['display_name', 'first_name', 'last_name', 'short_code'])
                    ->sortable(),
                TextColumn::make('short_code')
                    ->label(__('staff::fields.short_code'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('staff::fields.status'))
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
                    ->label(__('staff::fields.email'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('legacy_id')
                    ->label(__('staff::fields.legacy_id'))
                    ->sortable()
                    ->searchable(),
                IconColumn::make('is_internal')
                    ->label(__('staff::fields.is_internal'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                ...static::getStaffTableFilters(),
                ...static::getTaxonomyFilters(),
            ])
            ->deferFilters(false)
            ->persistFiltersInSession();
    }

    /**
     * @return array<SelectFilter|TernaryFilter>
     */
    protected static function getStaffTableFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->label(__('staff::fields.status'))
                ->options(static::configOptions('staff.statuses')),
            TernaryFilter::make('is_internal')
                ->label(__('staff::fields.is_internal')),
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
            'index' => ListStaff::route('/'),
            'create' => CreateStaff::route('/create'),
            'edit' => EditStaff::route('/{record}/edit'),
            'view' => ViewStaff::route('/{record}'),
        ];
    }
}
