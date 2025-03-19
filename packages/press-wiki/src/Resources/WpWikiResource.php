<?php

namespace Moox\PressWiki\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\PressWiki\Models\WpWiki;
use Moox\PressWiki\Resources\WpWikiResource\Pages;
use Moox\PressWiki\Resources\WpWikiResource\Pages\ListWpWikis;
use Moox\PressWiki\Resources\WpWikiResource\RelationManagers\WpCommentRelationManager;
use Moox\PressWiki\Resources\WpWikiResource\RelationManagers\WpPostMetaRelationManager;
use Override;

class WpWikiResource extends Resource
{
    use BaseInResource;
    use HasResourceTabs;

    protected static ?string $model = WpWiki::class;

    protected static ?string $navigationIcon = 'gmdi-library-books';

    protected static ?string $recordTitleAttribute = 'post_title';

    #[Override]
    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    TextInput::make('post_author')
                        ->label(__('core::post.post_author'))
                        ->rules(['max:255'])
                        ->required()
                        ->default('0')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    DateTimePicker::make('post_date')
                        ->label(__('core::post.post_date'))
                        ->rules(['date'])
                        ->required()
                        ->default('0000-00-00 00:00:00')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    DateTimePicker::make('post_date_gmt')
                        ->label(__('core::post.post_date_gmt'))
                        ->rules(['date'])
                        ->required()
                        ->default('0000-00-00 00:00:00')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('post_content')
                        ->label(__('core::post.post_content'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('post_title')
                        ->label(__('core::post.post_title'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('post_excerpt')
                        ->label(__('core::post.post_excerpt'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('post_status')
                        ->label(__('core::post.post_status'))
                        ->rules(['max:20', 'string'])
                        ->required()
                        ->default('publish')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('comment_status')
                        ->label(__('core::comment.comment_status'))
                        ->rules(['max:20', 'string'])
                        ->required()
                        ->default('open')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('ping_status')
                        ->label(__('core::post.ping_status'))
                        ->rules(['max:20', 'string'])
                        ->required()
                        ->default('open')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('post_password')
                        ->label(__('core::post.post_password'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('post_name')
                        ->label(__('core::post.post_name'))
                        ->rules(['max:200', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('to_ping')
                        ->label(__('core::to_ping'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('pinged')
                        ->label(__('core::post.pinged'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    DateTimePicker::make('post_modified')
                        ->label(__('core::post.post_modified'))
                        ->rules(['date'])
                        ->required()
                        ->default('0000-00-00 00:00:00')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    DateTimePicker::make('post_modified_gmt')
                        ->label(__('core::post.post_modified_gmt'))
                        ->rules(['date'])
                        ->required()
                        ->default('0000-00-00 00:00:00')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('post_content_filtered')
                        ->label(__('core::post.post_content_filtered'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('post_parent')
                        ->label(__('core::post.post_parent'))
                        ->rules(['max:255'])
                        ->required()
                        ->default('0')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('guid')
                        ->label(__('core::core.guid'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('menu_order')
                        ->label(__('core::core.menu_order'))
                        ->rules(['numeric'])
                        ->required()
                        ->numeric()
                        ->default('0')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('post_type')
                        ->label(__('core::post.post_type'))
                        ->rules(['max:20', 'string'])
                        ->required()
                        ->default('post')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('post_mime_type')
                        ->label(__('core::post.post_mime_type'))
                        ->rules(['max:100', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('comment_count')
                        ->label(__('core::comment.comment_count'))
                        ->rules(['max:255'])
                        ->required()
                        ->default('0')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),
                ]),
            ]),
        ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('post_title')
                    ->label(__('core::post.post_title'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('author.display_name')
                    ->label(__('core::post.post_author'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('departmentTopics.name')
                    ->label(__('press-wiki::translations.wiki-department-topics')),
                TextColumn::make('letterTopics.name')
                    ->label(__('press-wiki::translations.wiki-letter-topics'))
                    ->alignCenter(),
                TextColumn::make('companyTopics.name')
                    ->label(__('press-wiki::translations.wiki-company-topics')),
                TextColumn::make('locationTopics.name')
                    ->label(__('press-wiki::translations.wiki-location-topics')),
                TextColumn::make('wikiTopics.name')
                    ->label(__('press-wiki::translations.wiki-topics')),
                TextColumn::make('post_date')
                    ->label(__('core::post.post_date'))
                    ->toggleable()
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Action::make('Edit')->url(fn ($record): string => sprintf('/wp/wp-admin/post.php?post=%s&action=edit', $record->ID)),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            WpPostMetaRelationManager::class,
            WpCommentRelationManager::class,
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListWpWikis::route('/'),
            // 'create' => Pages\CreateWpWiki::route('/create'),
            // 'view' => Pages\ViewWpWiki::route('/{record}'),
            // 'edit' => Pages\EditWpWiki::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('press-wiki.resources.wiki.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('press-wiki.resources.wiki.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('press-wiki.resources.wiki.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('press-wiki.resources.wiki.single');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('press-wiki.navigation_group');
    }
}
