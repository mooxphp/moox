<?php

namespace Moox\News\Moox\Entities\News\News;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;
use Moox\Core\Entities\Items\Draft\BaseDraftResource;
use Moox\Core\Forms\Components\CopyableField;
use Moox\Core\Traits\Taxonomy\HasResourceTaxonomy;
use Moox\Localization\Filament\Tables\Columns\TranslationColumn;
use Moox\Media\Forms\Components\MediaPicker;
use Moox\News\Models\News;
use Moox\Slug\Forms\Components\TitleWithSlugInput;

class NewsResource extends BaseDraftResource
{
    use HasResourceTaxonomy;

    protected static ?string $model = News::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getModelLabel(): string
    {
        return config('news.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('news.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('news.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('news.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('news.navigation_group');
    }

    public static function form(Form $form): Form
    {
        $taxonomyFields = static::getTaxonomyFields();

        $schema = [
            Grid::make(2)
                ->schema([
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    TitleWithSlugInput::make(
                                        fieldTitle: 'title',
                                        fieldSlug: 'slug',
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
                                            'table' => 'news_translations',
                                            'column' => 'slug',
                                        ]
                                    ),

                                    Toggle::make('is_active')
                                        ->label(__('news::fields.is_active')),
                                    MarkdownEditor::make('excerpt')
                                        ->label(__('news::fields.excerpt'))
                                        ->rules(config('news.rules.excerpt')),
                                    MarkdownEditor::make('content')
                                        ->label(__('news::fields.content')),
                                    KeyValue::make('data')
                                        ->label(__('news::fields.data')),
                                    MediaPicker::make('image')
                                        ->label(__('core::core.image')),
                                    MediaPicker::make('gallery')
                                        ->label(__('news::fields.gallery')),
                                ]),
                            Grid::make(2)
                                ->schema([
                                    static::getFooterActions()->columnSpan(1),
                                ]),
                        ])
                        ->columnSpan(['lg' => 2]),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    static::getFormActions(),
                                ]),
                            Section::make('')
                                ->schema([
                                    Select::make('type')
                                        ->label(__('news::fields.type'))
                                        ->options(['Post' => 'Post', 'Page' => 'Page']),
                                    Select::make('status')
                                        ->label(__('news::fields.status'))
                                        ->placeholder(__('core::core.status'))
                                        ->reactive()
                                        ->options(['news' => 'News', 'waiting' => 'Waiting', 'privat' => 'Privat', 'scheduled' => 'Scheduled', 'published' => 'Published'])
                                        ->default('news'),
                                    DateTimePicker::make('to_publish_at')
                                        ->label(__('news::fields.to_publish_at'))
                                        ->placeholder(__('core::core.to_publish_at'))
                                        ->hidden(fn ($get) => $get('status') !== 'scheduled')
                                        ->dehydrateStateUsing(fn ($state, $get) => $get('status') === 'scheduled' ? $state : null),
                                    DateTimePicker::make('to_unpublish_at')
                                        ->label(__('news::fields.to_unpublish_at'))
                                        ->placeholder(__('core::core.to_unpublish_at'))
                                        ->hidden(fn ($get) => ! in_array($get('status'), ['scheduled', 'published']))
                                        ->dehydrateStateUsing(fn ($state, $get) => in_array($get('status'), ['scheduled', 'published']) ? $state : null),
                                ]),
                            Section::make('')
                                ->schema($taxonomyFields),
                            Section::make('')
                                ->schema([
                                    Select::make('author_id')
                                        ->label(__('news::fields.author_id'))
                                        ->options(\Moox\User\Models\User::all()->pluck('name', 'id')),
                                    DateTimePicker::make('due_at')
                                        ->label(__('news::fields.due_at')),
                                    ColorPicker::make('color')
                                        ->label(__('news::fields.color')),
                                ]),
                            Section::make('')
                                ->schema([
                                    CopyableField::make('id')
                                        ->label(__('news::fields.id'))
                                        ->defaultValue(fn ($record): string => $record->id ?? ''),
                                    CopyableField::make('uuid')
                                        ->label(__('news::fields.uuid'))
                                        ->defaultValue(fn ($record): string => $record->uuid ?? ''),
                                    CopyableField::make('ulid')
                                        ->label(__('news::fields.ulid'))
                                        ->defaultValue(fn ($record): string => $record->ulid ?? ''),

                                    Section::make('')
                                        ->schema([
                                            Placeholder::make('created_by')
                                                ->label(__('news::fields.created_by'))
                                                ->content(function ($record) {
                                                    $lang = request()->get('lang') ?? app()->getLocale();
                                                    $translation = $record?->translate($lang);

                                                    return $translation?->createdBy?->name ?? '—';
                                                }),
                                            Placeholder::make('updated_by')
                                                ->label(__('news::fields.updated_by'))
                                                ->content(function ($record) {
                                                    $lang = request()->get('lang') ?? app()->getLocale();
                                                    $translation = $record?->translate($lang);

                                                    return $translation?->updatedBy?->name ?? '—';
                                                }),
                                            Placeholder::make('created_at')
                                                ->label(__('news::fields.created_at'))
                                                ->content(fn ($record): string => $record->created_at ?
                                                    $record->created_at.' - '.$record->created_at->diffForHumans() : '')
                                                ->extraAttributes(['class' => 'font-mono']),
                                            Placeholder::make('updated_at')
                                                ->label(__('news::fields.updated_at'))
                                                ->content(fn ($record): string => $record->updated_at ?
                                                    $record->updated_at.' - '.$record->updated_at->diffForHumans() : '')
                                                ->extraAttributes(['class' => 'font-mono']),
                                            Placeholder::make('published_at')
                                                ->label(__('news::fields.published_at'))
                                                ->content(fn ($record): string => $record->published_at ?
                                                    $record->published_at.' - '.$record->published_at->diffForHumans().
                                                    ($record->published_by_id ? ' by '.$record->published_by_id : '') : '')
                                                ->extraAttributes(['class' => 'font-mono'])
                                                ->hidden(fn ($record) => ! $record->published_at),
                                            Placeholder::make('to_unpublish_at')
                                                ->label(__('news::fields.to_unpublish_at'))
                                                ->content(fn ($record): string => $record->to_unpublish_at ?
                                                    $record->to_unpublish_at.' - '.$record->to_unpublish_at->diffForHumans() : '')
                                                ->extraAttributes(['class' => 'font-mono'])
                                                ->hidden(fn ($record) => ! $record->to_unpublish_at),

                                        ]),

                                ])
                                ->hidden(fn ($record) => $record === null),
                        ])
                        ->columnSpan(['lg' => 1]),
                ])
                ->columns(['lg' => 3]),
        ];

        return $form
            ->schema($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('news::fields.title'))
                    ->searchable()
                    ->sortable()->state(function ($record) {
                        $lang = request()->get('lang');
                        if ($lang && $record->hasTranslation($lang)) {
                            return $record->translate($lang)->title;
                        }

                        return $record->title;
                    }),
                TranslationColumn::make('translations.locale'),
                IconColumn::make('is_active')
                    ->label(__('news::fields.is_active'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('news::fields.slug'))
                    ->searchable()
                    ->sortable()
                    ->state(function ($record) {
                        $lang = request()->get('lang');
                        if ($lang && $record->hasTranslation($lang)) {
                            return $record->translate($lang)->slug;
                        }

                        return $record->slug;
                    }),
                TextColumn::make('excerpt')
                    ->label(__('news::fields.excerpt'))
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->state(function ($record) {
                        $lang = request()->get('lang');
                        $excerpt = $lang && $record->hasTranslation($lang)
                            ? $record->translate($lang)->excerpt
                            : $record->excerpt;

                        return strip_tags($excerpt);
                    }),

                TextColumn::make('created_by')
                    ->label(__('news::fields.created_by'))
                    ->formatStateUsing(function ($state, $record) {
                        $lang = request()->get('lang') ?? app()->getLocale();

                        return $record->translate($lang)?->createdBy?->name ?? '–';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_by')
                    ->label(__('news::fields.updated_by'))
                    ->formatStateUsing(function ($state, $record) {
                        $lang = request()->get('lang') ?? app()->getLocale();

                        return $record->translate($lang)?->updatedBy?->name ?? '–';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('content')
                    ->label(__('news::fields.content'))
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->state(function ($record) {
                        $lang = request()->get('lang');
                        if ($lang && $record->hasTranslation($lang)) {
                            return $record->translate($lang)->content;
                        }

                        return $record->content;
                    }),
                TextColumn::make('author.name')
                    ->label(__('news::fields.author_name'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('type')
                    ->label(__('news::fields.type'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('published_at')
                    ->label(__('news::fields.published_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                ColorColumn::make('color')
                    ->label(__('news::fields.color'))
                    ->toggleable(),
                TextColumn::make('uuid')
                    ->label(__('news::fields.uuid'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ulid')
                    ->label(__('news::fields.ulid'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('section')
                    ->label(__('news::fields.section'))
                    ->sortable()
                    ->toggleable(),
                ...static::getTaxonomyColumns(),
                TextColumn::make('status')
                    ->label(__('news::fields.status'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
            ])
            ->actions([...static::getTableActions()])
            ->bulkActions([...static::getBulkActions()])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('news::fields.is_active')),
                Filter::make('title')
                    ->form([
                        TextInput::make('title')
                            ->label(__('news::fields.title'))
                            ->placeholder(__('core::core.filter').' Title'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['title'],
                            fn (Builder $query, $value): Builder => $query->where('title', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['title']) {
                            return null;
                        }

                        return 'Title: '.$data['title'];
                    }),
                SelectFilter::make('status')
                    ->label(__('news::fields.status'))
                    ->placeholder(__('core::core.filter').' Status')
                    ->options(['Probably' => 'Probably', 'Never' => 'Never', 'Done' => 'Done', 'Maybe' => 'Maybe']),
                SelectFilter::make('type')
                    ->label(__('news::fields.type'))
                    ->placeholder(__('core::core.filter').' Type')
                    ->options(['Post' => 'Post', 'Page' => 'Page']),
                SelectFilter::make('section')
                    ->label(__('news::fields.section'))
                    ->placeholder(__('core::core.filter').' Section')
                    ->options(['Header' => 'Header', 'Main' => 'Main', 'Footer' => 'Footer']),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNews::route('/'),
            'create' => Pages\CreateNews::route('/create'),
            'edit' => Pages\EditNews::route('/{record}/edit'),
            'view' => Pages\ViewNews::route('/{record}'),
        ];
    }
}
