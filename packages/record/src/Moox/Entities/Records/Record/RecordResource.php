<?php

namespace Moox\Record\Moox\Entities\Records\Record;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Entities\Items\Record\BaseRecordResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Core\Traits\Taxonomy\HasResourceTaxonomy;
use Moox\Record\Enums\RecordStatus;
use Moox\Record\Models\Record;
use Moox\Record\Moox\Entities\Records\Record\Pages\CreateRecord;
use Moox\Record\Moox\Entities\Records\Record\Pages\EditRecord;
use Moox\Record\Moox\Entities\Records\Record\Pages\ListRecords;
use Moox\Record\Moox\Entities\Records\Record\Pages\ViewRecord;
use Moox\Slug\Forms\Components\TitleWithSlugInput;

class RecordResource extends BaseRecordResource
{
    use HasResourceTabs;
    use HasResourceTaxonomy;

    protected static ?string $model = Record::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-storage';

    public static function getModelLabel(): string
    {
        return config('record.resources.record.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('record.resources.record.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('record.resources.record.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('record.resources.record.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('record.navigation_group');
    }

    public static function form(Schema $form): Schema
    {
        $taxonomyFields = static::getTaxonomyFields();

        $schema = [
            Grid::make()
                ->schema([
                    Section::make()
                        ->schema([
                            TitleWithSlugInput::make(
                                fieldTitle: 'title',
                                fieldSlug: 'slug',
                                fieldPermalink: 'permalink',
                                urlPathEntityType: 'records',
                                slugRuleUniqueParameters: [
                                    'table' => 'records',
                                    'column' => 'slug',
                                ]
                            ),
                            RichEditor::make('description')
                                ->label(__('core::core.description')),
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
                                    Select::make('status')
                                        ->label(__('core::core.status'))
                                        ->options(collect(RecordStatus::cases())->mapWithKeys(fn ($case) => [
                                            $case->value => __('core::core.'.$case->value),
                                        ]))
                                        ->default(RecordStatus::INACTIVE->value)
                                        ->required(),
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
                TextColumn::make('title')
                    ->label(__('core::core.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('core::core.slug'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('content')
                    ->label(__('core::core.content'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('custom_properties')
                    ->limit(50),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                static::getStatusColumn(),
                ...static::getTaxonomyColumns(),
            ])
            ->defaultSort('title', 'desc')
            ->recordActions([...static::getTableActions()])
            ->toolbarActions([...static::getBulkActions()])
            ->filters([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecords::route('/'),
            'create' => CreateRecord::route('/create'),
            'edit' => EditRecord::route('/{record}/edit'),
            'view' => ViewRecord::route('/{record}'),
        ];
    }
}
