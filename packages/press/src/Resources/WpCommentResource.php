<?php

namespace Moox\Press\Resources;

use Override;
use Filament\Tables\Columns\TextColumn;
use Moox\Press\Resources\WpCommentResource\Pages\ListWpComments;
use Moox\Press\Resources\WpCommentResource\Pages\CreateWpComment;
use Moox\Press\Resources\WpCommentResource\Pages\ViewWpComment;
use Moox\Press\Resources\WpCommentResource\Pages\EditWpComment;
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
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Tabs\TabsInResource;
use Moox\Press\Models\WpComment;
use Moox\Press\Resources\WpCommentResource\Pages;
use Moox\Press\Resources\WpCommentResource\RelationManagers\WpCommentMetaRelationManager;

class WpCommentResource extends Resource
{
    use BaseInResource;
    use TabsInResource;
    protected static ?string $model = WpComment::class;

    protected static ?string $navigationIcon = 'gmdi-comment';

    protected static ?string $recordTitleAttribute = 'comment_author';

    #[Override]
    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    TextInput::make('comment_post_ID')
                        ->label(__('core::comment.comment_post_ID'))
                        ->rules(['max:255'])
                        ->required()
                        ->default('0')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('comment_author')
                        ->label(__('core::comment.comment_author'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('comment_author_email')
                        ->label(__('core::comment.comment_author_email'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('comment_author_url')
                        ->label(__('core::comment.comment_author_url'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('comment_author_IP')
                        ->label(__('core::comment.comment_author_IP'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    DateTimePicker::make('comment_date')
                        ->label(__('core::comment.comment_date'))
                        ->rules(['date'])
                        ->required()
                        ->default('0000-00-00 00:00:00')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    DateTimePicker::make('comment_date_gmt')
                        ->label(__('core::comment.comment_date_gmt'))
                        ->rules(['date'])
                        ->required()
                        ->default('0000-00-00 00:00:00')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('comment_content')
                        ->label(__('core::comment.comment_content'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('comment_karma')
                        ->label(__('core::comment.comment_karma'))
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
                        ->label(__('core::comment.comment_approved'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->default('1')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('comment_agent')
                        ->label(__('core::comment.comment_agent'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('comment_type')
                        ->label(__('core::comment.comment_type'))
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
                        ->label(__('core::comment.comment_parent'))
                        ->rules(['max:255'])
                        ->required()
                        ->default('0')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('user_id')
                        ->label(__('core::user.user_id'))
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
                TextColumn::make('comment_post_ID')
                    ->label(__('core::comment.comment_post_ID'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('comment_author')
                    ->label(__('core::comment.comment_author'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('comment_author_email')
                    ->label(__('core::comment.comment_author_email'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('comment_author_url')
                    ->label(__('core::comment.comment_author_url'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('comment_author_IP')
                    ->label(__('core::comment.comment_author_IP'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('comment_date')
                    ->label(__('core::comment.comment_date'))
                    ->toggleable()
                    ->dateTime(),
                TextColumn::make('comment_date_gmt')
                    ->label(__('core::comment.comment_date_gmt'))
                    ->toggleable()
                    ->dateTime(),
                TextColumn::make('comment_content')
                    ->label(__('core::comment.comment_content'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('comment_karma')
                    ->label(__('core::comment.comment_karma'))
                    ->toggleable()
                    ->searchable(true, null, true),
                TextColumn::make('comment_approved')
                    ->label(__('core::comment.comment_approved'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('comment_agent')
                    ->label(__('core::comment.comment_agent'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('comment_type')
                    ->label(__('core::comment.comment_type'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('comment_parent')
                    ->label(__('core::comment.comment_parent'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('user_id')
                    ->label(__('core::user.user_id'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
            ])
            ->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            WpCommentMetaRelationManager::class,
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListWpComments::route('/'),
            'create' => CreateWpComment::route('/create'),
            'view' => ViewWpComment::route('/{record}'),
            'edit' => EditWpComment::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('press.resources.comment.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('press.resources.comment.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('press.resources.comment.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('press.resources.comment.single');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('press.press_navigation_group');
    }

    #[Override]
    public static function getNavigationSort(): ?int
    {
        return config('press.press_navigation_sort') + 6;
    }
}
