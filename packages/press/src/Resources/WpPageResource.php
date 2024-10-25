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
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Table;
use Moox\Core\Traits\TabsInResource;
use Moox\Press\Models\WpPage;
use Moox\Press\Resources\WpPageResource\Pages;
use Moox\Press\Resources\WpPageResource\RelationManagers\WpPostMetaRelationManager;

class WpPageResource extends Resource
{
    use TabsInResource;

    protected static ?string $model = WpPage::class;

    protected static ?string $navigationIcon = 'gmdi-pages';

    protected static ?string $recordTitleAttribute = 'post_title';

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

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                Tables\Columns\TextColumn::make('post_author')
                    ->label(__('core::post.post_author'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('post_date')
                    ->label(__('core::post.post_date'))
                    ->toggleable()
                    ->dateTime(),
                Tables\Columns\TextColumn::make('post_date_gmt')
                    ->label(__('core::post.post_date_gmt'))
                    ->toggleable()
                    ->dateTime(),
                Tables\Columns\TextColumn::make('post_content')
                    ->label(__('core::post.post_content'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('post_title')
                    ->label(__('core::post.post_title'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('post_excerpt')
                    ->label(__('core::post.post_excerpt'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('post_status')
                    ->label(__('core::post.post_status'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('comment_status')
                    ->label(__('core::comment.comment_status'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('ping_status')
                    ->label(__('core::post.ping_status'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('post_password')
                    ->label(__('core::post.post_password'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('post_name')
                    ->label(__('core::post.post_name'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('to_ping')
                    ->label(__('core::post.to_ping'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('pinged')
                    ->label(__('core::post.pinged'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('post_modified')
                    ->label(__('core::post.post_modified'))
                    ->toggleable()
                    ->dateTime(),
                Tables\Columns\TextColumn::make('post_modified_gmt')
                    ->label(__('core::post.post_modified_gmt'))
                    ->toggleable()
                    ->dateTime(),
                Tables\Columns\TextColumn::make('post_content_filtered')
                    ->label(__('core::post.post_content_filtered'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('post_parent')
                    ->label(__('core::post.post_parent'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('guid')
                    ->label(__('core::core.guid'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('menu_order')
                    ->label(__('core::core.menu_order'))
                    ->toggleable()
                    ->searchable(true, null, true),
                Tables\Columns\TextColumn::make('post_type')
                    ->label(__('core::post.post_type'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('post_mime_type')
                    ->label(__('core::post.post_mime_type'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('comment_count')
                    ->label(__('core::comment.comment_count'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('Wp Edit')->url(fn ($record): string => "/wp/wp-admin/post.php?post={$record->ID}&action=edit"),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
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
            'index' => Pages\ListWpPage::route('/'),
            'create' => Pages\CreateWpPage::route('/create'),
            'view' => Pages\ViewWpPage::route('/{record}'),
            'edit' => Pages\EditWpPage::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return config('press.resources.page.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('press.resources.page.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('press.resources.page.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('press.resources.page.single');
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
