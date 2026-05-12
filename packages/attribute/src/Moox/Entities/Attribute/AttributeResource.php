<?php

namespace Moox\Attribute\Moox\Entities\Attribute;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Moox\Core\Entities\Items\Draft\BaseDraftResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Core\Traits\Taxonomy\HasResourceTaxonomy;
use Moox\Localization\Filament\Tables\Columns\TranslationColumn;

class AttributeResource extends BaseDraftResource
{
    use HasResourceTabs;
    use HasResourceTaxonomy;

    protected static ?string $model = Attribute::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-description';

    public static function getModelLabel(): string
    {
        return config('attribute.resources.attribute.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('attribute.resources.attribute.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('attribute.resources.attribute.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('attribute.resources.attribute.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('attribute.navigation_group');
    }

    public static function form(Schema $form): Schema
    {
        $taxonomyFields = static::getTaxonomyFields();

        $schema = [
            Grid::make()
                ->schema([
                    Section::make()
                        ->schema([
                            TextInput::make('type')
                                ->label(__('core::core.type'))
                                ->required(),
                            TextInput::make('value')
                                ->label(__('core::core.value')),
                            Grid::make(2)
                                ->schema([
                                    static::getFooterActions()->columnSpan(1),
                                ]),
                        ])->columnSpan(2),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    static::getFormActions(),
                                ]),
                            Section::make('')
                                ->schema([
                                    static::getTypeSelect(),
                                    static::getTranslationStatusSelect(),
                                    static::getPublishDateField(),
                                    static::getUnpublishDateField(),
                                ]),
                            Section::make('')
                                ->schema($taxonomyFields),
                            Section::make('')
                                ->schema([
                                    static::getAuthorSelect(),
                                ]),
                            Section::make('')
                                ->schema([
                                    ...static::getStandardCopyableFields(),
                                    Section::make('')
                                        ->schema([
                                            ...static::getStandardTimestampFields(),
                                        ]),
                                ])
                                ->hidden(fn ($record) => $record === null),
                        ])
                        ->columnSpan(1)
                        ->columns(1),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ];

        return $form
            ->components($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TranslationColumn::make('translations.locale'),
                TextColumn::make('author.name')
                    ->label('Author')
                    ->sortable(),
                TextColumn::make('type')
                    ->sortable(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('uuid')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ulid')
                    ->toggleable(isToggledHiddenByDefault: true),
                static::getStatusColumn(),
                ...static::getTaxonomyColumns(),
            ])
            ->recordActions([...static::getTableActions()])
            ->toolbarActions([...static::getBulkActions()])
            ->filters([
                static::getTranslationStatusFilter(),
                SelectFilter::make('type')
                    ->label(__('core::core.type'))
                    ->options(['text' => 'Text', 'number' => 'Number', 'boolean' => 'Boolean', 'date' => 'Date', 'time' => 'Time', 'datetime' => 'Datetime', 'select' => 'Select', 'multiselect' => 'Multiselect', 'checkbox' => 'Checkbox', 'radio' => 'Radio', 'textarea' => 'Textarea', 'editor' => 'Editor', 'file' => 'File', 'image' => 'Image', 'video' => 'Video', 'audio' => 'Audio', 'link' => 'Link', 'embed' => 'Embed']),
                ...static::getTaxonomyFilters(),
                static::getLocaleFilter(),
            ])->deferFilters(false)
            ->persistFiltersInSession();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttributes::route('/'),
            'create' => Pages\CreateAttribute::route('/create'),
            'edit' => Pages\EditAttribute::route('/{record}/edit'),
            'view' => Pages\ViewAttribute::route('/{record}'),
        ];
    }

    public static function setCurrentTab(?string $tab): void
    {
        static::$currentTab = $tab;
    }
}
