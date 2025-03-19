<?php

namespace Moox\Draft\Moox\Entities\Drafts\Draft;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Moox\Core\Entities\Items\Draft\BaseDraftResource;
use Moox\Core\Forms\Components\CopyableField;
use Moox\Core\Traits\Taxonomy\HasResourceTaxonomy;
use Moox\Draft\Models\Draft;
use Moox\Media\Forms\Components\MediaPicker;

class DraftResource extends BaseDraftResource
{
    use HasResourceTaxonomy;

    protected static ?string $model = Draft::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                                    TextInput::make('title')
                                        ->live(onBlur: true)
                                        ->label(__('core::core.title'))
                                        ->required()
                                        ->afterStateUpdated(
                                            fn (Set $set, ?string $state) => $set('slug', Str::slug($state))
                                        ),
                                    TextInput::make('slug')
                                        ->label(__('core::core.slug'))
                                        ->required()
                                        ->unique(
                                            modifyRuleUsing: function (Unique $rule) {
                                                return $rule
                                                    ->where('locale', request()->query('lang', app()->getLocale()))
                                                    ->whereNull('draft_translations.draft_id');
                                            },
                                            table: 'draft_translations',
                                            column: 'slug',
                                            ignoreRecord: true,
                                            ignorable: fn ($record) => $record?->translations()
                                                ->where('locale', request()->query('lang', app()->getLocale()))
                                                ->first()
                                        ),
                                    Toggle::make('is_active')
                                        ->label('Active'),
                                    RichEditor::make('description')
                                        ->label('Description'),
                                    MarkdownEditor::make('content')
                                        ->label('Content'),
                                    KeyValue::make('data')
                                        ->label('Data (JSON)'),
                                    MediaPicker::make('image')
                                        ->label(__('core::core.image')),
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
                                        ->options(['Probably' => 'Probably', 'Never' => 'Never', 'Done' => 'Done', 'Maybe' => 'Maybe']),
                                ]),
                            Section::make('')
                                ->schema($taxonomyFields),
                            Section::make('')
                                ->schema([
                                    Select::make('author_id')
                                        ->label('Author')
                                        ->relationship('author', 'name'),
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
                    ->searchable()
                    ->sortable()->state(function ($record) {
                        $lang = request()->get('lang');
                        if ($lang && $record->hasTranslation($lang)) {
                            return $record->translate($lang)->title;
                        }

                        return $record->title;
                    }),
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
            // TODO: add default sort for localized fields
            // ->defaultSort('title', 'desc')
            ->actions([...static::getTableActions()])
            ->bulkActions([...static::getBulkActions()])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
                Filter::make('title')
                    ->form([
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
            'index' => Pages\ListDrafts::route('/'),
            'create' => Pages\CreateDraft::route('/create'),
            'edit' => Pages\EditDraft::route('/{record}/edit'),
            'view' => Pages\ViewDraft::route('/{record}'),
        ];
    }
}
