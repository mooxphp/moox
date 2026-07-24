<?php

declare(strict_types=1);

namespace Moox\Transform\Filament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Moox\Core\Entities\Items\Record\BaseRecordResource;
use Moox\Transform\Filament\Resources\TransformRecordResource\Pages;
use Moox\Transform\Jobs\RunTransformRecordJob;
use Moox\Transform\Models\TransformRecord;
use Moox\Transform\Support\Execution\BulkTransformSummaryFormatter;

class TransformRecordResource extends BaseRecordResource
{
    protected static ?string $model = TransformRecord::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static function getEntityType(): string
    {
        return 'transform-record';
    }

    public static function getModelLabel(): string
    {
        return (string) config('transform-record.single', 'Transform Record');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) config('transform-record.plural', 'Transform Records');
    }

    public static function getNavigationLabel(): string
    {
        return (string) config('transform-record.plural', 'Transform Records');
    }

    public static function getBreadcrumb(): string
    {
        return (string) config('transform-record.single', 'Transform Record');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('transform-record.navigation_group', 'Transform');
    }

    public static function getNavigationSort(): ?int
    {
        return (int) config('transform.navigation_sort', 200) + 1;
    }

    public static function form(Schema $form): Schema
    {
        return $form->components([
            Grid::make()
                ->schema([
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    Select::make('transform_definition_id')
                                        ->label(__('transform::fields.transform_definition'))
                                        ->relationship('definition', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required(),
                                    TextInput::make('destination_key')
                                        ->label(__('transform::fields.destination_key'))
                                        ->maxLength(255)
                                        ->suffixAction(
                                            Action::make('openDestination')
                                                ->icon('heroicon-o-arrow-top-right-on-square')
                                                ->url(fn (?TransformRecord $record): ?string => static::resolveDestinationUrl($record))
                                                ->openUrlInNewTab()
                                                ->visible(fn (?TransformRecord $record): bool => static::resolveDestinationUrl($record) !== null)
                                        ),
                                    KeyValue::make('source_projection')
                                        ->label(__('transform::fields.source_projection')),
                                    Textarea::make('error_message')
                                        ->label(__('transform::fields.error_message'))
                                        ->rows(3),
                                    Textarea::make('bulk_result_summary')
                                        ->label(__('transform::fields.bulk_result'))
                                        ->rows(12)
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->formatStateUsing(fn (?TransformRecord $record): string => $record instanceof TransformRecord
                                            ? BulkTransformSummaryFormatter::formatForDisplay($record)
                                            : '')
                                        ->visible(fn (?TransformRecord $record): bool => $record instanceof TransformRecord
                                            && is_array($record->bulk_stats)
                                            && $record->bulk_stats !== []),
                                    KeyValue::make('bulk_stats')
                                        ->label(__('transform::fields.bulk_stats'))
                                        ->visible(fn (?TransformRecord $record): bool => $record instanceof TransformRecord
                                            && is_array($record->bulk_stats)
                                            && $record->bulk_stats !== []),
                                ])->columns(1)->columnSpan(2),
                        ])
                        ->columnSpan(2),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    static::getFormActions(),
                                ]),
                            Section::make('Status')
                                ->schema([
                                    Select::make('status')
                                        ->label(__('transform::fields.status'))
                                        ->options([
                                            'pending' => 'pending',
                                            'processing' => 'processing',
                                            'processed' => 'processed',
                                            'failed' => 'failed',
                                            'failed_validation' => 'failed_validation',
                                            'skipped' => 'skipped',
                                        ])
                                        ->required(),
                                    Select::make('validation_status')
                                        ->label(__('transform::fields.validation_status'))
                                        ->options([
                                            'pending' => 'pending',
                                            'valid' => 'valid',
                                            'invalid' => 'invalid',
                                        ])
                                        ->required(),
                                ]),
                        ])
                        ->columns(1)
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('definition.name')
                    ->label(__('transform::fields.transform_definition'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('definition.destination_model')
                    ->label(__('transform::fields.destination_model'))
                    ->formatStateUsing(fn (?string $state): string => static::formatDestinationModelLabel($state))
                    ->toggleable(),
                TextColumn::make('destination_key')
                    ->label(__('transform::fields.destination_key'))
                    ->url(fn (TransformRecord $record): ?string => static::resolveDestinationUrl($record))
                    ->openUrlInNewTab()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('transform::fields.status'))
                    ->badge()
                    ->color(
                        fn ($record): string => match ($record->status) {
                            'pending' => 'gray',
                            'processing' => 'warning',
                            'processed' => 'success',
                            'failed' => 'danger',
                            'updated' => 'info',
                            default => 'gray',
                        }
                    )
                    ->sortable(),
                TextColumn::make('validation_status')
                    ->label(__('transform::fields.validation_status'))
                    ->badge(
                        fn ($record): string => match ($record->validation_status) {
                            'pending' => 'gray',
                            'valid' => 'success',
                            'invalid' => 'danger',
                        }
                    )
                    ->color(
                        fn ($record): string => match ($record->validation_status) {
                            'pending' => 'gray',
                            'valid' => 'success',
                            'invalid' => 'danger',
                        }
                    )
                    ->sortable(),
                IconColumn::make('degraded')
                    ->label(__('transform::fields.degraded'))
                    ->boolean(),
                TextColumn::make('attempts')
                    ->label(__('transform::fields.attempts'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('last_run_at')
                    ->label(__('transform::fields.last_run_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_success_at')
                    ->label(__('transform::fields.last_success_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('bulk_stats.total')
                    ->label(__('transform::fields.bulk_total'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('bulk_stats.failed')
                    ->label(__('transform::fields.bulk_failed'))
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state): string => ((int) $state) > 0 ? 'danger' : 'gray')
                    ->toggleable(),
                TextColumn::make('error_message')
                    ->label(__('transform::fields.error_message'))
                    ->limit(80)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                ...static::getTableActions(),
                Action::make('run')
                    ->label('Run')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->action(function (TransformRecord $record): void {
                        RunTransformRecordJob::dispatch((int) $record->getKey());

                        Notification::make()
                            ->title('Run queued')
                            ->body('Transform record was dispatched to the queue.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([...static::getBulkActions()])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'pending',
                        'processing' => 'processing',
                        'processed' => 'processed',
                        'failed' => 'failed',
                        'failed_validation' => 'failed_validation',
                        'skipped' => 'skipped',
                    ]),
                SelectFilter::make('validation_status')
                    ->options([
                        'pending' => 'pending',
                        'valid' => 'valid',
                        'invalid' => 'invalid',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransformRecords::route('/'),
            'create' => Pages\CreateTransformRecord::route('/create'),
            'edit' => Pages\EditTransformRecord::route('/{record}/edit'),
            'view' => Pages\ViewTransformRecord::route('/{record}'),
        ];
    }

    public static function resolveDestinationUrl(?TransformRecord $record): ?string
    {
        if (! $record instanceof TransformRecord) {
            return null;
        }

        $destinationKey = $record->destination_key;
        if (! is_string($destinationKey) || $destinationKey === '') {
            return null;
        }

        $definition = $record->definition;
        if ($definition === null) {
            return null;
        }

        $destinationModel = $definition->destination_model;
        if (! is_string($destinationModel) || $destinationModel === '') {
            return null;
        }

        $resourceClass = static::resolveDestinationResourceClass($destinationModel);
        if (! is_string($resourceClass) || $resourceClass === '' || ! class_exists($resourceClass)) {
            return null;
        }

        if (! method_exists($resourceClass, 'getUrl')) {
            return null;
        }

        return $resourceClass::getUrl('edit', ['record' => $destinationKey]);
    }

    public static function formatDestinationModelLabel(?string $destinationModel): string
    {
        if (! is_string($destinationModel) || $destinationModel === '') {
            return '';
        }

        return class_basename($destinationModel);
    }

    /**
     * @return class-string|null
     */
    private static function resolveDestinationResourceClass(string $destinationModel): ?string
    {
        $configured = config('transform-record.destination_resources', config('transform.destination_resources', []));
        if (! is_array($configured)) {
            return null;
        }

        $resourceClass = $configured[$destinationModel] ?? null;

        return is_string($resourceClass) && $resourceClass !== '' ? $resourceClass : null;
    }
}
