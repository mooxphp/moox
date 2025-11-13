<?php

declare(strict_types=1);

namespace Moox\Tag\Resources;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;
use Moox\Core\Entities\Items\Draft\BaseDraftResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Localization\Filament\Tables\Columns\TranslationColumn;
use Moox\Media\Forms\Components\MediaPicker;
use Moox\Slug\Forms\Components\TitleWithSlugInput;
use Moox\Tag\Models\Tag;
use Moox\Tag\Resources\TagResource\Pages\CreateTag;
use Moox\Tag\Resources\TagResource\Pages\EditTag;
use Moox\Tag\Resources\TagResource\Pages\ListTags;
use Moox\Tag\Resources\TagResource\Pages\ViewTag;
use Override;

class TagResource extends BaseDraftResource
{
    use HasResourceTabs;

    protected static ?string $model = Tag::class;

    protected static ?string $currentTab = null;

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-label';

    protected static ?string $authorModel = null;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TitleWithSlugInput::make(
                                    fieldTitle: 'title',
                                    fieldSlug: 'slug',
                                    fieldPermalink: 'permalink',
                                    urlPathEntityType: 'tags',
                                    slugRuleUniqueParameters: [
                                        'modifyRuleUsing' => function (Unique $rule, $record, $livewire) {
                                            $locale = $livewire->lang;
                                            if ($record) {
                                                $rule->where('locale', $locale);
                                                $existingTranslation = $record->translations()
                                                    ->where('locale', $locale)
                                                    ->first();
                                                if ($existingTranslation) {
                                                    $rule->ignore($existingTranslation->id);
                                                }
                                            } else {
                                                $rule->where('locale', $locale);
                                            }

                                            return $rule;
                                        },
                                        'table' => 'tag_translations',
                                        'column' => 'slug',
                                    ]
                                ),
                                MediaPicker::make('image')
                                    ->label(__('core::core.image')),
                                Toggle::make('is_active')
                                    ->label(__('core::core.active')),
                                RichEditor::make('description')
                                    ->label(__('core::core.description')),
                                MarkdownEditor::make('content')
                                    ->label(__('core::core.content')),
                                Grid::make(2)
                                    ->schema([
                                        static::getFooterActions()->columnSpan(1),
                                    ]),
                            ])
                            ->columnSpan(2),
                        Grid::make()
                            ->schema([
                                Section::make()
                                    ->schema([
                                        static::getFormActions(),
                                    ]),
                                Section::make('')
                                    ->schema([
                                        static::getTranslationStatusSelect(),
                                        static::getPublishDateField(),
                                        static::getUnpublishDateField(),
                                    ]),
                                Section::make('')
                                    ->schema([
                                        static::getAuthorSelect(),
                                        DateTimePicker::make('due_at')
                                            ->label(__('core::core.due')),
                                        ColorPicker::make('color')
                                            ->label(__('core::core.color')),
                                    ]),
                                Section::make('')
                                    ->schema([
                                        ...static::getStandardCopyableFields(),
                                        Section::make('')
                                            ->schema([
                                                ...static::getStandardTimestampFields(),
                                            ]),
                                    ])->hidden(fn ($record) => $record === null),
                            ])
                            ->columns(1)
                            ->columnSpan(1),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                static::getTitleColumn(),
                static::getSlugColumn(),
                TranslationColumn::make('translations.locale'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),
                TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('content')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('author.name')
                    ->label('Author')
                    ->sortable(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                ColorColumn::make('color')
                    ->toggleable(),
                TextColumn::make('uuid')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ulid')
                    ->toggleable(isToggledHiddenByDefault: true),
                static::getStatusColumn(),
            ])
            ->recordActions([...static::getTableActions()])
            ->toolbarActions([...static::getBulkActions()])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
                static::getTranslationStatusFilter(),
            ])->deferFilters(false)
            ->persistFiltersInSession();
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListTags::route('/'),
            'edit' => EditTag::route('/{record}/edit'),
            'create' => CreateTag::route('/create'),
            'view' => ViewTag::route('/{record}'),
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('tag.resources.tag.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('tag.resources.tag.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('tag.resources.tag.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('tag.resources.tag.single');
    }

    #[Override]
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('tag.navigation_group');
    }

    public static function setCurrentTab(?string $tab): void
    {
        static::$currentTab = $tab;
    }
}
