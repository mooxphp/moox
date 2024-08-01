<?php

namespace Moox\Expiry\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Expiry\Models\Expiry;
use Moox\Expiry\Resources\ExpiryResource\Pages;

class ExpiryResource extends Resource
{
    protected static ?string $model = Expiry::class;

    protected static ?string $navigationIcon = 'gmdi-access-time-o';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationGroup = 'Moox Expiry';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    TextInput::make('title')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Title')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('slug')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Slug')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('item')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Item')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('link')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Link')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    DateTimePicker::make('expired_at')
                        ->rules(['date'])
                        ->required()
                        ->placeholder('Expired At')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    DateTimePicker::make('notified_at')
                        ->rules(['date'])
                        ->placeholder('Notified At')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('notified_to')
                        ->rules(['max:255', 'string'])
                        ->placeholder('Notified To')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    DateTimePicker::make('escalated_at')
                        ->rules(['date'])
                        ->placeholder('Escalated At')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('escalated_to')
                        ->rules(['max:255', 'string'])
                        ->placeholder('Escalated To')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('handled_by')
                        ->rules(['max:255', 'string'])
                        ->placeholder('Handled By')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    DateTimePicker::make('done_at')
                        ->rules(['date'])
                        ->placeholder('Done At')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('expiry_job')
                        ->required()
                        ->placeholder('Expiry Job')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('category')
                        ->required()
                        ->placeholder('Expiry Job')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('status')
                        ->required()
                        ->placeholder('Expiry Job')
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
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->toggleable()
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('expired_at')
                    ->label('Abgelaufen')
                    ->toggleable()
                    ->sortable()
                    ->since(),
                Tables\Columns\TextColumn::make('notifyUser.display_name')
                    ->label('Verantwortlicher')
                    ->toggleable()
                    ->searchable()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        $wpPrefix = config('press.wordpress_prefix');

                        $tableName = $wpPrefix.'users';

                        return $query
                            ->leftJoin($tableName, 'expiries.notified_to', '=', "{$tableName}.ID")
                            ->orderBy("{$tableName}.display_name", $direction)
                            ->select('expiries.*');
                    })
                    ->limit(50),
                Tables\Columns\TextColumn::make('expiry_job')
                    ->label('Typ')
                    ->toggleable()
                    ->sortable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('category')
                    ->label('Kategorie')
                    ->toggleable()
                    ->sortable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->toggleable()
                    ->sortable()
                    ->searchable()
                    ->limit(50),
            ])
            ->filters([
                SelectFilter::make('expiry_job')
                    ->label('Typ')
                    ->options(Expiry::getExpiryJobOptions()),

                SelectFilter::make('category')
                    ->label('Kategorie')
                    ->options(Expiry::getExpiryCategoryOptions()),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(Expiry::getExpiryStatusOptions()),

                SelectFilter::make('notified_to')
                    ->label('Verantwortlicher')
                    ->options(Expiry::getUserOptions()),
            ])
            ->actions([
                ViewAction::make()
                    ->url(function ($record) {
                        if ($record->category === 'Download') {
                            return "{$record->link}/#dokumente-und-downloads";
                        } elseif ($record->category === 'Aufgabe') {
                            return "{$record->link}/#dokumente-aufgabenliste";
                        } elseif ($record->category === 'OneDrive') {
                            return "{$record->link}/#onedrive-dokumente";
                        } else {
                            return $record->link;
                        }
                    })
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([DeleteBulkAction::make()]);

    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpiries::route('/'),
            'create' => Pages\CreateExpiry::route('/create'),
            'view' => Pages\ViewExpiry::route('/{record}'),
            'edit' => Pages\EditExpiry::route('/{record}/edit'),
        ];
    }
}
