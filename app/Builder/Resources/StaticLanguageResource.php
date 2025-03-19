<?php

declare(strict_types=1);

namespace App\Builder\Resources;

use App\Builder\Models\StaticLanguage;
use App\Builder\Resources\StaticLanguageResource\Pages\CreateStaticLanguage;
use App\Builder\Resources\StaticLanguageResource\Pages\EditStaticLanguage;
use App\Builder\Resources\StaticLanguageResource\Pages\ListStaticLanguages;
use App\Builder\Resources\StaticLanguageResource\Pages\ViewStaticLanguage;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Simple\SingleSimpleInResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Override;

class StaticLanguageResource extends Resource
{
    use BaseInResource;
    use HasResourceTabs;
    use SingleSimpleInResource;

    protected static ?string $model = StaticLanguage::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    #[Override]
    public static function getModelLabel(): string
    {
        return config('previews.static-language.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('previews.static-language.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('previews.static-language.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('previews.static-language.single');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('previews.navigation_group');
    }

    #[Override]
    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(2)
                ->schema([
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextInput::make('title')
                                        ->label('Title')
                                        ->maxLength(255)->required(),
                                    Textarea::make('content')
                                        ->label('Content'),
                                ]),
                        ])
                        ->columnSpan(['lg' => 2]),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    static::getFormActions(),
                                ]),
                        ])
                        ->columnSpan(['lg' => 1]),
                ])
                ->columns(['lg' => 3]),
        ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title'),
                TextColumn::make('content')
                    ->limit(50),
            ])
            ->defaultSort('id', 'desc')
            ->actions([...static::getTableActions()])
            ->bulkActions([...static::getBulkActions()])
            ->filters([
                Filter::make('title')
                    ->form([
                        TextInput::make('title')
                            ->label('Title')
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['title'],
                        fn (Builder $query, $value): Builder => $query->where('title', 'like', sprintf('%%%s%%', $value)),
                    ))
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['title']) {
                            return null;
                        }

                        return 'Title: '.$data['title'];
                    }),
            ]);
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListStaticLanguages::route('/'),
            'create' => CreateStaticLanguage::route('/create'),
            'edit' => EditStaticLanguage::route('/{record}/edit'),
            'view' => ViewStaticLanguage::route('/{record}'),
        ];
    }
}
