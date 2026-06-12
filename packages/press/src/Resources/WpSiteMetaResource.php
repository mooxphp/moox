<?php

declare(strict_types=1);

namespace Moox\Press\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Press\Models\WpSiteMeta;
use Moox\Press\Resources\WpSiteMetaResource\Pages\CreateWpSiteMeta;
use Moox\Press\Resources\WpSiteMetaResource\Pages\EditWpSiteMeta;
use Moox\Press\Resources\WpSiteMetaResource\Pages\ListWpSiteMetas;
use Moox\Press\Resources\WpSiteMetaResource\Pages\ViewWpSiteMeta;
use Override;

class WpSiteMetaResource extends Resource
{
    use BaseInResource;
    use HasResourceTabs;

    protected static ?string $model = WpSiteMeta::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-public';

    protected static ?string $recordTitleAttribute = 'meta_key';

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) config('press.multisite');
    }

    public static function canAccess(): bool
    {
        return (bool) config('press.multisite');
    }

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    TextInput::make('site_id')
                        ->label(__('core::core.site_id'))
                        ->rules(['max:255'])
                        ->required()
                        ->default('0')
                        ->columnSpan(['default' => 12, 'md' => 12, 'lg' => 12]),

                    TextInput::make('meta_key')
                        ->label(__('core::core.meta_key'))
                        ->rules(['max:255', 'string'])
                        ->nullable()
                        ->columnSpan(['default' => 12, 'md' => 12, 'lg' => 12]),

                    RichEditor::make('meta_value')
                        ->label(__('core::core.meta_value'))
                        ->rules(['string'])
                        ->nullable()
                        ->columnSpan(['default' => 12, 'md' => 12, 'lg' => 12]),
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
                TextColumn::make('site_id')
                    ->label(__('core::core.site_id'))
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
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListWpSiteMetas::route('/'),
            'create' => CreateWpSiteMeta::route('/create'),
            'view' => ViewWpSiteMeta::route('/{record}'),
            'edit' => EditWpSiteMeta::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('press.resources.siteMeta.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('press.resources.siteMeta.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('press.resources.siteMeta.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('press.resources.siteMeta.single');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('press.meta_navigation_group');
    }
}
