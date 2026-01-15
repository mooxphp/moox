<?php

namespace Moox\Prompts\Filament\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Moox\Prompts\Filament\Resources\CommandExecutionResource\Pages;
use Moox\Prompts\Models\CommandExecution;

class CommandExecutionResource extends Resource
{
    protected static ?string $model = CommandExecution::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-clock';
    }

    public static function getNavigationLabel(): string
    {
        return __('moox-prompts::prompts.ui.executions_navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('prompts.navigation_group');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('moox-prompts::prompts.ui.basic_information'))
                ->components([
                    TextInput::make('command_name')
                        ->label(__('moox-prompts::prompts.ui.command_name'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('command_description')
                        ->label(__('moox-prompts::prompts.ui.command_description'))
                        ->maxLength(255),
                    Select::make('status')
                        ->label(__('moox-prompts::prompts.ui.status'))
                        ->options([
                            'running' => __('moox-prompts::prompts.ui.status_running'),
                            'completed' => __('moox-prompts::prompts.ui.status_completed'),
                            'failed' => __('moox-prompts::prompts.ui.status_failed'),
                            'cancelled' => __('moox-prompts::prompts.ui.status_cancelled'),
                        ])
                        ->required(),
                    DateTimePicker::make('started_at')
                        ->label(__('moox-prompts::prompts.ui.started_at'))
                        ->required(),
                    DateTimePicker::make('completed_at')
                        ->label(__('moox-prompts::prompts.ui.completed_at'))
                        ->disabled()
                        ->visible(fn (Get $get): bool => $get('status') === 'completed'),
                    DateTimePicker::make('failed_at')
                        ->label(__('moox-prompts::prompts.ui.failed_at'))
                        ->disabled()
                        ->visible(fn (Get $get): bool => $get('status') === 'failed'),
                    TextInput::make('failed_at_step')
                        ->label(__('moox-prompts::prompts.ui.failed_at_step'))
                        ->disabled()
                        ->visible(fn (Get $get): bool => $get('status') === 'failed'),
                    DateTimePicker::make('cancelled_at')
                        ->label(__('moox-prompts::prompts.ui.cancelled_at'))
                        ->disabled()
                        ->visible(fn (Get $get): bool => $get('status') === 'cancelled'),
                    TextInput::make('cancelled_at_step')
                        ->label(__('moox-prompts::prompts.ui.cancelled_at_step'))
                        ->disabled()
                        ->visible(fn (Get $get): bool => $get('status') === 'cancelled'),
                ])
                ->columns(2),
            Section::make(__('moox-prompts::prompts.ui.details'))
                ->components([
                    KeyValue::make('context')
                        ->label(__('moox-prompts::prompts.ui.context'))
                        ->disabled(),
                    KeyValue::make('steps')
                        ->label(__('moox-prompts::prompts.ui.steps'))
                        ->disabled(),
                ])
                ->collapsible(),
            Section::make(__('moox-prompts::prompts.ui.step_outputs'))
                ->components([
                    KeyValue::make('step_outputs')
                        ->label(__('moox-prompts::prompts.ui.step_outputs'))
                        ->disabled(),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('command_name')
                    ->label(__('moox-prompts::prompts.ui.command_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('command_description')
                    ->label(__('moox-prompts::prompts.ui.command_description'))
                    ->searchable()
                    ->limit(50),
                BadgeColumn::make('status')
                    ->label(__('moox-prompts::prompts.ui.status'))
                    ->colors([
                        'warning' => 'running',
                        'success' => 'completed',
                        'danger' => 'failed',
                        'gray' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'running' => __('moox-prompts::prompts.ui.status_running'),
                        'completed' => __('moox-prompts::prompts.ui.status_completed'),
                        'failed' => __('moox-prompts::prompts.ui.status_failed'),
                        'cancelled' => __('moox-prompts::prompts.ui.status_cancelled'),
                        default => $state,
                    }),
                TextColumn::make('createdBy.name')
                    ->label(__('moox-prompts::prompts.ui.user'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->createdBy ? $record->createdBy->name : '-'),
                TextColumn::make('started_at')
                    ->label(__('moox-prompts::prompts.ui.started_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label(__('moox-prompts::prompts.ui.completed_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('failed_at')
                    ->label(__('moox-prompts::prompts.ui.failed_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('failed_at_step')
                    ->label(__('moox-prompts::prompts.ui.failed_at_step'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cancelled_at')
                    ->label(__('moox-prompts::prompts.ui.cancelled_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('cancelled_at_step')
                    ->label(__('moox-prompts::prompts.ui.cancelled_at_step'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('moox-prompts::prompts.ui.status'))
                    ->options([
                        'running' => __('moox-prompts::prompts.ui.status_running'),
                        'completed' => __('moox-prompts::prompts.ui.status_completed'),
                        'failed' => __('moox-prompts::prompts.ui.status_failed'),
                        'cancelled' => __('moox-prompts::prompts.ui.status_cancelled'),
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('started_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommandExecutions::route('/'),
            'view' => Pages\ViewCommandExecution::route('/{record}'),
        ];
    }
}
