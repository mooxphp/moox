<?php

namespace Moox\Press\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Press\Models\WpTerm;
use Moox\Press\Resources\WpTagResource\Pages\CreateWpTag;
use Moox\Press\Resources\WpTagResource\Pages\EditWpTag;
use Moox\Press\Resources\WpTagResource\Pages\ListWpTags;
use Moox\Press\Resources\WpTagResource\Pages\ViewWpTag;
use Override;

class WpTagResource extends Resource
{
    use BaseInResource;
    use HasResourceTabs;

    protected static ?string $model = WpTerm::class;

    protected static string | \BackedEnum | null $navigationIcon = 'gmdi-label';

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('termTaxonomy', function ($query): void {
                $query->where('taxonomy', 'post_tag');
            });
    }

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
            'index' => ListWpTags::route('/'),
            'create' => CreateWpTag::route('/create'),
            'view' => ViewWpTag::route('/{record}'),
            'edit' => EditWpTag::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('press.resources.tag.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('press.resources.tag.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('press.resources.tag.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('press.resources.tag.single');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('press.press_navigation_group');
    }
}
