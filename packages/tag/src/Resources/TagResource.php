<?php

declare(strict_types=1);

namespace Moox\Tag\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Actions;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Localization\Filament\Tables\Columns\TranslationColumn;
use Moox\Localization\Models\Localization;
use Moox\Media\Forms\Components\MediaPicker;
use Moox\Media\Tables\Columns\CustomImageColumn;
use Moox\Tag\Models\Tag;
use Moox\Tag\Resources\TagResource\Pages\CreateTag;
use Moox\Tag\Resources\TagResource\Pages\EditTag;
use Moox\Tag\Resources\TagResource\Pages\ListTags;
use Moox\Tag\Resources\TagResource\Pages\ViewTag;
use Override;

class TagResource extends Resource
{
    use HasResourceTabs;

    protected static ?string $model = Tag::class;

    protected static ?string $currentTab = null;

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    protected static string | \BackedEnum | null $navigationIcon = 'gmdi-label';

    protected static ?string $authorModel = null;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        static::initUserModel();

        return $schema->components([
            Grid::make(2)
                ->schema([
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    MediaPicker::make('featured_image_url')
                                        ->label(__('core::core.featured_image_url'))
                                        ->multiple(),
                                    Tabs::make('Translations')
                                        ->tabs(self::generateTranslationTabs()),
                                ]),
                        ])
                        ->columnSpan(['lg' => 2]),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    Actions::make([
                                        Action::make('restore')
                                            ->label(__('core::core.restore'))
                                            ->color('success')
                                            ->button()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(fn ($record) => $record->restore())
                                            ->visible(fn ($livewire, $record): bool => $record && $record->trashed() && $livewire instanceof ViewTag),
                                        Action::make('save')
                                            ->label(__('core::core.save'))
                                            ->color('primary')
                                            ->button()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(function ($livewire): void {
                                                $livewire instanceof CreateTag ? $livewire->create() : $livewire->save();
                                            })
                                            ->visible(fn ($livewire): bool => $livewire instanceof CreateTag || $livewire instanceof EditTag),
                                        Action::make('saveAndCreateAnother')
                                            ->label(__('core::core.save_and_create_another'))
                                            ->color('secondary')
                                            ->button()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(function ($livewire): void {
                                                $livewire->saveAndCreateAnother();
                                            })
                                            ->visible(fn ($livewire): bool => $livewire instanceof CreateTag),
                                        Action::make('cancel')
                                            ->label(__('core::core.cancel'))
                                            ->color('secondary')
                                            ->outlined()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->url(fn (): string => static::getUrl('index'))
                                            ->visible(fn ($livewire): bool => $livewire instanceof CreateTag),
                                        Action::make('edit')
                                            ->label(__('core::core.edit'))
                                            ->color('primary')
                                            ->button()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->url(fn ($record): string => static::getUrl('edit', ['record' => $record, 'lang' => request()->get('lang')]))
                                            ->visible(fn ($livewire, $record): bool => $livewire instanceof ViewTag && ! $record->trashed()),
                                        Action::make('restore')
                                            ->label(__('core::core.restore'))
                                            ->color('success')
                                            ->button()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(fn ($record) => $record->restore())
                                            ->visible(fn ($livewire, $record): bool => $record && $record->trashed() && $livewire instanceof EditTag),
                                        Action::make('delete')
                                            ->label(__('core::core.delete'))
                                            ->color('danger')
                                            ->link()
                                            ->extraAttributes(['class' => 'w-full'])
                                            ->action(fn ($record) => $record->delete())
                                            ->visible(fn ($livewire, $record): bool => $record && ! $record->trashed() && $livewire instanceof EditTag),
                                    ]),
                                    ColorPicker::make('color'),
                                    TextInput::make('weight'),
                                    TextInput::make('count')
                                        ->disabled()
                                        ->visible(fn ($livewire, $record): bool => ($record && $livewire instanceof EditTag) || ($record && $livewire instanceof ViewTag)),
                                    DateTimePicker::make('created_at')
                                        ->disabled()
                                        ->visible(fn ($livewire, $record): bool => ($record && $livewire instanceof EditTag) || ($record && $livewire instanceof ViewTag)),
                                    DateTimePicker::make('updated_at')
                                        ->disabled()
                                        ->visible(fn ($livewire, $record): bool => ($record && $livewire instanceof EditTag) || ($record && $livewire instanceof ViewTag)),
                                    DateTimePicker::make('deleted_at')
                                        ->disabled()
                                        ->visible(fn ($livewire, $record): bool => $record && $record->trashed() && $livewire instanceof ViewTag),
                                ]),
                        ])
                        ->columnSpan(['lg' => 1]),
                ])
                ->columns(['lg' => 3]),
        ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        static::initUserModel();

        $currentTab = static::getCurrentTab();

        return $table
            ->columns([
                CustomImageColumn::make('featured_image_url')
                    ->label(__('core::core.image'))
                    ->defaultImageUrl(url('/moox/core/assets/noimage.svg'))
                    ->alignment('center'),
                TranslationColumn::make('translations.locale'),
                TextColumn::make('title')
                    ->label(__('core::core.title'))
                    ->limit(30)
                    ->toggleable()
                    ->sortable()
                    ->state(function ($record) {
                        $lang = request()->get('lang');
                        if ($lang && $record->hasTranslation($lang)) {
                            return $record->translate($lang)->title;
                        }

                        return $record->title;
                    }),
                TextColumn::make('slug')
                    ->label(__('core::core.slug'))
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable()
                    ->state(function ($record) {
                        $lang = request()->get('lang');
                        if ($lang && $record->hasTranslation($lang)) {
                            return $record->translate($lang)->slug;
                        }

                        return $record->slug;
                    }),
                TextColumn::make('content')
                    ->label(__('core::core.content'))
                    ->sortable()
                    ->limit(30)
                    ->toggleable()
                    ->state(function ($record) {
                        $lang = request()->get('lang');
                        if ($lang && $record->hasTranslation($lang)) {
                            return $record->translate($lang)->content;
                        }

                        return $record->content;
                    }),
                TextColumn::make('count')
                    ->label(__('core::core.count'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('weight')
                    ->label(__('tag::translations.weight'))
                    ->sortable()
                    ->toggleable(),
                ColorColumn::make('color')
                    ->label(__('tag::translations.color'))
                    ->sortable()
                    ->toggleable(),
            ])
            ->recordActions([
                ViewAction::make()->url(
                    fn ($record) => request()->has('lang')
                    ? static::getUrl('view', ['record' => $record, 'lang' => request()->get('lang')])
                    : static::getUrl('view', ['record' => $record])
                ),
                EditAction::make()
                    ->url(
                        fn ($record) => request()->has('lang')
                        ? static::getUrl('edit', ['record' => $record, 'lang' => request()->get('lang')])
                        : static::getUrl('edit', ['record' => $record])
                    )
                    ->hidden(fn (): bool => in_array(static::getCurrentTab(), ['trash', 'deleted'])),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()->hidden(fn (): bool => in_array($currentTab, ['trash', 'deleted'])),
                RestoreBulkAction::make()->visible(fn (): bool => in_array($currentTab, ['trash', 'deleted'])),
            ]);
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

    protected static function initUserModel(): void
    {
        if (static::$authorModel === null) {
            static::$authorModel = config('tag.user_model');
        }
    }

    protected static function getUserOptions(): array
    {
        return static::$authorModel::query()->get()->pluck('name', 'id')->toArray();
    }

    protected static function shouldShowAuthorField(): bool
    {
        return static::$authorModel && class_exists(static::$authorModel);
    }

    public static function getCurrentTab(): ?string
    {
        if (static::$currentTab === null) {
            static::$currentTab = request()->query('tab', '');
        }

        return static::$currentTab ?: null;
    }

    public static function getTableQuery(?string $currentTab = null): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes();

        if ($currentTab === 'trash' || $currentTab === 'deleted') {
            $model = static::getModel();
            if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $query->whereNotNull($model::make()->getQualifiedDeletedAtColumn());
            }
        }

        return $query;
    }

    public static function setCurrentTab(?string $tab): void
    {
        static::$currentTab = $tab;
    }

    /**
     * Generate tabs for all available locales
     */
    protected static function generateTranslationTabs(): array
    {
        $tabs = [];

        foreach (Localization::pluck('title') as $locale) {
            $tabs[] = Tab::make(strtoupper($locale))
                ->schema([
                    TextInput::make("translations.{$locale}.title")
                        ->live(onBlur: true)
                        ->label(__('core::core.title'))
                        ->afterStateUpdated(
                            fn (Set $set, ?string $state) => $set("translations.{$locale}.slug", Str::slug($state))
                        ),

                    TextInput::make("translations.{$locale}.slug")
                        ->label(__('core::core.slug'))
                        ->unique(
                            modifyRuleUsing: function (Unique $rule) use ($locale) {
                                return $rule
                                    ->where('locale', $locale)
                                    ->whereNull('tag_translations.tag_id');
                            },
                            table: 'tag_translations',
                            column: 'slug',
                            ignoreRecord: true,
                            ignorable: fn ($record) => $record?->translations()
                                ->where('locale', $locale)
                                ->first()
                        ),

                    MarkdownEditor::make("translations.{$locale}.content")
                        ->label(__('core::core.content')),
                ]);
        }

        return $tabs;
    }
}
