<?php

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
use Moox\Press\Models\WpOption;
use Moox\Press\Resources\WpOptionResource\Pages\CreateWpOption;
use Moox\Press\Resources\WpOptionResource\Pages\EditWpOption;
use Moox\Press\Resources\WpOptionResource\Pages\ListWpOptions;
use Moox\Press\Resources\WpOptionResource\Pages\ViewWpOption;
use Override;

class WpOptionResource extends Resource
{
    use BaseInResource;
    use HasResourceTabs;

    protected static ?string $model = WpOption::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-settings';

    protected static ?string $recordTitleAttribute = 'option_name';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    TextInput::make('option_name')
                        ->label(__('core::core.option_name'))
                        ->rules(['max:191', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('option_value')
                        ->label(__('core::core.option_value'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('autoload')
                        ->label(__('core::core.autoload'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->default('20')
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
                TextColumn::make('option_name')
                    ->label(__('core::core.option_name'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('option_value')
                    ->label(__('core::core.option_value'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('autoload')
                    ->label(__('core::core.autoload'))
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
            'index' => ListWpOptions::route('/'),
            'create' => CreateWpOption::route('/create'),
            'view' => ViewWpOption::route('/{record}'),
            'edit' => EditWpOption::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('press.resources.option.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('press.resources.option.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('press.resources.option.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('press.resources.option.single');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('press.system_navigation_group');
    }
}
