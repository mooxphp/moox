<?php

namespace Moox\PressWiki\Resources;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\PressWiki\Models\WpWikiDepartmentTopic;
use Moox\PressWiki\Resources\WpWikiDepartmentTopicResource\Pages;
use Moox\PressWiki\Resources\WpWikiDepartmentTopicResource\Pages\ListWpWikiDepartmentTopics;
use Override;

class WpWikiDepartmentTopicResource extends Resource
{
    use BaseInResource;
    use HasResourceTabs;

    protected static ?string $model = WpWikiDepartmentTopic::class;

    protected static ?string $navigationIcon = 'gmdi-apartment';

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function form(Form $form): Form
    {
        return $form->schema([
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
            'index' => ListWpWikiDepartmentTopics::route('/'),
            // 'create' => Pages\CreateWpWikiDepartmentTopic::route('/create'),
            // 'view' => Pages\ViewWpWikiDepartmentTopic::route('/{record}'),
            // 'edit' => Pages\EditWpWikiDepartmentTopic::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('press-wiki.resources.wiki-department-topic.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('press-wiki.resources.wiki-department-topic.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('press-wiki.resources.wiki-department-topic.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('press-wiki.resources.wiki-department-topic.single');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('press-wiki.navigation_group');
    }
}
