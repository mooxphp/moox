<?php

namespace Moox\Item\Moox\Entities\Items\Item;

use Camya\Filament\Forms\Components\TitleWithSlugInput;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Clipboard\Forms\Components\CopyableField;
use Moox\Core\Entities\Items\Item\BaseItemResource;
use Moox\Core\Traits\Taxonomy\HasResourceTaxonomy;
use Moox\Item\Models\Item;
use Moox\Item\Moox\Entities\Items\Item\Pages\CreateItem;
use Moox\Item\Moox\Entities\Items\Item\Pages\EditItem;
use Moox\Item\Moox\Entities\Items\Item\Pages\ListItems;
use Moox\Item\Moox\Entities\Items\Item\Pages\ViewItem;

class ItemResource extends BaseItemResource
{
    use HasResourceTaxonomy;

    protected static ?string $model = Item::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getModelLabel(): string
    {
        return config('item.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('item.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('item.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('item.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('item.navigation_group');
    }

    public static function form(Schema $form): Schema
    {
        $taxonomyFields = static::getTaxonomyFields();

        $schema = [
            Grid::make(2)
                ->schema([
                    Section::make()
                        ->schema([
                            // TitleWithSlugInput::make(
                            //     fieldTitle: 'title',
                            //     fieldSlug: 'slug',
                            // ),
                            Toggle::make('is_active')
                                ->label('Active'),
                            RichEditor::make('description')
                                ->label('Description'),
                            MarkdownEditor::make('content')
                                ->label('Content'),
                            KeyValue::make('data')
                                ->label('Data (JSON)'),
                            FileUpload::make('image')
                                ->label('Image')
                                ->directory('items')
                                ->image(),
                            Grid::make(2)
                                ->schema([
                                    // TODO: exactly same as getFormActions(), why?
                                    /** @phpstan-ignore-next-line */
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
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('content')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->defaultSort('title', 'desc')
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
            'index' => ListItems::route('/'),
            'create' => CreateItem::route('/create'),
            'edit' => EditItem::route('/{record}/edit'),
            'view' => ViewItem::route('/{record}'),
        ];
    }
}
