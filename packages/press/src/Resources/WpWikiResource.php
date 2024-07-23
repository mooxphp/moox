<?php

namespace Moox\Press\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Press\Models\WpPost;
use Moox\Press\Resources\WpWikiResource\Pages;
use Moox\Press\Resources\WpWikiResource\RelationManagers\WpCommentRelationManager;
use Moox\Press\Resources\WpWikiResource\RelationManagers\WpPostMetaRelationManager;

class WpWikiResource extends Resource
{
    protected static ?string $model = WpPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static ?string $recordTitleAttribute = 'post_title';

    public static function getModelLabel(): string
    {
        return 'Wiki';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Wikis';
    }

    public static function getNavigationLabel(): string
    {
        return 'Wikis';
    }

    protected static ?string $navigationGroup = 'Moox Press Custom';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('post_type', 'wiki')
            ->whereIn('post_status', ['publish', 'draft']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    TextInput::make('post_author')
                        ->rules(['max:255'])
                        ->required()
                        ->placeholder('Post Author')
                        ->default('0')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    DateTimePicker::make('post_date')
                        ->rules(['date'])
                        ->required()
                        ->placeholder('Post Date')
                        ->default('0000-00-00 00:00:00')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    DateTimePicker::make('post_date_gmt')
                        ->rules(['date'])
                        ->required()
                        ->placeholder('Post Date Gmt')
                        ->default('0000-00-00 00:00:00')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('post_content')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Post Content')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('post_title')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Post Title')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('post_excerpt')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Post Excerpt')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('post_status')
                        ->rules(['max:20', 'string'])
                        ->required()
                        ->placeholder('Post Status')
                        ->default('publish')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('comment_status')
                        ->rules(['max:20', 'string'])
                        ->required()
                        ->placeholder('Comment Status')
                        ->default('open')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('ping_status')
                        ->rules(['max:20', 'string'])
                        ->required()
                        ->placeholder('Ping Status')
                        ->default('open')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('post_password')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Post Password')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('post_name')
                        ->rules(['max:200', 'string'])
                        ->required()
                        ->placeholder('Post Name')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('to_ping')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('To Ping')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('pinged')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Pinged')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    DateTimePicker::make('post_modified')
                        ->rules(['date'])
                        ->required()
                        ->placeholder('Post Modified')
                        ->default('0000-00-00 00:00:00')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    DateTimePicker::make('post_modified_gmt')
                        ->rules(['date'])
                        ->required()
                        ->placeholder('Post Modified Gmt')
                        ->default('0000-00-00 00:00:00')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('post_content_filtered')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Post Content Filtered')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('post_parent')
                        ->rules(['max:255'])
                        ->required()
                        ->placeholder('Post Parent')
                        ->default('0')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('guid')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Guid')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('menu_order')
                        ->rules(['numeric'])
                        ->required()
                        ->numeric()
                        ->placeholder('Menu Order')
                        ->default('0')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('post_type')
                        ->rules(['max:20', 'string'])
                        ->required()
                        ->placeholder('Post Type')
                        ->default('post')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('post_mime_type')
                        ->rules(['max:100', 'string'])
                        ->required()
                        ->placeholder('Post Mime Type')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('comment_count')
                        ->rules(['max:255'])
                        ->required()
                        ->placeholder('Comment Count')
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

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                Tables\Columns\TextColumn::make('post_author')
                    ->label('Author')
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('post_title')
                    ->label('Title')
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('post_status')
                    ->label('Status')
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('post_date')
                    ->label('Created')
                    ->toggleable()
                    ->dateTime(),
                Tables\Columns\TextColumn::make('post_modified')
                    ->label('Modified')
                    ->sortable()
                    ->toggleable()
                    ->dateTime(),
                Tables\Columns\TextColumn::make('post_parent')
                    ->label('Parent')
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('comment_count')
                    ->label('Comments')
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
            ])
            ->actions([
                Action::make('Edit')->url(fn ($record): string => "/wp/wp-admin/post.php?post={$record->ID}&action=edit"),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            WpPostMetaRelationManager::class,
            WpCommentRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWpWikis::route('/'),
            'create' => Pages\CreateWpWiki::route('/create'),
            'view' => Pages\ViewWpWiki::route('/{record}'),
            'edit' => Pages\EditWpWiki::route('/{record}/edit'),
        ];
    }
}
