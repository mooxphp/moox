<?php

namespace Moox\Press\Resources\WpPostResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;

class WpCommentRelationManager extends RelationManager
{
    protected static string $relationship = 'comment';

    protected static ?string $recordTitleAttribute = 'title';

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    TextInput::make('comment_post_ID')
                        ->label(__('core::comment.comment_post_id'))
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
                        ->label(__('core::comment.comment_author_ip'))
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
                        ->label(__('core::common.title'))
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

    public function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('comment_post_ID')
                    ->label(__('core::comment.comment_post_id'))
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
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }
}
