<?php

namespace Moox\Press\Resources;

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
use Moox\Core\Traits\Tabs\TabsInResource;
use Moox\Press\Models\WpUserMeta;
use Moox\Press\Resources\WpUserMetaResource\Pages;

class WpUserMetaResource extends Resource
{
    use TabsInResource;

    protected static ?string $model = WpUserMeta::class;

    protected static ?string $navigationIcon = 'gmdi-manage-accounts';

    protected static ?string $recordTitleAttribute = 'meta_key';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
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

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                Tables\Columns\TextColumn::make('user_id')
                    ->label(__('core::user.user_id'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('meta_key')
                    ->label(__('core::core.meta_key'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('meta_value')
                    ->label(__('core::core.meta_value'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
            ])
            ->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWpUserMetas::route('/'),
            'create' => Pages\CreateWpUserMeta::route('/create'),
            'view' => Pages\ViewWpUserMeta::route('/{record}'),
            'edit' => Pages\EditWpUserMeta::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return config('press.resources.userMeta.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('press.resources.userMeta.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('press.resources.userMeta.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('press.resources.userMeta.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('press.meta_navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('press.meta_navigation_sort') + 8;
    }
}
