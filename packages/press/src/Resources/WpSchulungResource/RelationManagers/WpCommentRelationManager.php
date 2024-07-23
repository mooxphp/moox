<?php

namespace Moox\Press\Resources\WpWikiResource\RelationManagers;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Table;

class WpCommentRelationManager extends RelationManager
{
    protected static string $relationship = 'comment';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {

        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    TextInput::make('comment_post_ID')
                        ->rules(['max:255'])
                        ->required()
                        ->placeholder('Comment Post Id')
                        ->default('0')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('comment_author')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Comment Author')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('comment_author_email')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Comment Author Email')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('comment_author_url')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Comment Author Url')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('comment_author_IP')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Comment Author Ip')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    DateTimePicker::make('comment_date')
                        ->rules(['date'])
                        ->required()
                        ->placeholder('Comment Date')
                        ->default('0000-00-00 00:00:00')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    DateTimePicker::make('comment_date_gmt')
                        ->rules(['date'])
                        ->required()
                        ->placeholder('Comment Date Gmt')
                        ->default('0000-00-00 00:00:00')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('comment_content')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Comment Content')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('comment_karma')
                        ->rules(['numeric'])
                        ->required()
                        ->numeric()
                        ->placeholder('Comment Karma')
                        ->default('0')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('comment_approved')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Comment Approved')
                        ->default('1')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('comment_agent')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Comment Agent')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('comment_type')
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
                        ->rules(['max:255'])
                        ->required()
                        ->placeholder('Comment Parent')
                        ->default('0')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('user_id')
                        ->rules(['max:255'])
                        ->required()
                        ->placeholder('User Id')
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
                Tables\Columns\TextColumn::make('comment_post_ID')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('comment_author')
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('comment_author_email')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('comment_author_url')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('comment_author_IP')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('comment_date')
                    ->toggleable()
                    ->dateTime(),
                Tables\Columns\TextColumn::make('comment_date_gmt')
                    ->toggleable()
                    ->dateTime(),
                Tables\Columns\TextColumn::make('comment_content')
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('comment_karma')
                    ->toggleable()
                    ->searchable(true, null, true),
                Tables\Columns\TextColumn::make('comment_approved')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('comment_agent')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('comment_type')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('comment_parent')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('user_id')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
            ])
            ->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }
}
