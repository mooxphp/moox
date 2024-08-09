<?php

namespace Moox\Press\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Table;
use Moox\Press\Models\WpMedia;
use Moox\Press\Resources\WpMediaResource\Pages;
use Moox\Press\Resources\WpMediaResource\RelationManagers\WpPostMetaRelationManager;

class WpMediaResource extends Resource
{
    protected static ?string $model = WpMedia::class;

    protected static ?string $navigationIcon = 'gmdi-image';

    protected static ?string $recordTitleAttribute = 'post_title';

    public static function getModelLabel(): string
    {
        return 'Media';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Media';
    }

    public static function getNavigationLabel(): string
    {
        return 'Media';
    }

    protected static ?string $navigationGroup = 'Moox Press';

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
                Stack::make([
                    ImageColumn::make('asset')
                        ->label('Thumbnail')
                        ->square()
                        ->size('100%'),
                    //->url(fn ($record) => $record->getAssetAttribute()),
                ]),
            ])
            ->contentGrid([
                'md' => 4,
                'xl' => 6,
            ])
            ->paginated([
                12,
                24,
                48,
                96,
                'all',
            ]);
        //->actions([ViewAction::make(), EditAction::make()]);
        //->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            WpPostMetaRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWpPosts::route('/'),
            'create' => Pages\CreateWpPost::route('/create'),
            'view' => Pages\ViewWpPost::route('/{record}'),
            'edit' => Pages\EditWpPost::route('/{record}/edit'),
        ];
    }
}
