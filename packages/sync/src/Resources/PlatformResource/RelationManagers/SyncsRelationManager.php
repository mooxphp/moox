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
use Filament\Tables\Table;
use Override;

class SyncsRelationManager extends RelationManager
{
    protected static string $relationship = 'syncs';

    protected static ?string $recordTitleAttribute = 'syncable_type';

    #[Override]
    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                TextInput::make('syncable_id')
                    ->label(__('core::sync.syncable_id'))
                    ->rules(['max:255'])
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                TextInput::make('syncable_type')
                    ->label(__('core::sync.syncable_type'))
                    ->rules(['max:255', 'string'])
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                Select::make('source_platform_id')
                    ->label(__('core::sync.source_platform_id'))
                    ->rules(['exists:platforms,id'])
                    ->relationship('sourcePlatform', 'title')
                    ->searchable()
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                DatePicker::make('last_sync')
                    ->label(__('core::sync.last_sync'))
                    ->rules(['date'])
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
                TextColumn::make('syncable_id')->limit(50)
                    ->label(__('core::sync.syncable_id')),
                TextColumn::make('syncable_type')->limit(50)
                    ->label(__('core::sync.syncable_type')),
                TextColumn::make('sourcePlatform.name')->limit(50)
                    ->label(__('core::sync.source_platform.sync.name')),
                TextColumn::make('targetPlatform.name')->limit(50)
                    ->label(__('core::sync.target_platform.sync.name')),
                TextColumn::make('last_sync')->date()
                    ->label(__('core::sync.last_sync')),

            ])
            ->headerActions([CreateAction::make()])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }
}
