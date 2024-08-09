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
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Table;
use Moox\Press\Models\WpComment;
use Moox\Press\Resources\WpCommentResource\Pages;
use Moox\Press\Resources\WpCommentResource\RelationManagers\WpCommentMetaRelationManager;

class WpCommentResource extends Resource
{
    protected static ?string $model = WpComment::class;

    protected static ?string $navigationIcon = 'gmdi-comment';

    protected static ?string $recordTitleAttribute = 'comment_author';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    TextInput::make('comment_post_ID')
                        ->label(__('core::content.comment_post_ID'))
                        ->rules(['max:255'])
                        ->required()
                        ->default('0')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('comment_author')
                        ->label(__('core::content.comment_author'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('comment_author_email')
                        ->label(__('core::content.comment_author_email'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('comment_author_url')
                        ->label(__('core::content.comment_author_url'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('comment_author_IP')
                        ->label(__('core::content.comment_author_IP'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    DateTimePicker::make('comment_date')
                    ->label(__('core::content.comment_date'))
                        ->rules(['date'])
                        ->required()
                        ->default('0000-00-00 00:00:00')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    DateTimePicker::make('comment_date_gmt')
                        ->label(__('core::content.comment_date_gmt'))
                        ->rules(['date'])
                        ->required()
                        ->default('0000-00-00 00:00:00')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('comment_content')
                        ->label(__('core::content.comment_content'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('comment_karma')
                        ->label(__('core::content.comment_karma'))
                        ->rules(['numeric'])
                        ->required()
                        ->numeric()
                        ->default('0')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('comment_approved')
                        ->label(__('core::content.comment_approved'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->default('1')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('comment_agent')
                        ->label(__('core::content.comment_agent'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('comment_type')
                        ->label(__('core::content.comment_type'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Comment Type')
                        ->default('comment')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('comment_parent')
                        ->label(__('core::content.comment_parent'))
                        ->rules(['max:255'])
                        ->required()
                        ->default('0')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('user_id')
                        ->label(__('core::common.user_id'))
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

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                Tables\Columns\TextColumn::make('comment_post_ID')
                    ->label(__('core::content.comment_post_ID'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('comment_author')
                    ->label(__('core::content.comment_author'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('comment_author_email')
                    ->label(__('core::content.comment_author_email'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('comment_author_url')
                    ->label(__('core::content.comment_author_url'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('comment_author_IP')
                    ->label(__('core::content.comment_author_IP'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('comment_date')
                    ->label(__('core::content.comment_date'))
                    ->toggleable()
                    ->dateTime(),
                Tables\Columns\TextColumn::make('comment_date_gmt')
                    ->label(__('core::content.comment_date_gmt'))
                    ->toggleable()
                    ->dateTime(),
                Tables\Columns\TextColumn::make('comment_content')
                    ->label(__('core::content.comment_content'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('comment_karma')
                    ->label(__('core::content.comment_karma'))
                    ->toggleable()
                    ->searchable(true, null, true),
                Tables\Columns\TextColumn::make('comment_approved')
                    ->label(__('core::content.comment_approved'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('comment_agent')
                    ->label(__('core::content.comment_agent'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('comment_type')
                    ->label(__('core::content.comment_type'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('comment_parent')
                    ->label(__('core::content.comment_parent'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('user_id')
                    ->label(__('core::common.user_id'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
            ])
            ->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            WpCommentMetaRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWpComments::route('/'),
            'create' => Pages\CreateWpComment::route('/create'),
            'view' => Pages\ViewWpComment::route('/{record}'),
            'edit' => Pages\EditWpComment::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('core::common.comment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('core::common.comments');
    }

    public static function getNavigationLabel(): string
    {
        return __('core::common.comments');
    }

    public static function getBreadcrumb(): string
    {
        return __('core::common.comment');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('press.press_navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('press.press_navigation_sort') + 2;
    }
}
