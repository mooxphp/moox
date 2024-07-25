<?php

namespace Moox\Sync\Resources\PlatformResource\RelationManagers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SyncsRelationManager extends RelationManager
{
    protected static string $relationship = 'syncs';

    protected static ?string $recordTitleAttribute = 'syncable_type';

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                TextInput::make('syncable_id')
                    ->rules(['max:255'])
                    ->placeholder('Syncable Id')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                TextInput::make('syncable_type')
                    ->rules(['max:255', 'string'])
                    ->placeholder('Syncable Type')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                Select::make('source_platform_id')
                    ->rules(['exists:platforms,id'])
                    ->relationship('sourcePlatform', 'title')
                    ->searchable()
                    ->placeholder('Source Platform')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                DatePicker::make('last_sync')
                    ->rules(['date'])
                    ->placeholder('Last Sync')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('syncable_id')->limit(50),
                TextColumn::make('syncable_type')->limit(50),
                TextColumn::make('sourcePlatform.name')->limit(
                    50
                ),
                TextColumn::make('targetPlatform.name')->limit(
                    50
                ),
                TextColumn::make('last_sync')->date(),
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (
                                    Builder $query,
                                    $date
                                ): Builder => $query->whereDate(
                                    'created_at',
                                    '>=',
                                    $date
                                )
                            )
                            ->when(
                                $data['created_until'],
                                fn (
                                    Builder $query,
                                    $date
                                ): Builder => $query->whereDate(
                                    'created_at',
                                    '<=',
                                    $date
                                )
                            );
                    }),

                SelectFilter::make('source_platform_id')
                    ->multiple()
                    ->relationship('sourcePlatform', 'title'),

                SelectFilter::make('target_platform_id')
                    ->multiple()
                    ->relationship('targetPlatform', 'title'),
            ])
            ->headerActions([CreateAction::make()])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }
}
