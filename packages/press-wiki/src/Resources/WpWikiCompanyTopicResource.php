<?php

namespace Moox\PressWiki\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\PressWiki\Models\WpWikiCompanyTopic;
use Moox\PressWiki\Resources\WpWikiCompanyTopicResource\Pages;
use Moox\PressWiki\Resources\WpWikiCompanyTopicResource\Pages\ListWpWikiCompanyTopics;
use Override;

class WpWikiCompanyTopicResource extends Resource
{
    use BaseInResource;
    use HasResourceTabs;

    protected static ?string $model = WpWikiCompanyTopic::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-apartment';

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    TextInput::make('name')
                        ->label(__('core::core.name'))
                        ->rules(['max:200', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('slug')
                        ->label(__('core::core.slug'))
                        ->rules(['max:200', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('term_group')
                        ->label(__('core::core.term_group'))
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
                TextColumn::make('name')
                    ->label(__('core::core.name'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('slug')
                    ->label(__('core::core.slug'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('term_group')
                    ->label(__('core::core.term_group'))
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
            'index' => ListWpWikiCompanyTopics::route('/'),
            // 'create' => Pages\CreateWpWikiCompanyTopic::route('/create'),
            // 'view' => Pages\ViewWpWikiCompanyTopic::route('/{record}'),
            // 'edit' => Pages\EditWpWikiCompanyTopic::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('press-wiki.resources.wiki-company-topic.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('press-wiki.resources.wiki-company-topic.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('press-wiki.resources.wiki-company-topic.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('press-wiki.resources.wiki-company-topic.single');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('press-wiki.navigation_group');
    }
}
