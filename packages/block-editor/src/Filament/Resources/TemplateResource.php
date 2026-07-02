<?php

namespace Moox\BlockEditor\Filament\Resources;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Moox\BlockEditor\Filament\Resources\TemplateResource\Pages\CreateTemplate;
use Moox\BlockEditor\Filament\Resources\TemplateResource\Pages\EditTemplate;
use Moox\BlockEditor\Filament\Resources\TemplateResource\Pages\ListTemplates;
use Moox\BlockEditor\Forms\Components\BlockEditor;
use Moox\BlockEditor\Models\Template;
use UnitEnum;

class TemplateResource extends Resource
{
    protected static ?string $model = Template::class;

    protected static ?string $navigationLabel = 'Templates';

    protected static ?string $modelLabel = 'Template';

    protected static ?string $pluralModelLabel = 'Templates';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::RectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Editor';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('slug')
                    ->maxLength(255)
                    ->nullable()
                    ->unique(Template::class, 'slug', ignoreRecord: true)
                    ->helperText('Optional. Eindeutig, für URLs und die API.')
                    ->columnSpanFull(),

                BlockEditor::make('content')
                    ->label('Inhalt')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListTemplates::route('/'),
            'create' => CreateTemplate::route('/create'),
            'edit' => EditTemplate::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Template $record */
        return [
            'Slug' => $record->slug ? Str::limit((string) $record->slug, 40) : '—',
        ];
    }
}
