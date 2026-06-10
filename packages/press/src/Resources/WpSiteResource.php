<?php

declare(strict_types=1);

namespace Moox\Press\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Press\Models\WpSite;
use Moox\Press\Resources\WpSiteResource\Pages\CreateWpSite;
use Moox\Press\Resources\WpSiteResource\Pages\EditWpSite;
use Moox\Press\Resources\WpSiteResource\Pages\ListWpSites;
use Moox\Press\Resources\WpSiteResource\Pages\ViewWpSite;
use Override;

class WpSiteResource extends Resource
{
    use BaseInResource;
    use HasResourceTabs;

    protected static ?string $model = WpSite::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-public';

    protected static ?string $recordTitleAttribute = 'domain';

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
                Grid::make(['default' => 12])->schema([
                    TextInput::make('domain')
                        ->label(__('core::core.domain'))
                        ->rules(['max:200', 'string'])
                        ->required()
                        ->columnSpan(['default' => 12, 'md' => 6, 'lg' => 6]),

                    TextInput::make('path')
                        ->label(__('core::core.path'))
                        ->rules(['max:100', 'string'])
                        ->required()
                        ->default('/')
                        ->columnSpan(['default' => 12, 'md' => 6, 'lg' => 6]),

                    TextInput::make('site_name')
                        ->label(__('core::core.site_name'))
                        ->rules(['max:255', 'string'])
                        ->nullable()
                        ->columnSpan(['default' => 12, 'md' => 6, 'lg' => 6]),

                    TextInput::make('admin_email')
                        ->label(__('core::core.admin_email'))
                        ->email()
                        ->rules(['max:255'])
                        ->nullable()
                        ->columnSpan(['default' => 12, 'md' => 6, 'lg' => 6]),

                    TextInput::make('siteurl')
                        ->label(__('core::core.siteurl'))
                        ->rules(['max:255', 'string'])
                        ->nullable()
                        ->columnSpan(['default' => 12, 'md' => 6, 'lg' => 6]),

                    Select::make('registration')
                        ->label(__('core::core.registration'))
                        ->options([
                            'none' => 'none',
                            'user' => 'user',
                            'blog' => 'blog',
                            'all' => 'all',
                        ])
                        ->default('none')
                        ->columnSpan(['default' => 12, 'md' => 6, 'lg' => 6]),

                    TextInput::make('upload_filetypes')
                        ->label(__('core::core.upload_filetypes'))
                        ->rules(['max:255', 'string'])
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
                TextColumn::make('id')
                    ->label(__('core::core.id'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->sortable(),
                TextColumn::make('domain')
                    ->label(__('core::core.domain'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('path')
                    ->label(__('core::core.path'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
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
            'index' => ListWpSites::route('/'),
            'create' => CreateWpSite::route('/create'),
            'view' => ViewWpSite::route('/{record}'),
            'edit' => EditWpSite::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('press.resources.site.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('press.resources.site.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('press.resources.site.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('press.resources.site.single');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('press.system_navigation_group');
    }
}
