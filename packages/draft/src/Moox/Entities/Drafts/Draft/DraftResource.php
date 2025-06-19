<?php

namespace Moox\Draft\Moox\Entities\Drafts\Draft;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Moox\User\Models\User;
use Moox\Draft\Moox\Entities\Drafts\Draft\Pages\ListDrafts;
use Moox\Draft\Moox\Entities\Drafts\Draft\Pages\CreateDraft;
use Moox\Draft\Moox\Entities\Drafts\Draft\Pages\EditDraft;
use Moox\Draft\Moox\Entities\Drafts\Draft\Pages\ViewDraft;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
use Moox\Draft\Models\Draft;
use Moox\Localization\Filament\Tables\Columns\TranslationColumn;
use Moox\Media\Forms\Components\MediaPicker;
use Moox\Slug\Forms\Components\TitleWithSlugInput;

class DraftResource extends BaseDraftResource
{
    use HasResourceTaxonomy;

    protected static ?string $model = Draft::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getModelLabel(): string
    {
        return config('draft.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('draft.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('draft.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('draft.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('draft.navigation_group');
    }

    public static function form(Schema $form): Schema
    {
        $taxonomyFields = static::getTaxonomyFields();

        $schema = [
            Grid::make(2)
                ->schema([
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    MediaPicker::make('image')
                                        ->label(__('core::core.image')),
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
                                            'table' => 'draft_translations',
                                            'column' => 'slug',
                                        ]
                                    ),

                                    Toggle::make('is_active')
                                        ->label('Active'),
                                    RichEditor::make('description')
                                        ->label('Description'),
                                    MarkdownEditor::make('content')
                                        ->label('Content'),
                                    KeyValue::make('data')
                                        ->label('Data (JSON)'),
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
                                        ->label('Type')
                                        ->options(['Post' => 'Post', 'Page' => 'Page']),
                                    Select::make('status')
                                        ->label('Status')
                                        ->placeholder(__('core::core.status'))
                                        ->reactive()
                                        ->options(['draft' => 'Draft', 'waiting' => 'Waiting', 'privat' => 'Privat', 'scheduled' => 'Scheduled', 'published' => 'Published'])
                                        ->default('draft'),
                                    DateTimePicker::make('to_publish_at')
                                        ->label('To publish at')
                                        ->placeholder(__('core::core.to_publish_at'))
                                        ->hidden(fn ($get) => $get('status') !== 'scheduled')
                                        ->dehydrateStateUsing(fn ($state, $get) => $get('status') === 'scheduled' ? $state : null),
                                    DateTimePicker::make('to_unpublish_at')
                                        ->label('To unpublish at')
                                        ->placeholder(__('core::core.to_unpublish_at'))
                                        ->hidden(fn ($get) => ! in_array($get('status'), ['scheduled', 'published']))
                                        ->dehydrateStateUsing(fn ($state, $get) => in_array($get('status'), ['scheduled', 'published']) ? $state : null),
                                ]),
                            Section::make('')
                                ->schema($taxonomyFields),
                            Section::make('')
                                ->schema([
                                    Select::make('author_id')
                                        ->label('Author')
                                        ->options(User::all()->pluck('name', 'id')),
                                    DateTimePicker::make('due_at')
                                        ->label('Due'),
                                    ColorPicker::make('color')
                                        ->label('Color'),
                                ]),
                            Section::make('')
                                ->schema([
                                    CopyableField::make('id')
                                        ->label('ID')
                                        ->defaultValue(fn ($record): string => $record->id ?? ''),
                                    CopyableField::make('uuid')
                                        ->label('UUID')
                                        ->defaultValue(fn ($record): string => $record->uuid ?? ''),
                                    CopyableField::make('ulid')
                                        ->label('ULID')
                                        ->defaultValue(fn ($record): string => $record->ulid ?? ''),
                                    Section::make('')
                                        ->schema([
                                            Placeholder::make('created_at')
                                                ->label('Created')
                                                ->content(fn ($record): string => $record->created_at ?
                                                    $record->created_at.' - '.$record->created_at->diffForHumans() : '')
                                                ->extraAttributes(['class' => 'font-mono']),
                                            Placeholder::make('updated_at')
                                                ->label('Last Updated')
                                                ->content(fn ($record): string => $record->updated_at ?
                                                    $record->updated_at.' - '.$record->updated_at->diffForHumans() : '')
                                                ->extraAttributes(['class' => 'font-mono']),
                                            Placeholder::make('published_at')
                                                ->label('Published')
                                                ->content(fn ($record): string => $record->published_at ?
                                                    $record->published_at.' - '.$record->published_at->diffForHumans().
                                                    ($record->published_by_id ? ' by '.$record->published_by_id : '') : '')
                                                ->extraAttributes(['class' => 'font-mono'])
                                                ->hidden(fn ($record) => ! $record->published_at),
                                            Placeholder::make('to_unpublish_at')
                                                ->label('To Unpublish')
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
            ->components($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
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
                    ->boolean()
                    ->label('Active')
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable()
                    ->state(function ($record) {
                        $lang = request()->get('lang');
                        if ($lang && $record->hasTranslation($lang)) {
                            return $record->translate($lang)->slug;
                        }

                        return $record->slug;
                    }),
                TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->state(function ($record) {
                        $lang = request()->get('lang');
                        if ($lang && $record->hasTranslation($lang)) {
                            return $record->translate($lang)->description;
                        }

                        return $record->description;
                    }),
                TextColumn::make('content')
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
                    ->label('Author')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('type')
                    ->sortable()
                    ->searchable(),
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
                TextColumn::make('section')
                    ->sortable()
                    ->toggleable(),
                ...static::getTaxonomyColumns(),
                TextColumn::make('status')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
            ])
            ->recordActions([...static::getTableActions()])
            ->toolbarActions([...static::getBulkActions()])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
                Filter::make('title')
                    ->schema([
                        TextInput::make('title')
                            ->label('Title')
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
                    ->label('Status')
                    ->placeholder(__('core::core.filter').' Status')
                    ->options(['Probably' => 'Probably', 'Never' => 'Never', 'Done' => 'Done', 'Maybe' => 'Maybe']),
                SelectFilter::make('type')
                    ->label('Type')
                    ->placeholder(__('core::core.filter').' Type')
                    ->options(['Post' => 'Post', 'Page' => 'Page']),
                SelectFilter::make('section')
                    ->label('Section')
                    ->placeholder(__('core::core.filter').' Section')
                    ->options(['Header' => 'Header', 'Main' => 'Main', 'Footer' => 'Footer']),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDrafts::route('/'),
            'create' => CreateDraft::route('/create'),
            'edit' => EditDraft::route('/{record}/edit'),
            'view' => ViewDraft::route('/{record}'),
        ];
    }
}
