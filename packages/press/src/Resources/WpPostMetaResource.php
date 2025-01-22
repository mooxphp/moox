<?php

namespace Moox\Press\Resources;

use Override;
use Filament\Tables\Columns\TextColumn;
use Moox\Press\Resources\WpPostMetaResource\Pages\ListWpPostMetas;
use Moox\Press\Resources\WpPostMetaResource\Pages\CreateWpPostMeta;
use Moox\Press\Resources\WpPostMetaResource\Pages\ViewWpPostMeta;
use Moox\Press\Resources\WpPostMetaResource\Pages\EditWpPostMeta;
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
use Moox\Press\Models\WpPostMeta;
use Moox\Press\Resources\WpPostMetaResource\Pages;

class WpPostMetaResource extends Resource
{
    use BaseInResource;
    use TabsInResource;
    protected static ?string $model = WpPostMeta::class;

    protected static ?string $navigationIcon = 'gmdi-article';

    protected static ?string $recordTitleAttribute = 'meta_key';

    #[Override]
    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    TextInput::make('post_id')
                        ->label(__('core::post.post_id'))
                        ->rules(['max:255'])
                        ->required()
                        ->default('0')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('meta_key')
                        ->label(__('core::core.meta_key'))
                        ->rules(['max:255', 'string'])
                        ->nullable()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('meta_value')
                        ->label(__('core::core.meta_value'))
                        ->rules(['max:255', 'string'])
                        ->nullable()
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
                TextColumn::make('post_id')
                    ->label(__('core::post.post_id'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('meta_key')
                    ->label(__('core::core.meta_key'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('meta_value')
                    ->label(__('core::core.meta_value'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
            ])
            ->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListWpPostMetas::route('/'),
            'create' => CreateWpPostMeta::route('/create'),
            'view' => ViewWpPostMeta::route('/{record}'),
            'edit' => EditWpPostMeta::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('press.resources.postMeta.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('press.resources.postMeta.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('press.resources.postMeta.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('press.resources.postMeta.single');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('press.meta_navigation_group');
    }

    #[Override]
    public static function getNavigationSort(): ?int
    {
        return config('press.meta_navigation_sort') + 2;
    }
}
